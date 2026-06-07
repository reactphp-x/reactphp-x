<?php

namespace App\Http\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use React\Http\Message\Response;
use App\Exceptions\BusinessException;
use App\Models\User;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Hello', description: 'Hello API endpoints')]
class HelloController
{
    #[OA\Get(
        path: '/',
        summary: 'Get hello message',
        description: 'Returns a hello message with user information',
        operationId: 'helloIndex',
        tags: ['Hello'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Hello, API!'),
                        new OA\Property(property: 'user', type: 'object', nullable: true, description: 'User object')
                    ]
                )
            )
        ]
    )]
    public function index(Request $request)
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'message' => 'Hello, API!',
            'user' => repo(User::class)->findByPK(1),
        ]));
    }

    #[OA\Get(
        path: '/test-exception',
        summary: 'Test exception handling',
        description: 'Throws a test exception to verify exception handling',
        operationId: 'testException',
        tags: ['Hello'],
        responses: [
            new OA\Response(
                response: 500,
                description: 'Business exception thrown',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Test exception')
                    ]
                )
            )
        ]
    )]
    public function testException()
    {
        throw new BusinessException('Test exception');
    }
}


