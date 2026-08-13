<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Activity;

class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        $anjali = User::where('email', 'thakuranjali8431@gmail.com')->first();
        $testing = User::where('email', 'testingdemo5501@gmail.com')->first();
        $sunil = User::where('email', 'geniusunil@gmail.com')->first();
        $rajesh = User::where('email', 'sharmarajesh3578@gmail.com')->first();


        /*
        |--------------------------------------------------------------------------
        | USER 1 - ANJALI
        |--------------------------------------------------------------------------
        */

        Activity::create([
            'user_id' => $anjali->id,
            'guest_id' => null,
            'title' => 'test',
            'description' => null,
            'category' => 'Personal',
            'duration_value' => 26,
            'duration_unit' => 'hours',
            'due_date' => '2026-08-02 00:00:00',
            'is_completed' => false,
            'completed_at' => null,
            'reminder_times' => json_encode([
                [
                    'time' => '17:00',
                    'fixed' => false,
                    'date' => '2026-07-31'
                ]
            ]),
            'frequency_unit' => 'none',
            'frequency_value' => 0,
            'reminder_sound' => 'continuous',
            'custom_sound_path' => null,
            'reminder_vibration' => true,
            'priority' => 'medium',
            'thumbnail' => null,
            'show_in_drawer' => true,
            'notification_sound' => true,
            'notification_vibration' => true,
            'show_full_screen' => false,
            'deleted_at' => null,
            'created_at' => '2026-07-31 10:44:47',
            'updated_at' => '2026-07-31 10:44:47',
        ]);

        Activity::create([
            'user_id' => $anjali->id,
            'guest_id' => null,
            'title' => 'test2',
            'description' => null,
            'category' => 'Professional',
            'duration_value' => 49,
            'duration_unit' => 'hours',
            'due_date' => '2026-08-04 00:00:00',
            'is_completed' => false,
            'completed_at' => null,
            'reminder_times' => json_encode([
                [
                    'time' => '17:00',
                    'fixed' => false,
                    'date' => '2026-07-31'
                ]
            ]),
            'frequency_unit' => 'none',
            'frequency_value' => 0,
            'reminder_sound' => 'continuous',
            'custom_sound_path' => null,
            'reminder_vibration' => true,
            'priority' => 'high',
            'thumbnail' => null,
            'show_in_drawer' => true,
            'notification_sound' => true,
            'notification_vibration' => true,
            'show_full_screen' => false,
            'deleted_at' => null,
            'created_at' => '2026-07-31 10:45:36',
            'updated_at' => '2026-07-31 10:45:36',
        ]);

        Activity::create([
            'user_id' => $anjali->id,
            'guest_id' => null,
            'title' => 'test3',
            'description' => null,
            'category' => 'Self Care',
            'duration_value' => 72.17,
            'duration_unit' => 'hours',
            'due_date' => '2026-08-01 00:00:00',
            'is_completed' => false,
            'completed_at' => null,
            'reminder_times' => json_encode([
                [
                    'time' => '17:00',
                    'fixed' => false,
                    'date' => '2026-07-31'
                ]
            ]),
            'frequency_unit' => 'none',
            'frequency_value' => 0,
            'reminder_sound' => 'continuous',
            'custom_sound_path' => null,
            'reminder_vibration' => true,
            'priority' => 'medium',
            'thumbnail' => null,
            'show_in_drawer' => true,
            'notification_sound' => true,
            'notification_vibration' => true,
            'show_full_screen' => false,
            'deleted_at' => null,
            'created_at' => '2026-07-31 10:46:31',
            'updated_at' => '2026-07-31 10:46:31',
        ]);

        Activity::create([
            'user_id' => $anjali->id,
            'guest_id' => null,
            'title' => 'act',
            'description' => null,
            'category' => 'Personal',
            'duration_value' => 0,
            'duration_unit' => 'none',
            'due_date' => '2026-08-11 00:00:00',
            'is_completed' => false,
            'completed_at' => null,
            'reminder_times' => json_encode([
                [
                    'time' => '16:00',
                    'fixed' => false,
                    'date' => '2026-08-10'
                ]
            ]),
            'frequency_unit' => 'none',
            'frequency_value' => 0,
            'reminder_sound' => 'continuous',
            'custom_sound_path' => null,
            'reminder_vibration' => true,
            'priority' => 'medium',
            'thumbnail' => null,
            'show_in_drawer' => true,
            'notification_sound' => true,
            'notification_vibration' => true,
            'show_full_screen' => false,
            'deleted_at' => null,
            'created_at' => '2026-08-10 09:32:48',
            'updated_at' => '2026-08-10 09:32:48',
        ]);

        Activity::create([
            'user_id' => $anjali->id,
            'guest_id' => null,
            'title' => 'hi',
            'description' => null,
            'category' => 'Personal',
            'duration_value' => 0,
            'duration_unit' => 'none',
            'due_date' => null,
            'is_completed' => false,
            'completed_at' => null,
            'reminder_times' => json_encode([
                [
                    'time' => '12:00',
                    'fixed' => false,
                    'date' => '2026-08-03'
                ]
            ]),
            'frequency_unit' => 'none',
            'frequency_value' => 0,
            'reminder_sound' => 'continuous',
            'custom_sound_path' => null,
            'reminder_vibration' => true,
            'priority' => 'medium',
            'thumbnail' => null,
            'show_in_drawer' => true,
            'notification_sound' => true,
            'notification_vibration' => true,
            'show_full_screen' => false,
            'deleted_at' => null,
            'created_at' => '2026-08-03 05:47:49',
            'updated_at' => '2026-08-03 05:47:49',
        ]);

        Activity::create([
            'user_id' => $anjali->id,
            'guest_id' => null,
            'title' => 'act2',
            'description' => null,
            'category' => 'Personal',
            'duration_value' => 0,
            'duration_unit' => 'none',
            'due_date' => '2026-08-11 00:00:00',
            'is_completed' => false,
            'completed_at' => null,
            'reminder_times' => json_encode([
                [
                    'time' => '16:00',
                    'fixed' => false,
                    'date' => '2026-08-10'
                ]
            ]),
            'frequency_unit' => 'none',
            'frequency_value' => 0,
            'reminder_sound' => 'continuous',
            'custom_sound_path' => null,
            'reminder_vibration' => true,
            'priority' => 'high',
            'thumbnail' => null,
            'show_in_drawer' => true,
            'notification_sound' => true,
            'notification_vibration' => true,
            'show_full_screen' => false,
            'deleted_at' => null,
            'created_at' => '2026-08-10 09:33:20',
            'updated_at' => '2026-08-10 09:33:20',
        ]);


        /*
        |--------------------------------------------------------------------------
        | USER 2 - TESTING DEMO
        |--------------------------------------------------------------------------
        */

        Activity::create([
            'user_id' => $testing->id,
            'guest_id' => null,
            'title' => 'activity1',
            'description' => null,
            'category' => 'Personal',
            'duration_value' => 0,
            'duration_unit' => 'none',
            'due_date' => '2026-08-08 00:00:00',
            'is_completed' => false,
            'completed_at' => null,
            'reminder_times' => json_encode([
                [
                    'time' => '16:00',
                    'fixed' => false,
                    'date' => '2026-08-07'
                ]
            ]),
            'frequency_unit' => 'none',
            'frequency_value' => 0,
            'reminder_sound' => 'continuous',
            'custom_sound_path' => null,
            'reminder_vibration' => true,
            'priority' => 'high',
            'thumbnail' => null,
            'show_in_drawer' => true,
            'notification_sound' => true,
            'notification_vibration' => true,
            'show_full_screen' => false,
            'deleted_at' => null,
            'created_at' => '2026-08-07 09:32:36',
            'updated_at' => '2026-08-07 09:34:49',
        ]);

        Activity::create([
            'user_id' => $testing->id,
            'guest_id' => null,
            'title' => 'activity2',
            'description' => null,
            'category' => 'Personal',
            'duration_value' => 0,
            'duration_unit' => 'none',
            'due_date' => '2026-08-09 00:00:00',
            'is_completed' => false,
            'completed_at' => null,
            'reminder_times' => json_encode([
                [
                    'time' => '16:00',
                    'fixed' => false,
                    'date' => '2026-08-07'
                ]
            ]),
            'frequency_unit' => 'none',
            'frequency_value' => 0,
            'reminder_sound' => 'continuous',
            'custom_sound_path' => null,
            'reminder_vibration' => true,
            'priority' => 'medium',
            'thumbnail' => null,
            'show_in_drawer' => true,
            'notification_sound' => true,
            'notification_vibration' => true,
            'show_full_screen' => false,
            'deleted_at' => null,
            'created_at' => '2026-08-07 09:33:06',
            'updated_at' => '2026-08-07 09:33:06',
        ]);

        Activity::create([
            'user_id' => $testing->id,
            'guest_id' => null,
            'title' => 'activity3',
            'description' => null,
            'category' => 'Personal',
            'duration_value' => 0,
            'duration_unit' => 'none',
            'due_date' => '2026-08-10 00:00:00',
            'is_completed' => false,
            'completed_at' => null,
            'reminder_times' => json_encode([
                [
                    'time' => '16:00',
                    'fixed' => false,
                    'date' => '2026-08-07'
                ]
            ]),
            'frequency_unit' => 'none',
            'frequency_value' => 0,
            'reminder_sound' => 'continuous',
            'custom_sound_path' => null,
            'reminder_vibration' => true,
            'priority' => 'medium',
            'thumbnail' => null,
            'show_in_drawer' => true,
            'notification_sound' => true,
            'notification_vibration' => true,
            'show_full_screen' => false,
            'deleted_at' => null,
            'created_at' => '2026-08-07 09:33:48',
            'updated_at' => '2026-08-07 09:33:48',
        ]);

        Activity::create([
            'user_id' => $testing->id,
            'guest_id' => null,
            'title' => 'activity4',
            'description' => null,
            'category' => 'Personal',
            'duration_value' => 0,
            'duration_unit' => 'none',
            'due_date' => '2026-08-07 00:00:00',
            'is_completed' => false,
            'completed_at' => null,
            'reminder_times' => json_encode([
                [
                    'time' => '16:00',
                    'fixed' => false,
                    'date' => '2026-08-07'
                ]
            ]),
            'frequency_unit' => 'none',
            'frequency_value' => 0,
            'reminder_sound' => 'continuous',
            'custom_sound_path' => null,
            'reminder_vibration' => true,
            'priority' => 'medium',
            'thumbnail' => null,
            'show_in_drawer' => true,
            'notification_sound' => true,
            'notification_vibration' => true,
            'show_full_screen' => false,
            'deleted_at' => null,
            'created_at' => '2026-08-07 09:34:07',
            'updated_at' => '2026-08-07 09:34:30',
        ]);

        Activity::create([
            'user_id' => $testing->id,
            'guest_id' => null,
            'title' => 'activity5',
            'description' => null,
            'category' => 'Personal',
            'duration_value' => 0,
            'duration_unit' => 'none',
            'due_date' => '2026-08-07 00:00:00',
            'is_completed' => false,
            'completed_at' => null,
            'reminder_times' => json_encode([
                [
                    'time' => '16:00',
                    'fixed' => false,
                    'date' => '2026-08-07'
                ]
            ]),
            'frequency_unit' => 'none',
            'frequency_value' => 0,
            'reminder_sound' => 'continuous',
            'custom_sound_path' => null,
            'reminder_vibration' => true,
            'priority' => 'high',
            'thumbnail' => null,
            'show_in_drawer' => true,
            'notification_sound' => true,
            'notification_vibration' => true,
            'show_full_screen' => false,
            'deleted_at' => null,
            'created_at' => '2026-08-07 09:35:25',
            'updated_at' => '2026-08-07 09:35:25',
        ]);

        Activity::create([
            'user_id' => $testing->id,
            'guest_id' => null,
            'title' => 'new one',
            'description' => null,
            'category' => 'Personal',
            'duration_value' => 0,
            'duration_unit' => 'none',
            'due_date' => null,
            'is_completed' => false,
            'completed_at' => null,
            'reminder_times' => json_encode([
                [
                    'time' => '16:17',
                    'fixed' => false,
                    'date' => '2026-08-07'
                ]
            ]),
            'frequency_unit' => 'none',
            'frequency_value' => 0,
            'reminder_sound' => 'continuous',
            'custom_sound_path' => null,
            'reminder_vibration' => true,
            'priority' => 'medium',
            'thumbnail' => null,
            'show_in_drawer' => true,
            'notification_sound' => true,
            'notification_vibration' => true,
            'show_full_screen' => true,
            'deleted_at' => null,
            'created_at' => '2026-08-07 10:48:30',
            'updated_at' => '2026-08-07 10:48:30',
        ]);


        /*
        |--------------------------------------------------------------------------
        | USER 3 - SUNIL
        |--------------------------------------------------------------------------
        */

        Activity::create([
            'user_id' => $sunil->id,
            'guest_id' => null,
            'title' => '1',
            'description' => null,
            'category' => 'Personal',
            'duration_value' => 144,
            'duration_unit' => 'hours',
            'due_date' => '2026-08-18 00:00:00',
            'is_completed' => false,
            'completed_at' => null,
            'reminder_times' => json_encode([
                [
                    'time' => '17:00',
                    'fixed' => false,
                    'date' => '2026-08-10'
                ]
            ]),
            'frequency_unit' => 'none',
            'frequency_value' => 0,
            'reminder_sound' => 'continuous',
            'custom_sound_path' => null,
            'reminder_vibration' => true,
            'priority' => 'low',
            'thumbnail' => null,
            'show_in_drawer' => true,
            'notification_sound' => true,
            'notification_vibration' => true,
            'show_full_screen' => false,
            'deleted_at' => null,
            'created_at' => '2026-08-10 10:36:32',
            'updated_at' => '2026-08-11 11:13:54',
        ]);

        Activity::create([
            'user_id' => $sunil->id,
            'guest_id' => null,
            'title' => '2',
            'description' => null,
            'category' => 'Personal',
            'duration_value' => 48,
            'duration_unit' => 'hours',
            'due_date' => '2026-08-14 00:00:00',
            'is_completed' => false,
            'completed_at' => null,
            'reminder_times' => json_encode([
                [
                    'time' => '17:00',
                    'fixed' => false,
                    'date' => '2026-08-10'
                ]
            ]),
            'frequency_unit' => 'none',
            'frequency_value' => 0,
            'reminder_sound' => 'continuous',
            'custom_sound_path' => null,
            'reminder_vibration' => true,
            'priority' => 'high',
            'thumbnail' => null,
            'show_in_drawer' => true,
            'notification_sound' => true,
            'notification_vibration' => true,
            'show_full_screen' => false,
            'deleted_at' => null,
            'created_at' => '2026-08-10 10:43:42',
            'updated_at' => '2026-08-12 10:56:33',
        ]);

        Activity::create([
            'user_id' => $sunil->id,
            'guest_id' => null,
            'title' => '3',
            'description' => null,
            'category' => 'Personal',
            'duration_value' => 48,
            'duration_unit' => 'hours',
            'due_date' => '2026-08-18 00:00:00',
            'is_completed' => false,
            'completed_at' => null,
            'reminder_times' => json_encode([
                [
                    'time' => '17:00',
                    'fixed' => false,
                    'date' => '2026-08-10'
                ]
            ]),
            'frequency_unit' => 'none',
            'frequency_value' => 0,
            'reminder_sound' => 'continuous',
            'custom_sound_path' => null,
            'reminder_vibration' => true,
            'priority' => 'medium',
            'thumbnail' => null,
            'show_in_drawer' => true,
            'notification_sound' => true,
            'notification_vibration' => true,
            'show_full_screen' => false,
            'deleted_at' => null,
            'created_at' => '2026-08-10 10:44:45',
            'updated_at' => '2026-08-11 11:14:47',
        ]);

        Activity::create([
            'user_id' => $sunil->id,
            'guest_id' => null,
            'title' => 'task 4',
            'description' => null,
            'category' => 'Personal',
            'duration_value' => 216,
            'duration_unit' => 'hours',
            'due_date' => '2026-08-29 00:00:00',
            'is_completed' => false,
            'completed_at' => null,
            'reminder_times' => json_encode([
                [
                    'time' => '17:00',
                    'fixed' => false,
                    'date' => '2026-08-11'
                ]
            ]),
            'frequency_unit' => 'none',
            'frequency_value' => 0,
            'reminder_sound' => 'continuous',
            'custom_sound_path' => null,
            'reminder_vibration' => true,
            'priority' => 'high',
            'thumbnail' => null,
            'show_in_drawer' => true,
            'notification_sound' => true,
            'notification_vibration' => true,
            'show_full_screen' => false,
            'deleted_at' => null,
            'created_at' => '2026-08-11 11:11:17',
            'updated_at' => '2026-08-11 11:15:04',
        ]);

        Activity::create([
            'user_id' => $sunil->id,
            'guest_id' => null,
            'title' => 'task 5',
            'description' => null,
            'category' => 'Personal',
            'duration_value' => 144,
            'duration_unit' => 'hours',
            'due_date' => '2026-08-18 00:00:00',
            'is_completed' => false,
            'completed_at' => null,
            'reminder_times' => json_encode([
                [
                    'time' => '17:00',
                    'fixed' => false,
                    'date' => '2026-08-11'
                ]
            ]),
            'frequency_unit' => 'none',
            'frequency_value' => 0,
            'reminder_sound' => 'continuous',
            'custom_sound_path' => null,
            'reminder_vibration' => true,
            'priority' => 'medium',
            'thumbnail' => null,
            'show_in_drawer' => true,
            'notification_sound' => true,
            'notification_vibration' => true,
            'show_full_screen' => false,
            'deleted_at' => null,
            'created_at' => '2026-08-11 11:15:43',
            'updated_at' => '2026-08-11 11:15:43',
        ]);

        Activity::create([
            'user_id' => $sunil->id,
            'guest_id' => null,
            'title' => 'task 6',
            'description' => null,
            'category' => 'Personal',
            'duration_value' => 192,
            'duration_unit' => 'hours',
            'due_date' => '2026-08-18 00:00:00',
            'is_completed' => false,
            'completed_at' => null,
            'reminder_times' => json_encode([
                [
                    'time' => '17:00',
                    'fixed' => false,
                    'date' => '2026-08-11'
                ]
            ]),
            'frequency_unit' => 'none',
            'frequency_value' => 0,
            'reminder_sound' => 'continuous',
            'custom_sound_path' => null,
            'reminder_vibration' => true,
            'priority' => 'low',
            'thumbnail' => null,
            'show_in_drawer' => true,
            'notification_sound' => true,
            'notification_vibration' => true,
            'show_full_screen' => false,
            'deleted_at' => null,
            'created_at' => '2026-08-11 11:16:16',
            'updated_at' => '2026-08-11 11:16:16',
        ]);

        Activity::create([
            'user_id' => $sunil->id,
            'guest_id' => null,
            'title' => 'task 7',
            'description' => null,
            'category' => 'Personal',
            'duration_value' => 192,
            'duration_unit' => 'hours',
            'due_date' => '2026-08-18 00:00:00',
            'is_completed' => false,
            'completed_at' => null,
            'reminder_times' => json_encode([
                [
                    'time' => '17:00',
                    'fixed' => false,
                    'date' => '2026-08-11'
                ]
            ]),
            'frequency_unit' => 'none',
            'frequency_value' => 0,
            'reminder_sound' => 'continuous',
            'custom_sound_path' => null,
            'reminder_vibration' => true,
            'priority' => 'high',
            'thumbnail' => null,
            'show_in_drawer' => true,
            'notification_sound' => true,
            'notification_vibration' => true,
            'show_full_screen' => false,
            'deleted_at' => null,
            'created_at' => '2026-08-11 11:16:50',
            'updated_at' => '2026-08-11 11:16:50',
        ]);

        // Thumbnail/attachments intentionally skipped for activity 25
        Activity::create([
            'user_id' => $sunil->id,
            'guest_id' => null,
            'title' => 'hi',
            'description' => null,
            'category' => 'Personal',
            'duration_value' => 0,
            'duration_unit' => 'none',
            'due_date' => null,
            'is_completed' => false,
            'completed_at' => null,
            'reminder_times' => json_encode([
                [
                    'time' => '17:00',
                    'fixed' => '0',
                    'date' => '2026-08-12'
                ]
            ]),
            'frequency_unit' => 'none',
            'frequency_value' => 0,
            'reminder_sound' => 'continuous',
            'custom_sound_path' => null,
            'reminder_vibration' => true,
            'priority' => 'medium',
            'thumbnail' => null,
            'show_in_drawer' => true,
            'notification_sound' => true,
            'notification_vibration' => true,
            'show_full_screen' => false,
            'deleted_at' => null,
            'created_at' => '2026-08-12 10:51:59',
            'updated_at' => '2026-08-12 10:51:59',
        ]);


        /*
        |--------------------------------------------------------------------------
        | USER 4 - RAJESH
        |--------------------------------------------------------------------------
        */

        Activity::create([
            'user_id' => $rajesh->id,
            'guest_id' => null,
            'title' => 'hhh4517',
            'description' => 'hh',
            'category' => 'Personal',
            'duration_value' => 0,
            'duration_unit' => 'none',
            'due_date' => null,
            'is_completed' => false,
            'completed_at' => null,
            'reminder_times' => json_encode([
                [
                    'time' => '16:45',
                    'fixed' => false,
                    'date' => '2026-08-07'
                ]
            ]),
            'frequency_unit' => 'hours',
            'frequency_value' => 5,
            'reminder_sound' => 'continuous',
            'custom_sound_path' => null,
            'reminder_vibration' => true,
            'priority' => 'medium',
            'thumbnail' => null,
            'show_in_drawer' => true,
            'notification_sound' => true,
            'notification_vibration' => true,
            'show_full_screen' => false,
            'deleted_at' => null,
            'created_at' => '2026-08-07 11:14:30',
            'updated_at' => '2026-08-07 11:46:06',
        ]);
    }
}