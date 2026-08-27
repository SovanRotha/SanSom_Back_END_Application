<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Account\AccountType;
use App\Http\Controllers\AI\AIConversationController;
use App\Http\Controllers\AI\AITestController;
use App\Http\Controllers\Bill\BillController;
use App\Http\Controllers\Bill\RecurringTransactionController;
use App\Http\Controllers\Bill\SubscriptionController;
use App\Http\Controllers\Budget\BudgetCategoryController;
use App\Http\Controllers\Budget\BudgetController;
use App\Http\Controllers\Category\CategoryController;
use App\Http\Controllers\Category\TransactionAttachmentController;
use App\Http\Controllers\Category\TransactionController;
use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\Saving\SavingContributionController;
use App\Http\Controllers\Saving\SavingGoalController;
use App\Http\Controllers\User\LoginController;
use App\Http\Controllers\User\RoleController;
use App\Http\Controllers\User\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


Route::apiResource('roles', RoleController::class);

Route::get('/users', [UserController::class, 'index']);
Route::get('/user/{id}', [UserController::class, 'show']);
Route::put('/users/{id}', [ UserController::class, 'update']);
Route::delete('/users/{i,d}', [UserController::class, 'destroy']);


Route::post('/register', [UserController::class, 'store']);
Route::post('/login', [LoginController::class, 'login']);

Route::get('/accounts', [AccountController::class, 'index']);
Route::get('/accounts/{id}', [AccountController::class, 'show']);
Route::post('/accounts', [AccountController::class, 'store']);
Route::put('/accounts/{id}', [AccountController::class, 'update']);
Route::delete('/accounts/{id}', [AccountController::class, 'destroy']);

Route::get('/accountTypes', [AccountType::class, 'index']);
Route::get('/accountTypes/{id}', [AccountType::class, 'show']);
Route::post('/accountTypes', [AccountType::class, 'store']);
Route::put('/accountTypes/{id}', [AccountType::class, 'update']);
Route::delete('/accountTypes/{id}', [AccountType::class, 'destroy']);

Route::get('/bills', [BillController::class, 'index']);
Route::get('/bills/{id}', [BillController::class, 'show']);
Route::post('/bills', [BillController::class, 'store']);
Route::put('/bills/{id}', [BillController::class, 'update']);
Route::delete('/bills/{id}', [BillController::class, 'destroy']);

Route::get('/recurrings', [RecurringTransactionController::class, 'index']);
Route::get('/recurrings/{id}', [RecurringTransactionController::class, 'show']);
Route::post('/recurrings', [RecurringTransactionController::class, 'store']);
Route::put('/recurrings/{id}', [RecurringTransactionController::class, 'update']);
Route::delete('/recurrings/{id}', [RecurringTransactionController::class, 'destroy']);

Route::get('/subscriptions', [SubscriptionController::class, 'index']);
Route::get('/subscriptions/{id}', [SubscriptionController::class, 'show']);
Route::put('/subscriptions/{id}', [SubscriptionController::class, 'update']);
Route::delete('/subscriptions/{id}', [SubscriptionController::class, 'destroy']);
Route::post('/subscriptions', [SubscriptionController::class, 'store']);

Route::get('/budgets', [BudgetController::class, 'index']);
Route::post('/budgets', [BudgetController::class, 'store']);
Route::put('/budgets/{id}', [BudgetController::class, 'update']);
Route::delete('/budgets/{id}', [BudgetController::class, 'destroy']);
Route::get('/budgets/{id}', [BudgetController::class, 'show']);

Route::get('/budgetCategories', [BudgetCategoryController::class, 'index']);
Route::get('/budgetCategories/{id}', [BudgetCategoryController::class, 'show']);
Route::post('/budgetCategories', [BudgetCategoryController::class, 'store']);
Route::put('/budgetCategories/{id}', [BudgetCategoryController::class, 'update']);
Route::delete('/budgetCategories/{id}', [BudgetCategoryController::class, 'destroy']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::post('/categories', [CategoryController::class, 'store']);
Route::put('/categories/{id}', [CategoryController::class, 'update']);
Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

Route::get('/transactions', [TransactionController::class, 'index']);
Route::get('/transactions/{id}', [TransactionController::class, 'show']);
Route::post('/transactions', [TransactionController::class, 'store']);
Route::put('/transactions/{id}', [TransactionController::class, 'update']);
Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);

Route::get('/attachments', [TransactionAttachmentController::class, 'index']);
Route::get('/attachments/{id}', [TransactionAttachmentController::class, 'show']);
Route::post('/attachments', [TransactionAttachmentController::class, 'store']);
// Route::put('/attachments/{id}', [TransactionAttachmentController::class, 'update']);
Route::delete('/attachments/{id}', [TransactionAttachmentController::class, 'destroy']);

Route::get('/goals', [SavingGoalController::class, 'index']);
Route::get('/goals/{id}', [SavingGoalController::class, 'show']);
Route::post('/goals', [SavingGoalController::class, 'store']);
Route::put('/goals/{id}', [SavingGoalController::class, 'update']);
Route::delete('/goals/{id}', [SavingGoalController::class, 'destroy']);

Route::get('/contributions', [SavingContributionController::class, 'index']);
Route::get('/contributions/{id}', [SavingContributionController::class, 'show']);
Route::post('/contributions', [SavingContributionController::class, 'store']);
Route::put('/contributions/{id}', [SavingContributionController::class, 'update']);
Route::delete('/contributions/{id}', [SavingContributionController::class, 'destroy']);

Route::get('/notifications', [NotificationController::class, 'index']);
Route::get('/unread', [NotificationController::class, 'unread']);
Route::get('/notifications/{id}', [NotificationController::class, 'show']);
Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
Route::delete('/notifications', [NotificationController::class, 'destroyAll']);

// have to do service to alert the message into the notification 
// NotificationService

Route::get('/ai/test', [AITestController::class, 'test']);

Route::post('/conversations', [AIConversationController::class, 'createConversation']);
Route::post('/conversation/{id}/messages', [AIConversationController::class, 'sendMessage']);
