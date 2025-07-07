<?php

namespace App\CompanyBill\Models;
enum Method: string
{
    case CASH = 'cash';
    case MOBILE_PAYMENT = 'mobile_payment';
    case BANK_TRANSFERED = 'bank_transfered';
}
