<?php

namespace App\Services\Notification;

use App\Models\Saving\SavingGoal;
use App\Services\Notification\NotificationService;

class SavingNotificationService
{
    public function __construct(
        protected NotificationService $notification
    ) {
    }

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


        // 100% - Goal Completed
        if (
            $previousPercentage < 100 &&
            $currentPercentage >= 100
        ) {
            return $this->notification->create(
                $savingGoal->user_id,
                type: 'saving_goal_completed',
                title: 'Saving Goal Completed',
                message: "Congratulations! You have reached your {$savingGoal->name} saving goal.",
                data: [
                    'saving_goal_id' => $savingGoal->id,
                    'saved_amount' => $currentAmount,
                    'target_amount' => $target,
                    'percentage' => 100,
                ]
            );
        }


        // 75% milestone
        if (
            $previousPercentage < 75 &&
            $currentPercentage >= 75
        ) {
            return $this->notification->create(
                $savingGoal->user_id,
                type: 'saving_goal_75',
                title: '75% Saving Goal Reached',
                message: "Great job! You have reached 75% of your {$savingGoal->name} saving goal.",
                data: [
                    'saving_goal_id' => $savingGoal->id,
                    'saved_amount' => $currentAmount,
                    'target_amount' => $target,
                    'percentage' => 75,
                ]
            );
        }


        // 50% milestone
        if (
            $previousPercentage < 50 &&
            $currentPercentage >= 50
        ) {
            return $this->notification->create(
                $savingGoal->user_id,
                type: 'saving_goal_50',
                title: '50% Saving Goal Reached',
                message: "Great progress! You have reached 50% of your {$savingGoal->name} saving goal.",
                data: [
                    'saving_goal_id' => $savingGoal->id,
                    'saved_amount' => $currentAmount,
                    'target_amount' => $target,
                    'percentage' => 50,
                ]
            );
        }


        // 25% milestone
        if (
            $previousPercentage < 25 &&
            $currentPercentage >= 25
        ) {
            return $this->notification->create(
                $savingGoal->user_id,
                type: 'saving_goal_25',
                title: '25% Saving Goal Reached',
                message: "Good start! You have reached 25% of your {$savingGoal->name} saving goal.",
                data: [
                    'saving_goal_id' => $savingGoal->id,
                    'saved_amount' => $currentAmount,
                    'target_amount' => $target,
                    'percentage' => 25,
                ]
            );
        }

        return null;
    }
}