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
        Schema::create('nfc_cards', function (Blueprint $table) {
            $table->id();
            // Naka-constraint na required (hindi na nullable) dahil binubura na natin ang record kapag na-unbind
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('tag_id', 100)->unique();
            
            // Credential lifecycle tracking
            $table->enum('status', ['active', 'lost', 'damaged', 'replaced'])->default('active');
            $table->text('remarks')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nfc_cards');
    }
};