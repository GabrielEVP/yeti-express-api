<?php
use App\Http\Controllers\Api\DebtController;

Route::prefix('debts')->group(function () {

        Route::get('all-amout-debts', [DebtController::class, 'getAllUnPaidDebtsAmount']);

    Route::get('/clients/with-debt', [DebtController::class, 'clientsWithDebt']);
    Route::get('/clients/{client}/stats', [DebtController::class, 'stats']);
    Route::get('/clients/{client}/delivery-with-debts', [DebtController::class, 'loadDeliveryWithDebtByClient']);
    Route::get('/clients/{client}/delivery-with-debts-filter', [DebtController::class, 'filterDeliveryWithDebtByStatusByClient']);
});

