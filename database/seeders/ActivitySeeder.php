<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Activity;

class ActivitySeeder extends Seeder
{
    public function run(): void
    {
       
        $sunil = User::where('email', 'geniusunil@gmail.com')->first();
     

      
        /*
        |--------------------------------------------------------------------------
        | USER 3 - SUNIL
        |--------------------------------------------------------------------------
        */

        Activity::create([
            'user_id' => $sunil->id,
            'guest_id' => null,
            'title' => 'task1',
            'description' => null,
            'category' => 'Personal',
            'duration_value' => 144,
            'duration_unit' => 'hours',
            'due_date' => '2026-08-20 00:00:00',
            'is_completed' => false,
            'completed_at' => null,
            // 'reminder_times' => json_encode([
            //     [
            //         'time' => '17:00',
            //         'fixed' => false,
            //         'date' => '2026-08-10'
            //     ]
            // ]),
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
            'title' => 'task2',
            'description' => null,
            'category' => 'Personal',
            'duration_value' => 48,
            'duration_unit' => 'hours',
            'due_date' => '2026-08-16 00:00:00',
            'is_completed' => false,
            'completed_at' => null,
            // 'reminder_times' => json_encode([
            //     [
            //         'time' => '17:00',
            //         'fixed' => false,
            //         'date' => '2026-08-10'
            //     ]
            // ]),
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
            'title' => 'task3',
            'description' => null,
            'category' => 'Personal',
            'duration_value' => 48,
            'duration_unit' => 'hours',
            'due_date' => '2026-08-20 00:00:00',
            'is_completed' => false,
            'completed_at' => null,
            // 'reminder_times' => json_encode([
            //     [
            //         'time' => '17:00',
            //         'fixed' => false,
            //         'date' => '2026-08-10'
            //     ]
            // ]),
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
            'due_date' => '2026-08-31 00:00:00',
            'is_completed' => false,
            'completed_at' => null,
            // 'reminder_times' => json_encode([
            //     [
            //         'time' => '17:00',
            //         'fixed' => false,
            //         'date' => '2026-08-11'
            //     ]
            // ]),
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
            'due_date' => '2026-08-20 00:00:00',
            'is_completed' => false,
            'completed_at' => null,
            // 'reminder_times' => json_encode([
            //     [
            //         'time' => '17:00',
            //         'fixed' => false,
            //         'date' => '2026-08-11'
            //     ]
            // ]),
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
            'due_date' => '2026-08-20 00:00:00',
            'is_completed' => false,
            'completed_at' => null,
            // 'reminder_times' => json_encode([
            //     [
            //         'time' => '17:00',
            //         'fixed' => false,
            //         'date' => '2026-08-11'
            //     ]
            // ]),
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
            'due_date' => '2026-08-20 00:00:00',
            'is_completed' => false,
            'completed_at' => null,
            // 'reminder_times' => json_encode([
            //     [
            //         'time' => '17:00',
            //         'fixed' => false,
            //         'date' => '2026-08-11'
            //     ]
            // ]),
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

    }

}