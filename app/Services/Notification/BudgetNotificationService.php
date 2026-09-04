<?php

namespace App\Services\Notification;

use App\Models\Budget\BudgetCategory;
use App\Models\Notification\Notification;

class BudgetNotificationService
{
    public function __construct(
        protected NotificationService $notification
    ) {
    }

    public function checkBudget(BudgetCategory $budgetCategory, float $spent): ?Notification
    {
        $limit = (float) $budgetCategory->limit_amount;

        if ($limit <= 0) {
            return null;
        }

        $percentage = round(($spent / $limit) * 100, 2);
        $alertPercentage = (float) $budgetCategory->alert_percentage;

        if ($percentage < $alertPercentage) {
            return null;
        }

        $alreadyNotified = Notification::where('user_id', $budgetCategory->budget->user_id)
            ->where('type', 'budget_warning')
            ->whereJsonContains('data->budget_category_id', $budgetCategory->id)
            ->whereJsonContains('data->alert_percentage', $alertPercentage)
            ->exists();

        if ($alreadyNotified) {
            return null;
        }

        return $this->notification->create(
            $budgetCategory->budget->user_id,
            type: 'budget_warning',
            title: 'Budget Warning',
            message: "You have used {$percentage}% of your {$budgetCategory->category->name} budget.",
            data: [
                'budget_id' => $budgetCategory->budget_id,
                'budget_category_id' => $budgetCategory->id,
                'category_id' => $budgetCategory->category_id,
                'spent' => $spent,
                'limit' => $limit,
                'percentage' => $percentage,
                'alert_percentage' => $alertPercentage,
            ]
        );
    }
}