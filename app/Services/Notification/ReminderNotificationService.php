<?php

namespace App\Services\Notification;

use App\Models\Saving\SavingGoal;
use Carbon\Carbon;

class ReminderNotificationService
{
    protected const DEADLINE_WARNING_DAYS = 7;

    public function __construct(
        protected NotificationService $notification
    ) {
    }

    /**
     * Check if a saving goal deadline is approaching or overdue,
     * and create a notification if applicable.
     */
    public function checkSavingGoalDeadline(SavingGoal $savingGoal)
    {
        // Goal is already completed — nothing to notify
        if ($savingGoal->current_amount >= $savingGoal->target_amount) {
            return null;
        }

        $targetDate = Carbon::parse($savingGoal->target_date);
        $daysRemaining = Carbon::today()->diffInDays($targetDate, false);

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

        if ($daysRemaining <= self::DEADLINE_WARNING_DAYS) {
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