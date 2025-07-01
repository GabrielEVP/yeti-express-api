<?php

namespace App\Http\Controllers;

abstract class Controller extends \App\Core\Controllers\Controller
{
    // This class extends the new Controller class to maintain backward compatibility
    // with code that still references the old namespace
}
