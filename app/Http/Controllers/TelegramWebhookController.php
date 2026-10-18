<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TelegramBotService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramBotService $bot)
    {
        if (! $bot->enabled()) {
            return response()->json(['ok' => true, 'message' => 'Telegram bot is disabled.']);
        }

        $secret = $bot->webhookSecret();
        $providedSecret = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');
        if ($secret === '' || ! hash_equals($secret, $providedSecret)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $update = $request->json()->all();
        $updateId = filter_var($update['update_id'] ?? null, FILTER_VALIDATE_INT);
        if ($updateId === false || $updateId === null || $updateId < 0) {
            return response()->json(['message' => 'Invalid update.'], 400);
        }

        $message = $update['message'] ?? null;
        $callback = $update['callback_query'] ?? null;
        $chat = $message['chat'] ?? $callback['message']['chat'] ?? null;
        $chatId = $chat['id'] ?? null;
        if (($chat['type'] ?? null) !== 'private' || ! is_int($chatId) && ! is_string($chatId)) {
            return response()->json(['ok' => true]);
        }

        $chatId = (string) $chatId;
        $lock = Cache::lock('telegram-chat-process:'.hash('sha256', $chatId), 30);
        try {
            $lock->block(3);
        } catch (Throwable) {
            return response()->json(['message' => 'Update is being processed.'], 503);
        }

        try {
            if (DB::table('telegram_updates')->where('update_id', $updateId)->exists()) {
                return response()->json(['ok' => true]);
            }

            $rateKey = 'telegram-chat:'.hash('sha256', $chatId);
            if (RateLimiter::tooManyAttempts($rateKey, 40)) {
                $bot->sendMessage($chatId, 'Too many requests. Please try again in a minute.');
            } else {
                RateLimiter::hit($rateKey, 60);
                if (is_array($callback)) {
                    $this->handleCallback($callback, $chatId, $bot);
                } elseif (is_array($message) && is_string($message['text'] ?? null)) {
                    $this->handleMessage($chatId, trim($message['text']), $bot);
                }
            }

            DB::table('telegram_updates')->insert([
                'update_id' => $updateId,
                'processed_at' => now(),
            ]);

            return response()->json(['ok' => true]);
        } catch (Throwable $exception) {
            Log::warning('Telegram update processing failed.', [
                'update_id' => $updateId,
                'exception' => $exception::class,
            ]);

            return response()->json(['message' => 'Update processing failed.'], 500);
        } finally {
            $lock->release();
        }
    }

    private function handleMessage(string $chatId, string $text, TelegramBotService $bot): void
    {
        $command = strtolower((string) strtok($text, " \t\n@"));
        if (str_starts_with($command, '/')) {
            match ($command) {
                '/start' => $this->showMenu($chatId, $bot),
                '/link' => $this->startLink($chatId, $bot),
                '/my_students' => $this->showStudents($chatId, $bot),
                '/notifications' => $this->showNotificationSettings($chatId, $bot),
                '/unlink' => $this->startUnlink($chatId, $bot),
                '/help' => $bot->sendMessage($chatId, 'Choose Link Another Child to enter a student’s 12-digit LRN and school-issued linking code. Use My Linked Children to see linked students and Notification Settings to turn alerts on or off. If a child is currently linked, choose Unlink a Child to remove that link.'),
                '/cancel' => $this->cancelConversation($chatId, $bot),
                default => $this->showMenu($chatId, $bot),
            };

            return;
        }

        $conversation = DB::table('telegram_conversations')
            ->where('telegram_chat_id', $chatId)
            ->first();
        if (! $conversation) {
            $this->showMenu($chatId, $bot);

            return;
        }
        if (Carbon::parse($conversation->expires_at)->isPast()) {
            $this->clearConversation($chatId);
            $bot->sendMessage($chatId, 'Your session expired. Send /start to see the available options.');

            return;
        }

        if ($conversation->step === 'awaiting_lrn') {
            if (! preg_match('/^\d{12}$/', $text)) {
                $bot->sendMessage($chatId, 'Enter the student’s 12-digit LRN, or send /cancel to stop.');

                return;
            }

            DB::table('telegram_conversations')->where('telegram_chat_id', $chatId)->update([
                'step' => 'awaiting_link_code',
                'encrypted_student_id' => Crypt::encryptString($text),
                'expires_at' => now()->addMinutes(10),
                'updated_at' => now(),
            ]);
            $bot->sendMessage($chatId, 'Enter the school-issued student linking code, or send /cancel to stop.');

            return;
        }

        if ($conversation->step === 'awaiting_unlink_lrn') {
            if (! preg_match('/^\d{12}$/', $text)) {
                $bot->sendMessage($chatId, 'Enter the student’s 12-digit LRN to unlink, or send /cancel to stop.');

                return;
            }

            $this->clearConversation($chatId);
            $link = DB::table('telegram_parent_links as links')
                ->join('users', 'users.id', '=', 'links.student_id')
                ->where('links.telegram_chat_id', $chatId)
                ->where('links.status', 'approved')
                ->where('users.id_number', $text)
                ->select('links.id', 'users.first_name', 'users.last_name')
                ->first();

            if (! $link) {
                $bot->sendMessage($chatId, 'No linked student was found for that LRN on this Telegram account.');

                return;
            }

            $studentName = trim($link->first_name.' '.$link->last_name);
            $bot->sendMessage($chatId, "Unlink {$studentName} and turn off their attendance notifications?", [[
                ['text' => 'Confirm unlink', 'callback_data' => 'unlink_yes:'.$link->id],
                ['text' => 'Cancel', 'callback_data' => 'students'],
            ]]);

            return;
        }

        if ($conversation->step !== 'awaiting_link_code') {
            $this->clearConversation($chatId);
            $this->showMenu($chatId, $bot);

            return;
        }

        $studentIdentifier = Crypt::decryptString($conversation->encrypted_student_id);
        $student = User::query()
            ->where('role_id', 3)
            ->where('id_number', $studentIdentifier)
            ->first();
        $linkCode = DB::table('telegram_student_link_codes')
            ->where('student_id', $student?->id)
            ->where('code', strtoupper(trim($text)))
            ->first();

        if (! $student || ! $linkCode) {
            $this->verificationFailed($chatId, $bot);

            return;
        }

        $this->clearConversation($chatId);
        RateLimiter::clear('telegram-verification:'.hash('sha256', $chatId));

        $existing = DB::table('telegram_parent_links')
            ->where('telegram_chat_id', $chatId)
            ->where('student_id', $student->id)
            ->first();

        if ($existing && $existing->status === 'approved') {
            $bot->sendMessage($chatId, 'This student is already linked to your Telegram account.');

            return;
        }

        DB::table('telegram_parent_links')->updateOrInsert(
            ['telegram_chat_id' => $chatId, 'student_id' => $student->id],
            [
                'status' => 'approved',
                'notifications_enabled' => true,
                'approved_by' => null,
                'approved_at' => now(),
                'revoked_at' => null,
                'updated_at' => now(),
                'created_at' => $existing?->created_at ?? now(),
            ]
        );

        $studentName = trim($student->first_name.' '.$student->last_name);
        $contactName = trim((string) $student->parent_name);
        $message = "{$studentName} was linked successfully. Attendance notifications are now enabled.";
        if ($contactName !== '') {
            $message .= " The emergency-contact parent name recorded in the student’s school profile is {$contactName}.";
        }

        \App\Models\AuditLog::record(
            action: 'Telegram Parent Linked',
            module: 'Telegram Notifications',
            status: 'Success',
            description: "Parent linked Telegram (chat ID: {$chatId}) to student {$studentName} (LRN: {$student->id_number}) via bot.",
            user: null
        );
        $bot->sendMessage($chatId, $message);
    }

    private function handleCallback(array $callback, string $chatId, TelegramBotService $bot): void
    {
        $callbackId = (string) ($callback['id'] ?? '');
        $data = (string) ($callback['data'] ?? '');
        if ($callbackId !== '') {
            $bot->answerCallback($callbackId);
        }

        if ($data === 'link') {
            $this->startLink($chatId, $bot);
        } elseif ($data === 'students') {
            $this->showStudents($chatId, $bot);
        } elseif ($data === 'notifications') {
            $this->showNotificationSettings($chatId, $bot);
        } elseif ($data === 'unlink') {
            $this->startUnlink($chatId, $bot);
        } elseif ($data === 'help') {
            $bot->sendMessage($chatId, 'Choose Link Another Child and enter their 12-digit LRN and school-issued linking code. Use My Linked Children to review links and Notification Settings to manage alerts. If a child is currently linked, choose Unlink a Child to remove that link.');
        } elseif (preg_match('/^toggle:(\d+)$/', $data, $matches)) {
            $link = DB::table('telegram_parent_links')
                ->where('id', $matches[1])
                ->where('telegram_chat_id', $chatId)
                ->where('status', 'approved')
                ->first();
            if ($link) {
                DB::table('telegram_parent_links')->where('id', $link->id)->update([
                    'notifications_enabled' => ! $link->notifications_enabled,
                    'updated_at' => now(),
                ]);
            }
            $this->showNotificationSettings($chatId, $bot);
        } elseif (preg_match('/^unlink:(\d+)$/', $data, $matches)) {
            $link = $this->ownedApprovedLink($chatId, (int) $matches[1]);
            if ($link) {
                $bot->sendMessage($chatId, 'Unlink this student and stop their attendance alerts?', [[
                    ['text' => 'Confirm unlink', 'callback_data' => 'unlink_yes:'.$link->id],
                    ['text' => 'Cancel', 'callback_data' => 'students'],
                ]]);
            } else {
                $bot->sendMessage($chatId, 'This student is not currently linked to your Telegram account.');
            }
        } elseif (preg_match('/^unlink_yes:(\d+)$/', $data, $matches)) {
            $updated = DB::table('telegram_parent_links')
                ->where('id', $matches[1])
                ->where('telegram_chat_id', $chatId)
                ->where('status', 'approved')
                ->update([
                    'status' => 'revoked',
                    'notifications_enabled' => false,
                    'revoked_at' => now(),
                    'updated_at' => now(),
                ]);
            if ($updated === 1) {
                $bot->sendMessage($chatId, 'The student has been unlinked and their attendance notifications are off.');
                $this->showStudents($chatId, $bot);
            } else {
                $bot->sendMessage($chatId, 'That student is no longer linked to this Telegram account.');
            }
        }
    }

    private function showMenu(string $chatId, TelegramBotService $bot): void
    {
        $this->clearConversation($chatId);
        $hasLinks = DB::table('telegram_parent_links')
            ->where('telegram_chat_id', $chatId)
            ->where('status', 'approved')
            ->exists();
        $keyboard = [
            [
                ['text' => 'Link Another Child', 'callback_data' => 'link'],
                ['text' => 'My Linked Children', 'callback_data' => 'students'],
            ],
        ];
        if ($hasLinks) {
            $keyboard[] = [
                ['text' => 'Notification Settings', 'callback_data' => 'notifications'],
                ['text' => 'Unlink a Child', 'callback_data' => 'unlink'],
            ];
        } else {
            $keyboard[] = [
                ['text' => 'Notification Settings', 'callback_data' => 'notifications'],
            ];
        }
        $keyboard[] = [['text' => 'Help', 'callback_data' => 'help']];

        $bot->sendMessage($chatId, 'Welcome to SIATRACK Parent Notifications. Use the options below to manage your children and their attendance alerts.', $keyboard);
    }

    private function startLink(string $chatId, TelegramBotService $bot): void
    {
        if (RateLimiter::tooManyAttempts('telegram-verification:'.hash('sha256', $chatId), 5)) {
            $bot->sendMessage($chatId, 'Verification is temporarily unavailable after repeated attempts. Please try again later or contact the school office.');

            return;
        }

        DB::table('telegram_conversations')->updateOrInsert(
            ['telegram_chat_id' => $chatId],
            [
                'step' => 'awaiting_lrn',
                'encrypted_student_id' => null,
                'expires_at' => now()->addMinutes(10),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $bot->sendMessage($chatId, 'Enter the student’s 12-digit LRN. No student record will be shown in this chat. Send /cancel to stop.');
    }

    private function verificationFailed(string $chatId, TelegramBotService $bot): void
    {
        $key = 'telegram-verification:'.hash('sha256', $chatId);
        RateLimiter::hit($key, 900);
        $this->clearConversation($chatId);
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $bot->sendMessage($chatId, 'We could not verify those details. Linking is temporarily paused; please contact the school office.');

            return;
        }
        $bot->sendMessage($chatId, 'We could not verify those details. No student information was shared. Choose Link Another Child to try again or contact the school office.');
    }

    private function showStudents(string $chatId, TelegramBotService $bot): void
    {
        $links = DB::table('telegram_parent_links as links')
            ->join('users', 'users.id', '=', 'links.student_id')
            ->where('links.telegram_chat_id', $chatId)
            ->where('links.status', 'approved')
            ->select('users.first_name', 'users.last_name', 'links.notifications_enabled')
            ->orderBy('users.last_name')
            ->get();

        if ($links->isEmpty()) {
            $bot->sendMessage($chatId, 'No children are linked to this Telegram account yet. Choose Link Another Child and use the LRN and code provided by the school.');

            return;
        }

        $names = $links->map(fn ($student) => '• '.trim($student->first_name.' '.$student->last_name)
            .' — notifications '.($student->notifications_enabled ? 'On' : 'Off'))->implode("\n");
        $bot->sendMessage($chatId, "Your linked children:\n{$names}");
    }

    private function showNotificationSettings(string $chatId, TelegramBotService $bot): void
    {
        $links = DB::table('telegram_parent_links as links')
            ->join('users', 'users.id', '=', 'links.student_id')
            ->where('links.telegram_chat_id', $chatId)
            ->where('links.status', 'approved')
            ->select('links.id', 'links.notifications_enabled', 'users.first_name', 'users.last_name')
            ->orderBy('users.last_name')
            ->get();

        if ($links->isEmpty()) {
            $bot->sendMessage($chatId, 'Notification settings are available after the school approves a student link.');

            return;
        }

        $keyboard = $links->map(fn ($link) => [[
            'text' => trim($link->first_name.' '.$link->last_name).': '.($link->notifications_enabled ? 'On' : 'Off'),
            'callback_data' => 'toggle:'.$link->id,
        ]])->all();
        $bot->sendMessage($chatId, 'Choose a student to turn their attendance alerts on or off:', $keyboard);
    }

    private function startUnlink(string $chatId, TelegramBotService $bot): void
    {
        $hasLinks = DB::table('telegram_parent_links')
            ->where('telegram_chat_id', $chatId)
            ->where('status', 'approved')
            ->exists();
        if (! $hasLinks) {
            $this->clearConversation($chatId);
            $bot->sendMessage($chatId, 'No children are currently linked to this Telegram account. Choose Link Another Child to link one first.');

            return;
        }

        DB::table('telegram_conversations')->updateOrInsert(
            ['telegram_chat_id' => $chatId],
            [
                'step' => 'awaiting_unlink_lrn',
                'encrypted_student_id' => null,
                'expires_at' => now()->addMinutes(10),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
        $bot->sendMessage($chatId, 'Enter the student’s 12-digit LRN to unlink. We will only search links on this Telegram account. Send /cancel to stop.');
    }

    private function ownedApprovedLink(string $chatId, int $linkId): ?object
    {
        return DB::table('telegram_parent_links')
            ->where('id', $linkId)
            ->where('telegram_chat_id', $chatId)
            ->where('status', 'approved')
            ->first();
    }

    private function cancelConversation(string $chatId, TelegramBotService $bot): void
    {
        $this->clearConversation($chatId);
        $bot->sendMessage($chatId, 'Request cancelled. Send /start whenever you are ready.');
    }

    private function clearConversation(string $chatId): void
    {
        DB::table('telegram_conversations')->where('telegram_chat_id', $chatId)->delete();
    }
}
