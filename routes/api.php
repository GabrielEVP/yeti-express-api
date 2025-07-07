<?php

use Illuminate\Support\Facades\Route;

require base_path('app/Auth/Routes/AuthRoutes.php');

Route::middleware("auth:sanctum")->group(function () {
    require base_path('app/Auth/Routes/AuthLoginRoutes.php');
    require base_path('app/Cash/Routes/CashRoutes.php');
    require base_path('app/Client/Routes/ClientRoutes.php');
    require base_path('app/Delivery/Routes/DeliveryRoutes.php');
    require base_path('app/Courier/Routes/CourierRoutes.php');
    require base_path('app/Employee/Routes/EmployeeRoutes.php');
    require base_path('app/Service/Routes/ServiceRoutes.php');
    require base_path('app/CompanyBill/Routes/CompanyBillRoutes.php');
    require base_path('app/Debt/Routes/DebtRoutes.php');
    require base_path('app/Debt/Routes/DebtPaymentRoutes.php');
});
