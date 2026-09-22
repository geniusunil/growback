<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {

            /*
             * Time Window
             */
            $table->boolean('time_window_enabled')
                ->default(false)
                ->after('reminder_times');

            $table->string('time_window_mode')
                ->nullable()
                ->after('time_window_enabled');

            $table->json('time_window_ranges')
                ->nullable()
                ->after('time_window_mode');


            /*
             * Days of Week
             */
            $table->boolean('days_of_week_enabled')
                ->default(false)
                ->after('time_window_ranges');

            $table->string('days_of_week_mode')
                ->nullable()
                ->after('days_of_week_enabled');

            $table->json('days_of_week')
                ->nullable()
                ->after('days_of_week_mode');


            /*
             * Public Holidays
             */
            $table->boolean('public_holidays_enabled')
                ->default(false)
                ->after('days_of_week');

            $table->string('public_holidays_mode')
                ->nullable()
                ->after('public_holidays_enabled');

           
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {

            $table->dropColumn([
                'time_window_enabled',
                'time_window_mode',
                'time_window_ranges',

                'days_of_week_enabled',
                'days_of_week_mode',
                'days_of_week',

                'public_holidays_enabled',
                'public_holidays_mode',
              
            ]);
        });
    }
};
