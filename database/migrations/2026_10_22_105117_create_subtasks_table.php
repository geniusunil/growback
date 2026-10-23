
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_subtasks', function (Blueprint $table) {
            $table->id();

            // Parent Activity
            $table->foreignId('parent_activity_id')
                ->constrained('activities')
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority')->nullable();
            $table->date('due_date')->nullable();

            // Reminder Settings
            $table->json('reminder_times')->nullable();
            $table->boolean('repeat_enabled')->default(false);
            $table->string('frequency_unit')->default('none');
            $table->unsignedInteger('frequency_value')->nullable();
            $table->json('weekly_days')->nullable();

            // Mandatory Gap
            $table->boolean('is_mandatory_gap')->default(false);
            $table->unsignedInteger('mandatory_gap_value')->nullable();
            $table->string('mandatory_gap_unit')->nullable();

            // Duration
            $table->string('duration_unit')->nullable();
            $table->decimal('duration_value', 10, 2)->nullable();

            // Time Window
            $table->boolean('time_window_enabled')->default(false);
            $table->string('time_window_mode')->nullable();
            $table->json('time_window_ranges')->nullable();

            // Days of Week
            $table->boolean('days_of_week_enabled')->default(false);
            $table->string('days_of_week_mode')->nullable();
            $table->json('days_of_week')->nullable();

            // Public Holidays
            $table->boolean('public_holidays_enabled')->default(false);
            $table->string('public_holidays_mode')->nullable();

            // Repeat Window
            $table->json('repeat_window')->nullable();

            // Display and Alarm
            $table->boolean('show_full_screen')->default(false);
            $table->string('reminder_sound')->nullable();
            $table->boolean('reminder_vibration')->default(false);
            $table->boolean('show_in_drawer')->default(false);

            // Notification Settings
            $table->boolean('notification_sound')->default(false);
            $table->boolean('notification_vibration')->default(false);
            $table->string('custom_sound_path')->nullable();

            // URLs
            $table->json('urls')->nullable();

            // Completion and Snooze
            $table->boolean('is_completed')->default(false);
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('snoozed_until')->nullable();

            // Sorting
            $table->integer('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_subtasks');
    }
};
