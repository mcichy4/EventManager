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
        Schema::create('events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organizer_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();

            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            
            $table->string('location');
            $table->unsignedInteger('participant_limit')->nullable();
            
            $table->string('status');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
