<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::create('evaluation_submissions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('academic_period_id')->constrained('academic_periods')->onDelete('cascade');
        $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
        $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
        $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
        $table->decimal('average_score', 4, 2)->default(0.00);
        $table->text('comments')->nullable();
        $table->string('status', 50)->default('SUBMITTED');
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_submissions');
    }
};
