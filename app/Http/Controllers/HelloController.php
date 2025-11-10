<?php

namespace App\Http\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use React\Http\Message\Response;
use App\Exceptions\BusinessException;
use App\Models\User;
class HelloController
{
    public function index(Request $request)
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'message' => 'Hello, API!',
            'user' => app('orm')->getRepository(User::class)->findByPK(1),
        ]));
    }

    public function testException()
    {
        throw new BusinessException('Test exception');
    }
}


