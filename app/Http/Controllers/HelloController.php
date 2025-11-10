<?php

namespace App\Http\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use React\Http\Message\Response;
use App\Exceptions\BusinessException;

class HelloController
{
    public function index(Request $request)
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'message' => 'Hello, API!',
        ]));
    }

    public function testException()
    {
        throw new BusinessException('Test exception');
    }
}


