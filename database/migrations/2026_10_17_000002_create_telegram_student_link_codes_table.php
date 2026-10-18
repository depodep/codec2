<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public function up(): void
    {
        Schema::create('telegram_student_link_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('code', 10)->unique();
            $table->timestamps();
        });

        DB::table('users')
            ->where('role_id', 3)
            ->orderBy('id')
            ->select('id')
            ->chunk(200, function ($students): void {
                foreach ($students as $student) {
                    DB::table('telegram_student_link_codes')->insert([
                        'student_id' => $student->id,
                        'code' => $this->newCode(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_student_link_codes');
    }

    private function newCode(): string
    {
        do {
            $code = Str::upper(Str::random(10));
            $code = strtr($code, ['0' => '2', '1' => '3', 'I' => 'J', 'L' => 'M', 'O' => 'P']);
        } while (DB::table('telegram_student_link_codes')->where('code', $code)->exists());

        return $code;
    }
};
