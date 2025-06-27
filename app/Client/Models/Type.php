<?php

namespace App\Client\Models;


enum Type: string
{
    case VENEZOLANO = 'venezolano';
    case FOREIGN = 'foreing';
    case LEGAL = 'legal';
    case COMMUNE = 'commune';
    case GOVERNMENT = 'government';
    case PASAPORT = 'pasaport';
    case PERSONAL_SIGNATURE = 'personal_signature';
}
