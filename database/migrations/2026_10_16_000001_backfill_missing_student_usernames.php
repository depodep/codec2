<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'username')) {
            return;
        }

        DB::table('users')
            ->where('role_id', 3)
            ->where(function ($query): void {
                $query->whereNull('username')->orWhere('username', '');
            })
            ->orderBy('id')
            ->get(['id', 'id_number', 'first_name'])
            ->each(function ($student): void {
                $base = strtolower((string) preg_replace(
                    '/[^a-z0-9]/i',
                    '',
                    (string) $student->id_number . (string) $student->first_name
                ));
                $base = $base !== '' ? $base : 'student' . $student->id;
                $username = $base;
                $suffix = 2;

                while (DB::table('users')->where('username', $username)->exists()) {
                    $username = $base . $suffix++;
                }

                DB::table('users')->where('id', $student->id)->update(['username' => $username]);
            });
    }

    public function down(): void
    {
    }
};
