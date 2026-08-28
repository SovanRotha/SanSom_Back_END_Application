<?php

namespace App\Services\Notification\Notification;

use App\Models\Budget\BudgetCategory;
use App\Models\Notification\Notification;
use App\Services\Notification\NotificationService;

class BudgetNotificationService
{
    public function __construct(
        protected NotificationService $notification
    ) {}

    public function checkBudget(
        BudgetCategory $budgetCategory,
        float $spent
    ): ?Notification {

        $limit = (float) $budgetCategory->limit_amount;

        // No valid budget limit
        if ($limit <= 0) {
            return null;
        }

        // Calculate percentage
        $percentage = ($spent / $limit) * 100;

        // Get alert percentage from budget
        $alertPercentage = (float) $budgetCategory->alert_percentage;

        // Budget has not reached alert percentage
        if ($percentage < $alertPercentage) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate notification
        |--------------------------------------------------------------------------
        */

        $alreadyNotified = Notification::where(
            'user_id',
            $budgetCategory->budget->user_id
        )
            ->where(
                'type',
                'budget_warning'
            )
            ->whereJsonContains(
                'data->budget_category_id',
                $budgetCategory->id
            )
            ->whereJsonContains(
                'data->alert_percentage',
                $alertPercentage
            )
            ->exists();

        if ($alreadyNotified) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Create notification
        |--------------------------------------------------------------------------
        */

        return $this->notification->create(
            $budgetCategory->budget->user_id,

            type: 'budget_warning',

            title: 'Budget Warning',

            message: "You have used " .
                round($percentage, 2) .
                "% of your " .
                $budgetCategory->category->name .
                " budget.",

            data: [
                'budget_id' =>
                $budgetCategory->budget_id,

                'budget_category_id' =>
                $budgetCategory->id,

                'category_id' =>
                $budgetCategory->category_id,

                'spent' =>
                $spent,

                'limit' =>
                $limit,

                'percentage' =>
                round($percentage, 2),

                'alert_percentage' =>
                $alertPercentage,
            ]
        );
    }
}
