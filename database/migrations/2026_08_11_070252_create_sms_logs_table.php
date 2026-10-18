<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('phone_number', 50);
            $table->text('message');
            $table->string('type', 50)->default('ATTENDANCE');
            $table->string('status', 50)->default('SENT');
            $table->text('api_response')->nullable();
            
            // Idinagdag natin ang mga ito para sa Academic Year History & Reset:
            $table->string('academic_year')->nullable();
            $table->string('semester')->nullable();

            $table->timestamps();

            $table->index(['academic_year', 'semester']); // Index para mabilis ang filtering
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};