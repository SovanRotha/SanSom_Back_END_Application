<?php

namespace App\Services\Notification\Notification;

use App\Models\Saving\SavingGoal;
use App\Services\Notification\NotificationService;
use Carbon\Carbon;

class ReminderNotificationService
{
    public function __construct(
        protected NotificationService $notification
    ) {
    }

    /**
     * Check if a saving goal deadline is approaching.
     */
    public function checkSavingGoalDeadline(
        SavingGoal $savingGoal
    ) {
        $targetDate = Carbon::parse($savingGoal->target_date);

        $today = Carbon::today();

        $daysRemaining = $today->diffInDays($targetDate, false);

        // Goal is already completed
        if ($savingGoal->current_amount >= $savingGoal->target_amount) {
            return null;
        }

        // Goal is overdue
        if ($daysRemaining < 0) {
            return $this->notification->create(
                $savingGoal->user_id,
                type: 'saving_goal_overdue',
                title: 'Saving Goal Overdue',
                message: "Your {$savingGoal->name} saving goal has passed its deadline.",
                data: [
                    'saving_goal_id' => $savingGoal->id,
                    'target_date' => $savingGoal->target_date,
                    'days_overdue' => abs($daysRemaining),
                ]
            );
        }

        // 7 days remaining
        if ($daysRemaining <= 7) {
            return $this->notification->create(
                $savingGoal->user_id,
                type: 'saving_goal_deadline',
                title: 'Saving Goal Deadline Approaching',
                message: "Your {$savingGoal->name} saving goal deadline is in {$daysRemaining} days.",
                data: [
                    'saving_goal_id' => $savingGoal->id,
                    'target_date' => $savingGoal->target_date,
                    'days_remaining' => $daysRemaining,
                ]
            );
        }

        return null;
    }
}