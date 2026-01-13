<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Management Toko API',
    description: 'API Documentation for Management Toko App',
    contact: new OA\Contact(email: 'admin@management-toko.com')
)]
#[OA\Server(
    url: 'http://localhost:8000/api/v1',
    description: 'Local API Server'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer'
)]
class SwaggerConfig {}
