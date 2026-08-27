<?php

use App\Models\Budget\Budget;
use App\Services\Notification\NotificationService;


class BudgetNotificationService
{
    public function __construct(protected NotificationService $notification)
    {}

    // public function warning(Budget $budget, float $percentage)
    // {
    //     return $this->notification->create(
    //         $budget->user_id,
    //         type : 'Budget_Warning',
    //         title  : 'Budget Warning',
    //         message : `You have use {$percentage}% of your {$budget->name} budgets.`,
    //         data : [
    //             'budget_id' => $budget->id,
    //             'percentage' => $percentage,
    //         ]
    //     );
    // }

    
}