<?php

namespace App\Services\Notification\Notification;

use App\Models\Saving\SavingGoal;
use App\Services\Notification\NotificationService;

class SavingNotificationService
{
    public function __construct(
        protected NotificationService $notification
    ) {}

    public function checkSaving(
        SavingGoal $savingGoal,
        float $previousAmount,
        float $currentAmount
    ) {
        $target = $savingGoal->target_amount;

        if ($target <= 0) {
            return null;
        }

        $previousPercentage = ($previousAmount / $target) * 100;
        $currentPercentage = ($currentAmount / $target) * 100;

        $milestones = [100, 75, 50, 25];
        foreach ($milestones as $milestone) {
            if ($previousPercentage < $milestone && $currentPercentage >= $milestone) {
                return $this->notification->create(
                    $savingGoal->user_id,
                    type: $milestone === 100 ? 'saving_goal_completed' : "saving_goal_{$milestone}",
                    title: $milestone === 100 ? 'Saving Goal Completed' : "{$milestone}% Saving Goal Reached",
                    message: "You have reached {$milestone}% of your {$savingGoal->name} saving goal.",
                    data: [
                        'saving_goal_id' => $savingGoal->id,
                        'saved_amount' => $currentAmount,
                        'target_amount' => $target,
                        'percentage' => $milestone,
                    ]
                );
            }
        }

        return null;
    }
}