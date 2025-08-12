<?php

use App\Debt\Controllers\DebtController;
use App\Debt\Controllers\DebtReportController;

Route::prefix('debts')->group(function () {
    Route::get('all-amount-debts', [DebtController::class, 'getAllDebtsAmount']);
    Route::get('clients-with-debt', [DebtController::class, 'getClientsWithDebt']);
    Route::get('{client}/stats', [DebtController::class, 'getClientStats']);
    Route::get('delivery-with-debts-filter', [DebtController::class, 'filterDeliveryWithDebtByStatusByClient']);
    Route::get('un-paid-debts-report', [DebtReportController::class, 'getUnPaidDebtsReport']);
    Route::get('{id}/debts-report', [DebtReportController::class, 'getClientDebtReport']);
    Route::get('{id}/unpaid-debts-report', [DebtReportController::class, 'getClientUnpaidDebtsReport']);
    Route::get('debts-report', [DebtReportController::class, 'getAllClientsDebtReport']);
});
