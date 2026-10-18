<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'date_of_birth')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->date('date_of_birth')->nullable();
            });
        }

        Schema::create('telegram_parent_links', function (Blueprint $table): void {
            $table->id();
            $table->string('telegram_chat_id', 32);
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('pending')->index();
            $table->boolean('notifications_enabled')->default(true);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->unique(['telegram_chat_id', 'student_id']);
            $table->index(['student_id', 'status']);
        });

        Schema::create('telegram_conversations', function (Blueprint $table): void {
            $table->string('telegram_chat_id', 32)->primary();
            $table->string('step', 32);
            $table->text('encrypted_student_id')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::create('telegram_updates', function (Blueprint $table): void {
            $table->unsignedBigInteger('update_id')->primary();
            $table->timestamp('processed_at');
        });

        Schema::create('telegram_notifications', function (Blueprint $table): void {
            $table->id();
            $table->string('notification_key', 191)->unique();
            $table->foreignId('attendance_log_id')->nullable()->constrained('attendance_logs')->nullOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('telegram_parent_link_id')->nullable()->constrained('telegram_parent_links')->nullOnDelete();
            $table->string('telegram_chat_id', 32)->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->string('details', 255)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_notifications');
        Schema::dropIfExists('telegram_updates');
        Schema::dropIfExists('telegram_conversations');
        Schema::dropIfExists('telegram_parent_links');

        if (Schema::hasColumn('users', 'date_of_birth')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('date_of_birth');
            });
        }
    }
};
