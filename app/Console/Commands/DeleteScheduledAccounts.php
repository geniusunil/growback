<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;

class DeleteScheduledAccounts extends Command
{
    protected $signature = 'accounts:delete-scheduled';

    protected $description = 'Permanently delete accounts whose 14-day deletion period has expired';

    public function handle()
    {
        $now = Carbon::now('Asia/Kolkata');

        $dueAt = $now->format('Y-m-d H:i:s');

        $users = User::where('is_deletion_scheduled', 1)
            ->whereNotNull('deletion_due_at')
            ->where('deletion_due_at', '<=', $dueAt)
            ->get();

        if ($users->isEmpty()) {
            $this->info('No accounts are due for deletion.');

            return self::SUCCESS;
        }

        foreach ($users as $user) {

            try {

                $userId = $user->id;

                // Permanently delete user
                $user->delete();

                $this->info(
                    "Account #{$userId} permanently deleted."
                );

            } catch (\Exception $e) {

                $this->error(
                    "Failed to delete account #{$user->id}: {$e->getMessage()}"
                );
            }
        }

        return self::SUCCESS;
    }
}
