<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class TelegramStudentLinkCodeService
{
    private const ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public function ensureForStudent(int $studentId): object
    {
        return DB::transaction(function () use ($studentId): object {
            DB::table('users')->where('id', $studentId)->lockForUpdate()->first();

            $existing = DB::table('telegram_student_link_codes')
                ->where('student_id', $studentId)
                ->first();
            if ($existing) {
                return $existing;
            }

            $code = $this->newCode();
            DB::table('telegram_student_link_codes')->insert([
                'student_id' => $studentId,
                'code' => $code,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return DB::table('telegram_student_link_codes')->where('student_id', $studentId)->first();
        });
    }

    public function regenerateForStudent(int $studentId): object
    {
        return DB::transaction(function () use ($studentId): object {
            DB::table('users')->where('id', $studentId)->lockForUpdate()->first();

            $code = $this->newCode($studentId);
            $existing = DB::table('telegram_student_link_codes')
                ->where('student_id', $studentId)
                ->first();
            if ($existing) {
                DB::table('telegram_student_link_codes')->where('student_id', $studentId)->update([
                    'code' => $code,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('telegram_student_link_codes')->insert([
                    'student_id' => $studentId,
                    'code' => $code,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return DB::table('telegram_student_link_codes')->where('student_id', $studentId)->first();
        });
    }

    private function newCode(?int $studentId = null): string
    {
        do {
            $code = '';
            for ($index = 0; $index < 10; $index++) {
                $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
        } while (
            DB::table('telegram_student_link_codes')
                ->where('code', $code)
                ->when($studentId, fn ($query) => $query->where('student_id', '!=', $studentId))
                ->exists()
        );

        return $code;
    }
}
