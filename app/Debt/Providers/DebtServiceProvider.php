<?php

namespace App\Debt\Providers;

use App\Debt\Repositories\IDebtPaymentRepository;
use App\Debt\Repositories\IDebtRepository;
use App\Debt\Repositories\IPDFDebtRepository;
use App\Debt\Services\DebtPaymentService;
use App\Debt\Services\DebtService;
use App\Debt\Services\PDFDebtService;
use Illuminate\Support\ServiceProvider;

class DebtServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(app_path('Debt/database/migrations'));
        $this->loadViewsFrom(app_path('Debt/Resources/views'), 'debt');
        $this->loadRoutesFrom(app_path('Debt/Routes/DebtRoutes.php'));
        $this->loadRoutesFrom(app_path('Debt/Routes/DebtPaymentRoutes.php'));
    }

    public function register(): void
    {
        $this->app->bind(
            IDebtRepository::class,
            DebtService::class
        );

        $this->app->bind(
            IDebtPaymentRepository::class,
            DebtPaymentService::class
        );

        $this->app->bind(
            IPDFDebtRepository::class,
            PDFDebtService::class
        );
    }
}
