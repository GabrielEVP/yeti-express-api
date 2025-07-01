<?php

namespace App\CompanyBill\Providers;

use App\CompanyBill\Repositories\ICompanyBillRepository;
use App\CompanyBill\Services\CompanyBillService;
use Illuminate\Support\ServiceProvider;

class CompanyBillServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(app_path('CompanyBill/Database/migrations'));
        $this->loadRoutesFrom(app_path('CompanyBill/Routes/CompanyBillRoutes.php'));
    }

    public function register(): void
    {
        $this->app->bind(
            ICompanyBillRepository::class,
            CompanyBillService::class
        );
    }
}

