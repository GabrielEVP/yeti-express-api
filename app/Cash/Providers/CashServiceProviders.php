<?php

namespace App\Cash\Providers;

use App\Cash\DomPDF\DomPDFCash;
use App\Cash\Services\CashReportService;
use App\Cash\Services\CashService;
use App\Cash\Services\PDFService;
use App\Cash\Utils\FormatDate;
use Illuminate\Support\ServiceProvider;

class CashServiceProviders extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(app_path('Cash/Routes/CashRoutes.php'));
        $this->loadViewsFrom(app_path('Cash/Resources/views'), 'cash');
    }

    public function register(): void
    {
        $this->app->singleton(FormatDate::class);
        $this->app->singleton(DomPDFCash::class);

        $this->app->singleton(PDFService::class, function ($app) {
            return new PDFService($app->make(DomPDFCash::class));
        });

        $this->app->singleton(CashService::class, function ($app) {
            return new CashService($app->make(FormatDate::class));
        });

        $this->app->singleton(CashReportService::class, function ($app) {
            return new CashReportService(
                $app->make(DomPDFCash::class),
                $app->make(CashService::class)
            );
        });
    }
}
