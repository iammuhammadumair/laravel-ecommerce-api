<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(title: 'Ecommerce API', version: '0.1')]
#[OA\Server(url: 'http://laravel-12.test')]
#[OA\Server(url: 'http://localhost:8000/api')]
#[OA\Server(url: 'http://localhost:8000/api/v1')]
#[OA\Server(url: 'http://localhost:8000/api/v2')]

abstract class Controller
{
    //
}
