<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'can_evaluate_faculty')) {
                $table->boolean('can_evaluate_faculty')->default(false);
            }
            if (!Schema::hasColumn('users', 'can_view_master_data')) {
                $table->boolean('can_view_master_data')->default(false);
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $columns = array_filter([
                Schema::hasColumn('users', 'can_evaluate_faculty') ? 'can_evaluate_faculty' : null,
                Schema::hasColumn('users', 'can_view_master_data') ? 'can_view_master_data' : null,
            ]);
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
