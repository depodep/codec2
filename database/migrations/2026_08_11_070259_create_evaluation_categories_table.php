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
    Schema::create('evaluation_categories', function (Blueprint $table) {
        $table->id();
        $table->string('name', 255);
        $table->text('description')->nullable();
        $table->decimal('weight', 5, 2)->default(0.00);
        $table->integer('order_index')->default(0);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_categories');
    }
};
