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
            $table->string('guest_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->default('General');
            $table->json('reminder_times')->nullable();
            $table->string('frequency_unit')->default('days');
            $table->integer('frequency_value')->default(1);
            $table->string('reminder_sound')->default('small');
            $table->string('custom_sound_path')->nullable();
            $table->boolean('reminder_vibration')->default(true);
            $table->boolean('show_in_drawer')->default(true);
            $table->boolean('notification_sound')->default(true);
            $table->boolean('notification_vibration')->default(true);
            $table->boolean('show_full_screen')->default(false);
            $table->timestamps();
            $table->softDeletes();
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
