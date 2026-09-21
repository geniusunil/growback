<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {

            // TIME WINDOW
            $table->boolean('time_range_enabled')
                ->default(false)
                ->after('repeat_enabled');

            $table->string('time_range_mode')
                ->nullable()
                ->after('time_range_enabled');

            $table->json('time_ranges')
                ->nullable()
                ->after('time_range_mode');


            // DAYS OF WEEK
            $table->boolean('weekday_enabled')
                ->default(false)
                ->after('time_ranges');

            $table->string('weekday_mode')
                ->nullable()
                ->after('weekday_enabled');

            $table->json('weekdays')
                ->nullable()
                ->after('weekday_mode');


            // PUBLIC HOLIDAYS
            $table->boolean('holiday_enabled')
                ->default(false)
                ->after('weekdays');

            $table->string('holiday_mode')
                ->nullable()
                ->after('holiday_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {

            $table->dropColumn([
                'time_range_enabled',
                'time_range_mode',
                'time_ranges',

                'weekday_enabled',
                'weekday_mode',
                'weekdays',

                'holiday_enabled',
                'holiday_mode',
            ]);
        });
    }
};