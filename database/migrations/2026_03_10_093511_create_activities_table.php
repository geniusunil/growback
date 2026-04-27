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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('guest_id')->nullable(); // For tracking guest sessions/devices
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->default('General');
            $table->json('reminder_times')->nullable(); // Store [ { "time": "hh:mm", "fixed": boolean }, ... ]
            $table->string('frequency_unit')->default('days'); // minutes, hours, days, weeks, months, years
            $table->integer('frequency_value')->default(1); // the 'n' in 'Every n ...'
            $table->string('reminder_sound')->default('small'); // continuous, small, none
            $table->boolean('reminder_vibration')->default(true);
            $table->boolean('show_in_drawer')->default(true);
             
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
