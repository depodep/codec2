<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\TelegramOutboundNotification;
use App\Services\TelegramAttendanceNotificationService;
use App\Services\TelegramBotService;
use Carbon\Carbon;
use GuzzleHttp\Psr7\Response;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Mockery;
use NotificationChannels\Telegram\Telegram;
use NotificationChannels\Telegram\TelegramChannel;
use Tests\TestCase;

class TelegramParentNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.telegram.enabled' => true,
            'services.telegram.bot_token' => 'test-bot-token',
            'services.telegram-bot-api.token' => 'test-bot-token',
            'services.telegram.webhook_secret' => 'test-webhook-secret',
        ]);
        Http::fake(fn () => Http::response(['ok' => true, 'result' => []]));
        Notification::fake();
    }

    public function test_telegram_bot_is_disabled_by_default(): void
    {
        config(['services.telegram.enabled' => false]);

        $this->assertFalse(app(TelegramBotService::class)->enabled());
        $this->assertFalse(app(TelegramBotService::class)->configured());

        $this->postJson('/api/telegram/webhook', [
            'update_id' => 8001,
            'message' => [
                'text' => '/start',
                'chat' => ['id' => 900001, 'type' => 'private'],
            ],
        ], [
            'X-Telegram-Bot-Api-Secret-Token' => 'test-webhook-secret',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Telegram bot is disabled.');

        Notification::assertNothingSent();
        $this->assertDatabaseCount('telegram_updates', 0);

        $admin = User::query()->create([
            'role_id' => 1,
            'first_name' => 'School',
            'last_name' => 'Admin',
            'email' => 'telegram-disabled-admin@example.test',
            'password' => Hash::make('password'),
        ]);
        $this->withoutMiddleware(EnsureEmailIsVerified::class)
            ->actingAs($admin)
            ->get(route('admin.telegram.index', [], false))
            ->assertOk()
            ->assertSee('Bot disabled')
            ->assertDontSee('Test bot connection');

        try {
            app(TelegramBotService::class)->sendMessage('900001', 'This should not be sent.');
            $this->fail('A disabled Telegram bot must reject outgoing messages.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Telegram bot is disabled.', $exception->getMessage());
        }
    }

    public function test_admin_telegram_page_shows_summary_cards_and_ajax_returns_modal_content_without_page_reload(): void
    {
        $admin = User::query()->create([
            'role_id' => 1,
            'first_name' => 'School',
            'last_name' => 'Admin',
            'email' => 'telegram-summary-admin@example.test',
            'password' => Hash::make('password'),
        ]);
        $firstStudent = $this->createStudent();
        $secondStudent = $this->createStudent('123456789013', 'summary-second@example.test');
        $notLinkedStudent = $this->createStudent('123456789014', 'summary-third@example.test');
        DB::table('telegram_parent_links')->insert([
            'telegram_chat_id' => '900003',
            'student_id' => $notLinkedStudent->id,
            'status' => 'revoked',
            'notifications_enabled' => false,
            'approved_at' => null,
            'revoked_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $linkIds = [];
        foreach ([$firstStudent, $secondStudent] as $student) {
            $linkIds[] = DB::table('telegram_parent_links')->insertGetId([
                'telegram_chat_id' => '900001',
                'student_id' => $student->id,
                'status' => 'approved',
                'notifications_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $page = $this->withoutMiddleware(EnsureEmailIsVerified::class)
            ->actingAs($admin)
            ->get(route('admin.telegram.index', [], false));

        $page
            ->assertOk()
            ->assertSee('Telegram Settings')
            ->assertSee('Linked 2')
            ->assertSee('Not linked 1')
            ->assertSee('Student Links')
            ->assertSee('Parent Links (3)')
            ->assertSee('Not linked')
            ->assertDontSee('revoked')
            ->assertSee('Attendance Notification History')
            ->assertSee('Test Connection')
            ->assertSee('All Grades')
            ->assertSee('All Sections')
            ->assertSee('All Teachers')
            ->assertSee('student-link-filters')
            ->assertSee('parent-link-filters')
            ->assertSee('>1</td>', false)
            ->assertSee('>2</td>', false);
        $this->assertSame(2, preg_match_all('/<div id="(?:student|parent)-links-modal" data-list-modal/', $page->getContent()));

        $this->get(route('admin.telegram.index', [], false), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('Linked 2')
            ->assertSee('Not linked 1')
            ->assertDontSee('<html', false)
            ->assertSee('Attendance Notification History');

        $this->get(route('admin.telegram.index', ['student_status' => 'unlinked'], false))
            ->assertOk()
            ->assertViewHas('studentCodes', fn ($studentCodes) => $studentCodes->total() === 1)
            ->assertSee('Linked 2')
            ->assertSee('Not linked 1');

        $this->postJson(route('admin.telegram.test-connection', [], false))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Telegram bot connection verified.');

        $this->postJson(route('admin.telegram.links.toggle-notifications', $linkIds[0], false))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Notification preference updated.');
        $this->assertDatabaseHas('telegram_parent_links', [
            'id' => $linkIds[0],
            'notifications_enabled' => false,
        ]);
    }

    public function test_admin_telegram_filters_by_grade_section_teacher_and_ajax_pagination_numbers_rows(): void
    {
        $admin = User::query()->create([
            'role_id' => 1,
            'first_name' => 'School',
            'last_name' => 'Admin',
            'email' => 'telegram-filter-admin@example.test',
            'password' => Hash::make('password'),
        ]);
        $teacher = User::query()->create([
            'role_id' => 2,
            'first_name' => 'Taylor',
            'last_name' => 'Teacher',
            'email' => 'telegram-filter-teacher@example.test',
            'section' => 'Grade 11 A',
            'password' => Hash::make('password'),
        ]);
        DB::table('academic_sections')->insert([
            'grade_level' => '11',
            'section_name' => 'Grade 11 A',
            'advisor_id' => $teacher->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        for ($index = 1; $index <= 12; $index++) {
            $student = $this->createStudent(
                '123456789'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                "telegram-filter-student-{$index}@example.test"
            );
            $student->update([
                'first_name' => 'Filtered'.$index,
                'grade_level' => $index === 12 ? '12' : '11',
                'section' => $index === 12 ? 'Grade 12 B' : 'Grade 11 A',
            ]);
            DB::table('telegram_parent_links')->insert([
                'telegram_chat_id' => (string) (910000 + $index),
                'student_id' => $student->id,
                'status' => 'approved',
                'notifications_enabled' => true,
                'created_at' => now()->addSeconds($index),
                'updated_at' => now(),
            ]);
        }

        $this->withoutMiddleware(EnsureEmailIsVerified::class)
            ->actingAs($admin)
            ->get(route('admin.telegram.index', [
                'parent_grade' => '11',
                'parent_section' => 'Grade 11 A',
                'parent_teacher' => $teacher->id,
            ], false))
            ->assertOk()
            ->assertViewHas('links', fn ($links) => $links->total() === 11)
            ->assertSee('Grade 11 A')
            ->assertSee('Taylor Teacher');

        $this->get(route('admin.telegram.index', [
            'parent_grade' => '11',
            'parent_section' => 'Grade 11 A',
            'parent_teacher' => $teacher->id,
            'parent_links_page' => 2,
        ], false), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('>11</td>', false)
            ->assertDontSee('>12</td>', false);

        $this->get(route('admin.telegram.index', [
            'student_grade' => '11',
            'student_section' => 'Grade 11 A',
            'student_status' => 'linked',
        ], false))
            ->assertOk()
            ->assertViewHas('studentCodes', fn ($studentCodes) => $studentCodes->total() === 11
                && ! $studentCodes->pluck('first_name')->contains('Filtered12'))
            ->assertSee('Filtered1')
            ->assertSee('Linked 12')
            ->assertSee('Not linked 0')
            ->assertSee('Parent Links (12)');
    }

    public function test_student_codes_are_generated_for_new_students_and_admin_can_regenerate_and_export_them(): void
    {
        $admin = User::query()->create([
            'role_id' => 1,
            'first_name' => 'School',
            'last_name' => 'Admin',
            'email' => 'telegram-code-admin@example.test',
            'password' => Hash::make('password'),
        ]);
        $student = $this->createStudent();
        $originalCode = DB::table('telegram_student_link_codes')
            ->where('student_id', $student->id)
            ->value('code');

        $this->assertNotEmpty($originalCode);
        $this->assertSame(10, strlen($originalCode));

        $this->withoutMiddleware(EnsureEmailIsVerified::class)
            ->actingAs($admin)
            ->postJson(route('admin.telegram.students.regenerate-code', $student->id, false))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'New Telegram linking code generated. The previous code is no longer valid.')
            ->assertJsonStructure(['code']);

        $newCode = DB::table('telegram_student_link_codes')
            ->where('student_id', $student->id)
            ->value('code');
        $this->assertNotSame($originalCode, $newCode);

        $this->sendWebhook(1801, '/link')->assertOk();
        $this->sendWebhook(1802, '123456789012')->assertOk();
        $this->sendWebhook(1803, $originalCode)->assertOk();
        $this->assertDatabaseCount('telegram_parent_links', 0);
        $this->sendWebhook(1804, '/link')->assertOk();
        $this->sendWebhook(1805, '123456789012')->assertOk();
        $this->sendWebhook(1806, $newCode)->assertOk();
        $this->assertDatabaseHas('telegram_parent_links', [
            'telegram_chat_id' => '900001',
            'student_id' => $student->id,
            'status' => 'approved',
        ]);

        $this->get(route('admin.telegram.student-link-codes.export', [], false))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_regenerating_a_student_code_from_user_management_redirects_to_telegram_settings_with_the_new_code(): void
    {
        $admin = User::query()->create([
            'role_id' => 1,
            'first_name' => 'School',
            'last_name' => 'Admin',
            'email' => 'telegram-redirect-admin@example.test',
            'password' => Hash::make('password'),
        ]);
        $student = $this->createStudent();

        $this->withoutMiddleware(EnsureEmailIsVerified::class)
            ->actingAs($admin)
            ->post(route('admin.telegram.students.regenerate-code', $student->id, false))
            ->assertRedirect(route('admin.telegram.index', [], false))
            ->assertSessionHas('regeneratedTelegramCode', function (array $result) use ($student): bool {
                return $result['success'] === true
                    && $result['student_name'] === trim($student->first_name.' '.$student->last_name)
                    && $result['code'] === DB::table('telegram_student_link_codes')
                        ->where('student_id', $student->id)
                        ->value('code');
            });

        $newCode = DB::table('telegram_student_link_codes')
            ->where('student_id', $student->id)
            ->value('code');

        $this->get(route('admin.telegram.index', [], false))
            ->assertOk()
            ->assertSee('Telegram Settings')
            ->assertSee($student->first_name.' '.$student->last_name)
            ->assertSee($newCode);
    }

    public function test_lrn_and_school_code_link_student_without_birthday_or_admin_approval(): void
    {
        $student = $this->createStudent();
        $code = DB::table('telegram_student_link_codes')->where('student_id', $student->id)->value('code');

        $this->sendWebhook(1001, '/link')->assertOk();
        $this->sendWebhook(1002, '123456789012')->assertOk();
        $this->sendWebhook(1003, strtolower($code))->assertOk();

        $this->assertDatabaseHas('telegram_parent_links', [
            'telegram_chat_id' => '900001',
            'student_id' => $student->id,
            'status' => 'approved',
            'notifications_enabled' => 1,
        ]);
        $this->assertDatabaseMissing('telegram_conversations', ['telegram_chat_id' => '900001']);
        Notification::assertSentOnDemand(
            TelegramOutboundNotification::class,
            fn (TelegramOutboundNotification $notification) => ! str_contains($notification->text, '123456789012')
                && ! str_contains($notification->text, $code)
                && str_contains($notification->text, 'linked successfully')
        );
    }

    public function test_school_link_code_can_link_the_same_student_to_multiple_guardians(): void
    {
        $student = $this->createStudent();
        $code = DB::table('telegram_student_link_codes')->where('student_id', $student->id)->value('code');

        foreach ([['900001', 1900], ['900002', 1910]] as [$chatId, $updateId]) {
            $this->sendWebhook($updateId, '/link', $chatId)->assertOk();
            $this->sendWebhook($updateId + 1, '123456789012', $chatId)->assertOk();
            $this->sendWebhook($updateId + 2, $code, $chatId)->assertOk();
        }

        $this->assertDatabaseCount('telegram_parent_links', 2);
        $this->assertDatabaseHas('telegram_parent_links', [
            'telegram_chat_id' => '900001',
            'student_id' => $student->id,
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('telegram_parent_links', [
            'telegram_chat_id' => '900002',
            'student_id' => $student->id,
            'status' => 'approved',
        ]);
    }

    public function test_parent_can_link_multiple_children_review_them_toggle_alerts_and_unlink_by_lrn(): void
    {
        $firstStudent = $this->createStudent();
        $firstStudent->update(['parent_name' => 'Patricia Sample']);
        $secondStudent = $this->createStudent('123456789013', 'second-child@example.test');
        $firstCode = DB::table('telegram_student_link_codes')->where('student_id', $firstStudent->id)->value('code');
        $secondCode = DB::table('telegram_student_link_codes')->where('student_id', $secondStudent->id)->value('code');

        $this->sendWebhook(1949, '/unlink')->assertOk();
        $this->assertDatabaseMissing('telegram_conversations', [
            'telegram_chat_id' => '900001',
            'step' => 'awaiting_unlink_lrn',
        ]);
        Notification::assertSentOnDemand(
            TelegramOutboundNotification::class,
            fn (TelegramOutboundNotification $notification) => str_contains($notification->text, 'No children are currently linked')
        );

        $this->sendWebhook(1950, '/start')->assertOk();
        Notification::assertSentOnDemand(
            TelegramOutboundNotification::class,
            fn (TelegramOutboundNotification $notification) => $notification->keyboard !== null
                && collect($notification->keyboard)->flatten(1)->contains(
                    fn (array $button) => $button['text'] === 'Link Another Child'
                        && $button['callback_data'] === 'link'
                )
                && ! collect($notification->keyboard)->flatten(1)->contains(
                    fn (array $button) => $button['text'] === 'Unlink a Child'
                        && $button['callback_data'] === 'unlink'
                )
        );

        $this->sendCallback(1951, '900001', 'link')->assertOk();
        $this->sendWebhook(1952, '123456789012')->assertOk();
        $this->sendWebhook(1953, $firstCode)->assertOk();
        $this->sendCallback(1954, '900001', 'link')->assertOk();
        $this->sendWebhook(1955, '123456789013')->assertOk();
        $this->sendWebhook(1956, $secondCode)->assertOk();

        $this->assertDatabaseCount('telegram_parent_links', 2);
        Notification::assertSentOnDemand(
            TelegramOutboundNotification::class,
            fn (TelegramOutboundNotification $notification) => str_contains($notification->text, 'Sample Student was linked successfully')
                && str_contains($notification->text, 'Patricia Sample')
        );

        $this->sendWebhook(1963, '/start')->assertOk();
        Notification::assertSentOnDemand(
            TelegramOutboundNotification::class,
            fn (TelegramOutboundNotification $notification) => $notification->keyboard !== null
                && collect($notification->keyboard)->flatten(1)->contains(
                    fn (array $button) => $button['text'] === 'Unlink a Child'
                        && $button['callback_data'] === 'unlink'
                )
        );

        $this->sendCallback(1957, '900001', 'students')->assertOk();
        Notification::assertSentOnDemand(
            TelegramOutboundNotification::class,
            fn (TelegramOutboundNotification $notification) => str_contains($notification->text, 'Your linked children:')
                && str_contains($notification->text, 'Sample Student — notifications On')
        );

        $firstLinkId = DB::table('telegram_parent_links')
            ->where('telegram_chat_id', '900001')
            ->where('student_id', $firstStudent->id)
            ->value('id');
        $this->sendCallback(1958, '900001', 'toggle:'.$firstLinkId)->assertOk();
        $this->assertDatabaseHas('telegram_parent_links', [
            'id' => $firstLinkId,
            'notifications_enabled' => 0,
        ]);
        $this->sendCallback(1962, '900001', 'students')->assertOk();
        Notification::assertSentOnDemand(
            TelegramOutboundNotification::class,
            fn (TelegramOutboundNotification $notification) => str_contains($notification->text, 'Sample Student — notifications Off')
                && str_contains($notification->text, 'notifications On')
        );

        $this->sendCallback(1959, '900001', 'unlink')->assertOk();
        $this->assertDatabaseHas('telegram_conversations', [
            'telegram_chat_id' => '900001',
            'step' => 'awaiting_unlink_lrn',
        ]);
        $this->sendWebhook(1960, '123456789012')->assertOk();
        $this->sendCallback(1961, '900001', 'unlink_yes:'.$firstLinkId)->assertOk();
        $this->assertDatabaseHas('telegram_parent_links', [
            'id' => $firstLinkId,
            'status' => 'revoked',
            'notifications_enabled' => 0,
        ]);
        $this->assertDatabaseHas('telegram_parent_links', [
            'telegram_chat_id' => '900001',
            'student_id' => $secondStudent->id,
            'status' => 'approved',
        ]);
    }

    public function test_advisory_and_scheduled_class_absences_send_separate_notifications_for_the_same_student(): void
    {
        $this->travelTo(Carbon::parse('2026-10-19 09:29:00'));

        $teacher = User::query()->create([
            'role_id' => 2,
            'first_name' => 'Taylor',
            'last_name' => 'Teacher',
            'email' => 'absence-context-teacher@example.test',
            'password' => Hash::make('password'),
        ]);
        $absentStudent = $this->createStudent();
        $absentStudent->update(['section' => 'Grade 7 A', 'grade_level' => '7']);
        $presentStudent = $this->createStudent('123456789013', 'present-student@example.test');
        $presentStudent->update(['section' => 'Grade 7 A', 'grade_level' => '7']);
        $sectionId = DB::table('academic_sections')->insertGetId([
            'grade_level' => '7',
            'section_name' => 'Grade 7 A',
            'advisor_id' => $teacher->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        foreach ([$absentStudent, $presentStudent] as $student) {
            DB::table('section_student')->insert([
                'student_id' => $student->id,
                'section_id' => $sectionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $scheduleId = DB::table('class_schedules')->insertGetId([
            'section_id' => $sectionId,
            'teacher_id' => $teacher->id,
            'subject_name' => 'Mathematics',
            'grade_level' => '7',
            'section' => 'Grade 7 A',
            'day' => 'Mon',
            'start_time' => '09:00:00',
            'end_time' => '09:50:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $linkId = DB::table('telegram_parent_links')->insertGetId([
            'telegram_chat_id' => '900001',
            'student_id' => $absentStudent->id,
            'status' => 'approved',
            'notifications_enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('attendance_logs')->insert([
            'user_id' => $presentStudent->id,
            'attendance_date' => '2026-10-19',
            'class_schedule_id' => $scheduleId,
            'attendance_type' => 'subject',
            'status' => 'ON-TIME',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('attendance_logs')->insert([
            'user_id' => $presentStudent->id,
            'attendance_date' => '2026-10-19',
            'attendance_type' => 'advisory',
            'advisory_section_id' => $sectionId,
            'time_in' => '08:00:00',
            'status' => 'ON-TIME',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('settings')->insert([
            ['key' => 'advisory_absence_grace_minutes', 'value' => '60', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'subject_absence_grace_minutes', 'value' => '45', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->artisan('attendance:check-advisory', ['--date' => '2026-10-19'])
            ->assertExitCode(0);
        $this->artisan('attendance:check-completed-subjects')->assertExitCode(0);

        $this->assertDatabaseMissing('telegram_notifications', [
            'notification_key' => "advisory_absence:2026-10-19:{$sectionId}:{$absentStudent->id}:{$linkId}",
        ]);
        $this->assertDatabaseMissing('telegram_notifications', [
            'notification_key' => "subject_absence:2026-10-19:{$scheduleId}:{$absentStudent->id}:{$linkId}",
        ]);

        $this->travelTo(Carbon::parse('2026-10-19 09:30:00'));
        $this->artisan('attendance:check-advisory', ['--date' => '2026-10-19'])
            ->assertExitCode(0);
        $this->artisan('attendance:check-completed-subjects')->assertExitCode(0);

        $this->assertDatabaseHas('telegram_notifications', [
            'notification_key' => "advisory_absence:2026-10-19:{$sectionId}:{$absentStudent->id}:{$linkId}",
            'status' => 'sent',
            'details' => 'Advisory absence notification',
        ]);
        $this->assertDatabaseMissing('telegram_notifications', [
            'notification_key' => "subject_absence:2026-10-19:{$scheduleId}:{$absentStudent->id}:{$linkId}",
        ]);

        $this->travelTo(Carbon::parse('2026-10-19 09:45:00'));
        $this->artisan('attendance:check-completed-subjects')->assertExitCode(0);

        $this->assertDatabaseHas('telegram_notifications', [
            'notification_key' => "subject_absence:2026-10-19:{$scheduleId}:{$absentStudent->id}:{$linkId}",
            'status' => 'sent',
            'details' => 'Subject absence notification',
        ]);
        Notification::assertSentOnDemandTimes(TelegramOutboundNotification::class, 2);
        Notification::assertSentOnDemand(
            TelegramOutboundNotification::class,
            fn (TelegramOutboundNotification $notification) => str_contains($notification->text, 'was recorded absent from advisory')
                && str_contains($notification->text, 'Grade 7 A')
        );
        Notification::assertSentOnDemand(
            TelegramOutboundNotification::class,
            fn (TelegramOutboundNotification $notification) => str_contains($notification->text, 'absent from Mathematics')
        );
    }

    public function test_invalid_credentials_use_a_generic_response_and_do_not_create_a_link(): void
    {
        $student = $this->createStudent();

        $this->sendWebhook(1101, '/link')->assertOk();
        $this->sendWebhook(1102, '999999999999')->assertOk();
        $this->sendWebhook(1103, 'INVALIDCODE')->assertOk();
        $this->sendWebhook(1104, '/link')->assertOk();
        $this->sendWebhook(1105, '123456789012')->assertOk();
        $this->sendWebhook(1106, 'INVALIDCODE')->assertOk();

        $this->assertDatabaseCount('telegram_parent_links', 0);
        Notification::assertSentOnDemand(
            TelegramOutboundNotification::class,
            fn (TelegramOutboundNotification $notification) => str_contains(
                $notification->text,
                'We could not verify those details.'
            )
        );
        Notification::assertSentOnDemand(
            TelegramOutboundNotification::class,
            fn (TelegramOutboundNotification $notification) => ! str_contains($notification->text, '999999999999')
                && ! str_contains($notification->text, (string) DB::table('telegram_student_link_codes')->where('student_id', $student->id)->value('code'))
        );
    }

    public function test_webhook_requires_its_configured_secret_and_ignores_duplicate_updates(): void
    {
        $payload = [
            'update_id' => 1201,
            'message' => [
                'text' => '/start',
                'chat' => ['id' => 900001, 'type' => 'private'],
            ],
        ];

        $this->postJson('/api/telegram/webhook', $payload)->assertUnauthorized();
        $this->postJson('/api/telegram/webhook', $payload, [
            'X-Telegram-Bot-Api-Secret-Token' => 'test-webhook-secret',
        ])->assertOk();
        $this->postJson('/api/telegram/webhook', $payload, [
            'X-Telegram-Bot-Api-Secret-Token' => 'test-webhook-secret',
        ])->assertOk();

        Notification::assertSentOnDemandTimes(TelegramOutboundNotification::class, 1);
        $this->assertDatabaseCount('telegram_updates', 1);
    }

    public function test_attendance_notifications_are_recorded_and_deduplicated_for_approved_parents(): void
    {
        $student = $this->createStudent();
        $linkId = DB::table('telegram_parent_links')->insertGetId([
            'telegram_chat_id' => '900001',
            'student_id' => $student->id,
            'status' => 'approved',
            'notifications_enabled' => true,
            'approved_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $attendanceId = DB::table('attendance_logs')->insertGetId([
            'user_id' => $student->id,
            'attendance_date' => '2026-10-17',
            'time_in' => '08:10:00',
            'status' => 'LATE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(TelegramAttendanceNotificationService::class);
        $service->sendForAttendance($student, $attendanceId, 'TIME IN');
        $service->sendForAttendance($student, $attendanceId, 'TIME IN');

        $this->assertDatabaseHas('telegram_notifications', [
            'notification_key' => "attendance:{$attendanceId}:TIME IN:{$linkId}",
            'telegram_chat_id' => '900001',
            'status' => 'sent',
        ]);
        Notification::assertSentOnDemandTimes(TelegramOutboundNotification::class, 1);
        Notification::assertSentOnDemand(
            TelegramOutboundNotification::class,
            fn (TelegramOutboundNotification $notification) => str_contains(
                $notification->text,
                'was marked Late (time in)'
            )
        );
    }

    public function test_attendance_without_approved_links_is_recorded_as_skipped(): void
    {
        $student = $this->createStudent();
        $attendanceId = DB::table('attendance_logs')->insertGetId([
            'user_id' => $student->id,
            'attendance_date' => '2026-10-17',
            'time_in' => '08:10:00',
            'status' => 'ON-TIME',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(TelegramAttendanceNotificationService::class)
            ->sendForAttendance($student, $attendanceId, 'TIME IN');

        $this->assertDatabaseHas('telegram_notifications', [
            'notification_key' => "attendance:{$attendanceId}:TIME IN:unlinked",
            'status' => 'skipped',
        ]);
        Notification::assertNothingSent();
    }

    public function test_attendance_api_failure_is_recorded_without_throwing(): void
    {
        $student = $this->createStudent();
        $linkId = DB::table('telegram_parent_links')->insertGetId([
            'telegram_chat_id' => '900001',
            'student_id' => $student->id,
            'status' => 'approved',
            'notifications_enabled' => true,
            'approved_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $attendanceId = DB::table('attendance_logs')->insertGetId([
            'user_id' => $student->id,
            'attendance_date' => '2026-10-17',
            'time_in' => '08:10:00',
            'status' => 'ON-TIME',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $bot = Mockery::mock(TelegramBotService::class);
        $bot->shouldReceive('sendMessage')->once()->andThrow(new \RuntimeException('sensitive API error'));

        (new TelegramAttendanceNotificationService($bot))->sendForAttendance($student, $attendanceId, 'TIME IN');

        $this->assertDatabaseHas('telegram_notifications', [
            'notification_key' => "attendance:{$attendanceId}:TIME IN:{$linkId}",
            'status' => 'failed',
            'details' => 'Telegram delivery failed. Check bot configuration and recipient access.',
        ]);
    }

    public function test_failed_telegram_notifications_are_retried_with_the_same_key(): void
    {
        $student = $this->createStudent();
        $linkId = DB::table('telegram_parent_links')->insertGetId([
            'telegram_chat_id' => '900001',
            'student_id' => $student->id,
            'status' => 'approved',
            'notifications_enabled' => true,
            'approved_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $bot = Mockery::mock(TelegramBotService::class);
        $attempt = 0;
        $bot->shouldReceive('sendMessage')
            ->twice()
            ->andReturnUsing(function () use (&$attempt): void {
                $attempt++;
                if ($attempt === 1) {
                    throw new \RuntimeException('temporary API failure');
                }
            });

        $service = new TelegramAttendanceNotificationService($bot);
        $service->sendToStudent($student, 'absence:2026-10-17:'.$student->id, 'Absence message.');
        $service->sendToStudent($student, 'absence:2026-10-17:'.$student->id, 'Absence message.');

        $this->assertDatabaseHas('telegram_notifications', [
            'notification_key' => "absence:2026-10-17:{$student->id}:{$linkId}",
            'status' => 'sent',
        ]);
    }

    public function test_admin_custom_message_only_targets_approved_enabled_parent_links(): void
    {
        $admin = User::query()->create([
            'role_id' => 1,
            'first_name' => 'School',
            'last_name' => 'Admin',
            'email' => 'dispatch-admin@example.test',
            'password' => Hash::make('password'),
        ]);
        $linkedStudent = $this->createStudent();
        $pendingStudent = $this->createStudent('123456789013', 'pending-student@example.test');
        $disabledStudent = $this->createStudent('123456789014', 'disabled-student@example.test');

        foreach ([
            [$linkedStudent, '900001', 'approved', true],
            [$pendingStudent, '900002', 'pending', true],
            [$disabledStudent, '900003', 'approved', false],
        ] as [$student, $chatId, $status, $enabled]) {
            DB::table('telegram_parent_links')->insert([
                'telegram_chat_id' => $chatId,
                'student_id' => $student->id,
                'status' => $status,
                'notifications_enabled' => $enabled,
                'approved_at' => $status === 'approved' ? now() : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->withoutMiddleware(EnsureEmailIsVerified::class)
            ->actingAs($admin)
            ->get(route('admin.message', [], false))
            ->assertOk()
            ->assertSee('Absence Notifications')
            ->assertSee('Telegram notifications')
            ->assertSee('Absent students by section')
            ->assertSee('Absent students without a Telegram link')
            ->assertSee('Confirm absence notifications')
            ->assertSee('Send linked / retry failed')
            ->assertSee('Send a custom message')
            ->assertSee('Absence template')
            ->assertDontSee('custom_number')
            ->assertDontSee('Ping Gateway');

        $this->get('/system/status/sms')->assertRedirect(route('dashboard'));
        $this->post('/send-sms')->assertStatus(405);

        $this->withoutMiddleware(EnsureEmailIsVerified::class)
            ->actingAs($admin)
            ->postJson(route('admin.telegram.send-message', [], false), [
                'recipient_mode' => 'all_linked',
                'message' => 'School announcement.',
                'custom_number' => '900099',
            ])
            ->assertOk()
            ->assertJsonPath('summary.sent', 1)
            ->assertJsonPath('summary.failed', 0);

        Notification::assertSentOnDemandTimes(TelegramOutboundNotification::class, 1);
        Notification::assertSentOnDemand(
            TelegramOutboundNotification::class,
            fn (TelegramOutboundNotification $notification) => $notification->toTelegram(new AnonymousNotifiable)->getPayloadValue('chat_id') === '900001'
                && $notification->text === 'School announcement.'
        );
        $this->assertDatabaseCount('telegram_notifications', 1);
    }

    public function test_admin_and_teacher_can_save_role_specific_custom_templates_with_student_placeholders(): void
    {
        $this->travelTo(Carbon::parse('2026-10-18 10:00:00'));
        $admin = User::query()->create([
            'role_id' => 1,
            'first_name' => 'School',
            'last_name' => 'Admin',
            'email' => 'custom-template-admin@example.test',
            'password' => Hash::make('password'),
        ]);
        $teacher = User::query()->create([
            'role_id' => 2,
            'first_name' => 'Class',
            'last_name' => 'Teacher',
            'email' => 'custom-template-teacher@example.test',
            'section' => 'Grade 7 A',
            'password' => Hash::make('password'),
        ]);
        $firstStudent = $this->createStudent();
        $firstStudent->update([
            'first_name' => 'Ariana',
            'last_name' => 'Santos',
            'parent_name' => 'Rosa Santos',
            'section' => 'Grade 7 A',
            'grade_level' => '7',
        ]);
        $secondStudent = $this->createStudent('123456789013', 'custom-template-second@example.test');
        $secondStudent->update(['first_name' => 'Marco', 'last_name' => 'Reyes', 'section' => 'Grade 8 B', 'grade_level' => '8']);

        foreach ([[$firstStudent, '900001'], [$secondStudent, '900002']] as [$student, $chatId]) {
            DB::table('telegram_parent_links')->insert([
                'telegram_chat_id' => $chatId,
                'student_id' => $student->id,
                'status' => 'approved',
                'notifications_enabled' => true,
                'approved_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $adminTemplate = 'Dear {parent_name}: Hello {first_name} {last_name} - Grade {grade_level}, {section_name}, {date} at {school_name}.';
        $teacherTemplate = 'Notice for {student_name} from {section}, sent by {teacher_name}.';
        $this->withoutMiddleware(EnsureEmailIsVerified::class)
            ->actingAs($admin)
            ->get(route('admin.message', [], false))
            ->assertOk()
            ->assertSee('Save template')
            ->assertSee('{student_name}');
        $this->postJson(route('admin.telegram.custom-message-template.update', [], false), [
                'message_template' => $adminTemplate,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertDatabaseHas('settings', [
            'key' => 'telegram_custom_message_template_admin',
            'value' => $adminTemplate,
        ]);

        $this->withoutMiddleware(EnsureEmailIsVerified::class)
            ->actingAs($teacher)
            ->get(route('teacher.message', [], false))
            ->assertOk()
            ->assertSee('Save template');
        $this->postJson(route('teacher.telegram.custom-message-template.update', [], false), [
                'message_template' => $teacherTemplate,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertDatabaseHas('settings', [
            'key' => 'telegram_custom_message_template_teacher',
            'value' => $teacherTemplate,
        ]);

        $this->withoutMiddleware(EnsureEmailIsVerified::class)
            ->actingAs($admin)
            ->postJson(route('admin.telegram.send-message', [], false), [
                'recipient_mode' => 'all_linked',
                'message' => $adminTemplate,
            ])
            ->assertOk()
            ->assertJsonPath('summary.sent', 2);

        Notification::assertSentOnDemandTimes(TelegramOutboundNotification::class, 2);
        Notification::assertSentOnDemand(
            TelegramOutboundNotification::class,
            fn (TelegramOutboundNotification $notification) => $notification->toTelegram(new AnonymousNotifiable)->getPayloadValue('chat_id') === '900001'
                && $notification->text === 'Dear Rosa Santos: Hello Ariana Santos - Grade 7, Grade 7 A, October 18, 2026 at Southern Isabela Academy.'
        );
        Notification::assertSentOnDemand(
            TelegramOutboundNotification::class,
            fn (TelegramOutboundNotification $notification) => $notification->toTelegram(new AnonymousNotifiable)->getPayloadValue('chat_id') === '900002'
                && $notification->text === 'Dear Parent/Guardian: Hello Marco Reyes - Grade 8, Grade 8 B, October 18, 2026 at Southern Isabela Academy.'
        );

        $this->withoutMiddleware(EnsureEmailIsVerified::class)
            ->actingAs($teacher)
            ->postJson(route('teacher.telegram.send-message', [], false), [
                'recipient_mode' => 'all_linked',
                'message' => $teacherTemplate,
            ])
            ->assertOk()
            ->assertJsonPath('summary.sent', 1);
        Notification::assertSentOnDemandTimes(TelegramOutboundNotification::class, 3);
        Notification::assertSentOnDemand(
            TelegramOutboundNotification::class,
            fn (TelegramOutboundNotification $notification) => $notification->toTelegram(new AnonymousNotifiable)->getPayloadValue('chat_id') === '900001'
                && $notification->text === 'Notice for Ariana Santos from Grade 7 A, sent by Class Teacher.'
        );
    }

    public function test_admin_absence_alerts_send_only_to_enabled_telegram_links(): void
    {
        $admin = User::query()->create([
            'role_id' => 1,
            'first_name' => 'School',
            'last_name' => 'Admin',
            'email' => 'absence-admin@example.test',
            'password' => Hash::make('password'),
        ]);
        $student = $this->createStudent();
        DB::table('telegram_parent_links')->insert([
            'telegram_chat_id' => '900001',
            'student_id' => $student->id,
            'status' => 'approved',
            'notifications_enabled' => true,
            'approved_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withoutMiddleware(EnsureEmailIsVerified::class)
            ->actingAs($admin)
            ->postJson(route('admin.telegram.send-absence-notifications', [], false), [
                'absence_date' => '2026-10-17',
            ])
            ->assertOk()
            ->assertJsonPath('summary.sent', 1)
            ->assertJsonPath('dashboard.sent', 1)
            ->assertJsonPath('dashboard.linked', 1)
            ->assertJsonPath('dashboard.eligible_links', 0)
            ->assertJsonPath('dashboard.already_sent', 1)
            ->assertJsonPath('dashboard.sections.0.sent', 1);

        $this->postJson(route('admin.telegram.send-absence-notifications', [], false), [
            'absence_date' => '2026-10-17',
        ])
            ->assertOk()
            ->assertJsonPath('summary.sent', 0)
            ->assertJsonPath('dashboard.sent', 1);

        Notification::assertSentOnDemand(
            TelegramOutboundNotification::class,
            fn (TelegramOutboundNotification $notification) => str_contains($notification->text, 'was recorded absent on')
        );
        Notification::assertSentOnDemandTimes(TelegramOutboundNotification::class, 1);
        $this->assertDatabaseHas('telegram_notifications', [
            'student_id' => $student->id,
            'telegram_chat_id' => '900001',
            'status' => 'sent',
            'details' => 'Absence notification',
        ]);
    }

    public function test_absence_notifications_only_send_for_selected_sections(): void
    {
        $admin = User::query()->create([
            'role_id' => 1,
            'first_name' => 'School',
            'last_name' => 'Admin',
            'email' => 'selected-sections-admin@example.test',
            'password' => Hash::make('password'),
        ]);
        $stemStudent = $this->createStudent();
        $abmStudent = $this->createStudent('123456789013', 'abm-student@example.test');
        $unlinkedStemStudent = $this->createStudent('123456789014', 'unlinked-stem@example.test');
        $stemStudent->update(['section' => 'STEM A']);
        $unlinkedStemStudent->update(['section' => 'STEM A']);
        $abmStudent->update(['section' => 'ABM A']);
        foreach ([[$stemStudent, '900001'], [$abmStudent, '900002']] as [$student, $chatId]) {
            DB::table('telegram_parent_links')->insert([
                'telegram_chat_id' => $chatId,
                'student_id' => $student->id,
                'status' => 'approved',
                'notifications_enabled' => true,
                'approved_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->withoutMiddleware(EnsureEmailIsVerified::class)
            ->actingAs($admin)
            ->get(route('admin.message', [], false))
            ->assertOk()
            ->assertViewHas('dashboard', fn (array $dashboard): bool => $dashboard['eligible_links'] === 2
                && $dashboard['unlinked'] === 1);

        $this->postJson(route('admin.telegram.send-absence-notifications', [], false), [
                'absence_date' => '2026-10-17',
                'selected_sections' => ['STEM A'],
            ])
            ->assertOk()
            ->assertJsonPath('summary.total', 2)
            ->assertJsonPath('summary.sent', 1)
            ->assertJsonPath('summary.skipped', 1)
            ->assertJsonPath('dashboard.eligible_links', 1)
            ->assertJsonPath('dashboard.unlinked', 1);

        Notification::assertSentOnDemandTimes(TelegramOutboundNotification::class, 1);
        Notification::assertSentOnDemand(
            TelegramOutboundNotification::class,
            fn (TelegramOutboundNotification $notification) => $notification->toTelegram(new AnonymousNotifiable)->getPayloadValue('chat_id') === '900001'
        );
    }

    public function test_admin_can_edit_telegram_absence_template_and_existing_sms_template_is_reused(): void
    {
        $admin = User::query()->create([
            'role_id' => 1,
            'first_name' => 'School',
            'last_name' => 'Admin',
            'email' => 'absence-template-admin@example.test',
            'password' => Hash::make('password'),
        ]);
        DB::table('settings')->insert([
            'key' => 'absence_sms_template',
            'value' => 'Legacy template for {student_name}.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withoutMiddleware(EnsureEmailIsVerified::class)
            ->actingAs($admin)
            ->get(route('admin.message', [], false))
            ->assertOk()
            ->assertSee('Legacy template for {student_name}.');

        $this->post(route('admin.telegram.absence-template.update', [], false), [
            'absence_template' => 'Telegram notice: {student_name} on {date}.',
        ])->assertRedirect()
            ->assertSessionHas('success', 'Telegram absence message template saved.');

        $this->assertDatabaseHas('settings', [
            'key' => 'absence_telegram_template',
            'value' => 'Telegram notice: {student_name} on {date}.',
        ]);
    }

    public function test_teacher_absence_notifications_include_students_in_assigned_classes_only(): void
    {
        $teacher = User::query()->create([
            'role_id' => 2,
            'first_name' => 'Class',
            'last_name' => 'Teacher',
            'email' => 'class-absence-teacher@example.test',
            'password' => Hash::make('password'),
        ]);
        $sectionId = DB::table('academic_sections')->insertGetId([
            'grade_level' => '11',
            'section_name' => 'Grade 11 C',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('class_schedules')->insert([
            'section_id' => $sectionId,
            'teacher_id' => $teacher->id,
            'section' => 'Grade 11 C',
            'subject_name' => 'Science',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $classStudent = $this->createStudent();
        $outsideStudent = $this->createStudent('123456789013', 'outside-class@example.test');
        DB::table('section_student')->insert([
            'student_id' => $classStudent->id,
            'section_id' => $sectionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        foreach ([[$classStudent, '900001'], [$outsideStudent, '900002']] as [$student, $chatId]) {
            DB::table('telegram_parent_links')->insert([
                'telegram_chat_id' => $chatId,
                'student_id' => $student->id,
                'status' => 'approved',
                'notifications_enabled' => true,
                'approved_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->withoutMiddleware(EnsureEmailIsVerified::class)
            ->actingAs($teacher)
            ->postJson(route('teacher.telegram.send-absence-notifications', [], false), [
                'absence_date' => '2026-10-17',
            ])
            ->assertOk()
            ->assertJsonPath('summary.total', 1)
            ->assertJsonPath('summary.sent', 1)
            ->assertJsonPath('dashboard.sections.0.eligible_links', 0);

        Notification::assertSentOnDemandTimes(TelegramOutboundNotification::class, 1);
        Notification::assertSentOnDemand(
            TelegramOutboundNotification::class,
            fn (TelegramOutboundNotification $notification) => $notification->toTelegram(new AnonymousNotifiable)->getPayloadValue('chat_id') === '900001'
        );
    }

    public function test_teacher_custom_messages_are_limited_to_linked_students_in_their_scope(): void
    {
        $teacher = User::query()->create([
            'role_id' => 2,
            'first_name' => 'Class',
            'last_name' => 'Teacher',
            'email' => 'dispatch-teacher@example.test',
            'section' => 'Grade 11 A',
            'password' => Hash::make('password'),
        ]);
        $ownStudent = $this->createStudent();
        $ownStudent->update(['section' => 'Grade 11 A']);
        $otherStudent = $this->createStudent('123456789013', 'other-student@example.test');
        $otherStudent->update(['section' => 'Grade 12 B']);

        foreach ([[$ownStudent, '900001'], [$otherStudent, '900002']] as [$student, $chatId]) {
            DB::table('telegram_parent_links')->insert([
                'telegram_chat_id' => $chatId,
                'student_id' => $student->id,
                'status' => 'approved',
                'notifications_enabled' => true,
                'approved_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->withoutMiddleware(EnsureEmailIsVerified::class)
            ->actingAs($teacher)
            ->postJson(route('teacher.telegram.send-message', [], false), [
                'recipient_mode' => 'all_linked',
                'message' => 'Grade 11 notice.',
            ])
            ->assertOk()
            ->assertJsonPath('summary.sent', 1);

        Notification::assertSentOnDemandTimes(TelegramOutboundNotification::class, 1);
        Notification::assertSentOnDemand(
            TelegramOutboundNotification::class,
            fn (TelegramOutboundNotification $notification) => $notification->toTelegram(new AnonymousNotifiable)->getPayloadValue('chat_id') === '900001'
        );
    }

    public function test_one_parent_can_request_links_for_multiple_students(): void
    {
        $firstStudent = $this->createStudent();
        $secondStudent = $this->createStudent('123456789013', 'second-student@example.test');
        $firstCode = DB::table('telegram_student_link_codes')->where('student_id', $firstStudent->id)->value('code');
        $secondCode = DB::table('telegram_student_link_codes')->where('student_id', $secondStudent->id)->value('code');

        $this->sendWebhook(1601, '/link')->assertOk();
        $this->sendWebhook(1602, '123456789012')->assertOk();
        $this->sendWebhook(1603, $firstCode)->assertOk();
        $this->sendWebhook(1604, '/link')->assertOk();
        $this->sendWebhook(1605, '123456789013')->assertOk();
        $this->sendWebhook(1606, $secondCode)->assertOk();

        $this->assertDatabaseHas('telegram_parent_links', [
            'telegram_chat_id' => '900001',
            'student_id' => $firstStudent->id,
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('telegram_parent_links', [
            'telegram_chat_id' => '900001',
            'student_id' => $secondStudent->id,
            'status' => 'approved',
        ]);
    }

    public function test_five_failed_verifications_clear_state_and_apply_a_cooldown(): void
    {
        $this->createStudent();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $baseUpdateId = 1300 + ($attempt * 3);
            $this->sendWebhook($baseUpdateId, '/link')->assertOk();
            $this->sendWebhook($baseUpdateId + 1, '999999999999')->assertOk();
            $this->sendWebhook($baseUpdateId + 2, 'INVALIDCODE')->assertOk();
        }

        $this->sendWebhook(1400, '/link')->assertOk();

        $this->assertDatabaseMissing('telegram_conversations', ['telegram_chat_id' => '900001']);
        Notification::assertSentOnDemand(
            TelegramOutboundNotification::class,
            fn (TelegramOutboundNotification $notification) => str_contains(
                $notification->text,
                'temporarily unavailable after repeated attempts'
            )
        );
    }

    public function test_parent_link_requires_admin_approval_before_notifications_are_enabled(): void
    {
        $student = $this->createStudent();
        $admin = User::query()->create([
            'role_id' => 1,
            'first_name' => 'School',
            'last_name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => Hash::make('password'),
        ]);
        $linkId = DB::table('telegram_parent_links')->insertGetId([
            'telegram_chat_id' => '900001',
            'student_id' => $student->id,
            'status' => 'pending',
            'notifications_enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withoutMiddleware(EnsureEmailIsVerified::class)
            ->actingAs($admin)
            ->post(route('admin.telegram.links.approve', $linkId, false))
            ->assertRedirect();

        $this->assertDatabaseHas('telegram_parent_links', [
            'id' => $linkId,
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);
        Notification::assertSentOnDemand(
            TelegramOutboundNotification::class,
            fn (TelegramOutboundNotification $notification) => str_contains($notification->text, 'The school approved')
        );
    }

    public function test_parent_can_only_toggle_and_unlink_their_own_approved_students(): void
    {
        $student = $this->createStudent();
        $linkId = DB::table('telegram_parent_links')->insertGetId([
            'telegram_chat_id' => '900001',
            'student_id' => $student->id,
            'status' => 'approved',
            'notifications_enabled' => true,
            'approved_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->sendCallback(1451, '900002', "toggle:{$linkId}")->assertOk();
        $this->assertDatabaseHas('telegram_parent_links', ['id' => $linkId, 'notifications_enabled' => 1]);

        $this->sendCallback(1452, '900001', "toggle:{$linkId}")->assertOk();
        $this->assertDatabaseHas('telegram_parent_links', ['id' => $linkId, 'notifications_enabled' => 0]);

        $this->sendCallback(1453, '900001', "unlink:{$linkId}")->assertOk();
        $this->sendCallback(1454, '900001', "unlink_yes:{$linkId}")->assertOk();
        $this->assertDatabaseHas('telegram_parent_links', [
            'id' => $linkId,
            'status' => 'revoked',
            'notifications_enabled' => 0,
        ]);
    }

    public function test_outbound_messages_use_the_package_with_plain_text_and_inline_keyboard(): void
    {
        $keyboard = [[['text' => 'Link Student', 'callback_data' => 'link']]];
        $message = (new TelegramOutboundNotification('900001', 'Welcome safely.', $keyboard))
            ->toTelegram(new AnonymousNotifiable);

        $this->assertSame('900001', $message->getPayloadValue('chat_id'));
        $this->assertSame('Welcome safely.', $message->getPayloadValue('text'));
        $this->assertArrayNotHasKey('parse_mode', $message->toArray());
        $this->assertSame(
            json_encode(['inline_keyboard' => $keyboard]),
            $message->getPayloadValue('reply_markup')
        );
    }

    public function test_telegram_channel_sends_via_the_package_api_client(): void
    {
        $telegram = Mockery::mock(Telegram::class);
        $telegram->shouldReceive('sendMessage')
            ->once()
            ->with(Mockery::on(fn (array $payload) => ($payload['chat_id'] ?? null) === '900001'
                && ($payload['text'] ?? null) === 'Package delivery test.'))
            ->andReturn(new Response(200, [], json_encode(['ok' => true, 'result' => ['message_id' => 1]])));
        app()->instance(Telegram::class, $telegram);

        $notifiable = (new AnonymousNotifiable)->route('telegram', '900001');
        $result = (new TelegramChannel(app(Dispatcher::class)))->send(
            $notifiable,
            new TelegramOutboundNotification('900001', 'Package delivery test.')
        );

        $this->assertSame(['ok' => true, 'result' => ['message_id' => 1]], $result);
    }

    public function test_webhook_command_requires_https_and_registers_the_secret_header(): void
    {
        $this->artisan('telegram:webhook', ['url' => 'http://siatrack.example/api/telegram/webhook'])
            ->assertExitCode(1);

        $this->artisan('telegram:webhook', ['url' => 'https://siatrack.example/api/telegram/webhook'])
            ->expectsOutput('Telegram webhook registered successfully.')
            ->assertExitCode(0);

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/setWebhook')
            && ($request->data()['url'] ?? null) === 'https://siatrack.example/api/telegram/webhook'
            && ($request->data()['secret_token'] ?? null) === 'test-webhook-secret');
    }

    private function sendWebhook(int $updateId, string $text, string $chatId = '900001')
    {
        return $this->postJson('/api/telegram/webhook', [
            'update_id' => $updateId,
            'message' => [
                'text' => $text,
                'chat' => ['id' => (int) $chatId, 'type' => 'private'],
            ],
        ], [
            'X-Telegram-Bot-Api-Secret-Token' => 'test-webhook-secret',
        ]);
    }

    private function sendCallback(int $updateId, string $chatId, string $data)
    {
        return $this->postJson('/api/telegram/webhook', [
            'update_id' => $updateId,
            'callback_query' => [
                'id' => 'callback-'.$updateId,
                'data' => $data,
                'message' => [
                    'chat' => ['id' => (int) $chatId, 'type' => 'private'],
                ],
            ],
        ], [
            'X-Telegram-Bot-Api-Secret-Token' => 'test-webhook-secret',
        ]);
    }

    private function createStudent(string $lrn = '123456789012', string $email = 'student@example.test'): User
    {
        return User::query()->create([
            'role_id' => 3,
            'first_name' => 'Sample',
            'last_name' => 'Student',
            'email' => $email,
            'id_number' => $lrn,
            'date_of_birth' => '2010-01-02',
            'password' => Hash::make('password'),
        ]);
    }
}
