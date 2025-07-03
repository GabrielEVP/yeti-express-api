<?php

use App\Debt\Controllers\DebtController;

Route::prefix('debts')->group(function () {

    Route::get('all-amount-debts', [DebtController::class, 'getAllUnPaidDebtsAmount']);
    Route::get('/clients/with-debt', [DebtController::class, 'clientsWithDebt']);
    Route::get('/clients/{client}/stats', [DebtController::class, 'stats']);
    Route::get('/clients/{client}/delivery-with-debts-filter', [DebtController::class, 'filterDeliveryWithDebtByStatusByClient']);
});

