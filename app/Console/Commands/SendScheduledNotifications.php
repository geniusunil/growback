<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\ScheduledNotification;
use App\Mail\TaskNotificationMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendScheduledNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-scheduled-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send scheduled task notifications via email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        
        $notifications = ScheduledNotification::where('is_sent', false)
            ->where('scheduled_time', '<=', $now)
            ->get();

        foreach ($notifications as $notification) {
            try {
                Mail::to($notification->email)->send(new TaskNotificationMail([
                    'task_description' => $notification->task_description,
                    'scheduled_time' => $notification->scheduled_time,
                ]));

                $notification->update(['is_sent' => true]);
                $this->info("Notification sent to {$notification->email} for task: {$notification->task_description}");
            } catch (\Exception $e) {
                $this->error("Failed to send notification to {$notification->email}: " . $e->getMessage());
            }
        }

        if ($notifications->isEmpty()) {
            $this->info("No pending notifications found.");
        }
    }
}
