<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name'); // Snapshot for historical tracking
            $table->string('role');      // Admin, Faculty, Student
            $table->string('action');    // e.g., Login, Updated Student, SMS Sent
            $table->string('module');    // Authentication, Students, Attendance, etc.
            $table->string('status');    // Success, Failed
            $table->text('description')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('device')->nullable();
            $table->string('browser')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};