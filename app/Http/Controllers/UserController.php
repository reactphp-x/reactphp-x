<?php

namespace App\Http\Controllers;

use App\Models\User;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ServerRequestInterface as Request;
use React\Http\Message\Response;

#[OA\Tag(
    name: 'User',
    description: '用户与个人访问令牌（Personal Access Token）。签发接口仅适用于演示或受信机机场景，生产环境请改为密码登录、OAuth 等流程。',
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum 风格令牌',
    description: '请求头：`Authorization: Bearer {token}`。也支持查询参数 `?token=`（与 `BearerTokenAuthenticator` 一致）。令牌可为 `{id}|{明文}` 或仅明文（存入库的为 SHA-256）。',
)]
class UserController
{
    /**
     * 为已存在用户签发个人访问令牌（演示用；生产请改用登录鉴权）。
     */
    #[OA\Post(
        path: '/api/tokens',
        operationId: 'userIssueToken',
        summary: '签发 API 令牌',
        description: <<<'MD'
根据 `user_id` 创建一条 `personal_access_tokens` 记录并返回**明文令牌**（仅本次响应可见）。

- `name`：令牌展示名，便于在后台区分来源客户端。
- `abilities`：JSON 数组字符串化的能力列表，`["*"]` 表示不限制（与 Laravel Sanctum 语义类似）。
- `expires_at`：可选，ISO 8601 时间；不传则永不过期（由列 `expires_at` 的空值表示）。

**注意**：当前接口未校验调用方身份，仅适合本地/内网演示。
MD,
        requestBody: new OA\RequestBody(
            required: true,
            description: 'JSON 请求体',
            content: new OA\JsonContent(
                required: ['user_id'],
                properties: [
                    new OA\Property(
                        property: 'user_id',
                        description: '已存在于 `users` 表的主键',
                        type: 'integer',
                        minimum: 1,
                        example: 1
                    ),
                    new OA\Property(
                        property: 'name',
                        description: '令牌名称，默认 `api-token`',
                        type: 'string',
                        default: 'api-token',
                        example: 'mobile-app'
                    ),
                    new OA\Property(
                        property: 'abilities',
                        description: '能力列表；含 `"*"` 时视为全部允许',
                        type: 'array',
                        items: new OA\Items(type: 'string'),
                        example: ['*']
                    ),
                    new OA\Property(
                        property: 'expires_at',
                        description: '过期时间（可空）。需能被 PHP `DateTimeImmutable` 解析',
                        type: 'string',
                        format: 'date-time',
                        nullable: true,
                        example: '2026-12-31T23:59:59+08:00'
                    ),
                ]
            )
        ),
        tags: ['User'],
        responses: [
            new OA\Response(
                response: 200,
                description: <<<'MD'
**HTTP 始终 200**，仅用 `code` 区分结果。

- `code === 0`：`data` 含明文令牌等。
- `code === 422`：请求体非法（非 JSON、`user_id` 缺失、`expires_at` 无法解析等），仅含 `msg`。
- `code === 404`：`user_id` 对应用户不存在（或受作用域过滤）。
MD,
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'code', description: '业务码：0 成功；422 参数错误；404 用户不存在', type: 'integer', example: 0),
                        new OA\Property(property: 'msg', description: '`code` 非 0 时的说明；成功时通常省略', type: 'string', nullable: true, example: 'user_id is required'),
                        new OA\Property(
                            property: 'data',
                            description: '仅成功时返回',
                            type: 'object',
                            nullable: true,
                            properties: [
                                new OA\Property(
                                    property: 'token',
                                    description: '明文令牌（`{id}|{secret}` 或哈希前形态），用于 Authorization',
                                    type: 'string',
                                    example: '1|xxxxxxxx'
                                ),
                                new OA\Property(property: 'token_id', description: 'personal_access_tokens 主键', type: 'integer', example: 1),
                                new OA\Property(property: 'name', description: '与该令牌关联的展示名', type: 'string', example: 'mobile-app'),
                            ]
                        ),
                    ]
                )
            ),
        ]
    )]
    public function issue(Request $request)
    {
        $body = json_decode((string) $request->getBody(), true);
        if (! is_array($body)) {
            return $this->jsonBizError(422, 'Invalid JSON body');
        }

        $userId = (int) ($body['user_id'] ?? 0);
        if ($userId < 1) {
            return $this->jsonBizError(422, 'user_id is required');
        }

        $user = app('orm')->getRepository(User::class)->findByPK($userId);
        if (! $user instanceof User) {
            return $this->jsonBizError(404, 'User not found');
        }

        $name = (string) ($body['name'] ?? 'api-token');
        $abilities = $body['abilities'] ?? ['*'];
        if (! is_array($abilities)) {
            $abilities = ['*'];
        }

        $expiresAt = null;
        if (! empty($body['expires_at']) && is_string($body['expires_at'])) {
            try {
                $expiresAt = new \DateTimeImmutable($body['expires_at']);
            } catch (\Exception) {
                return $this->jsonBizError(422, 'expires_at must be a valid date-time');
            }
        }

        $issued = $user->createToken($name, $abilities, $expiresAt);

        return Response::json([
            'code' => 0,
            'data' => [
                'token' => $issued->plainTextToken,
                'token_id' => $issued->accessToken->id,
                'name' => $issued->accessToken->name,
            ],
        ]);
    }

    /**
     * 返回当前请求通过 Bearer / query token 解析到的登录用户（需经过 {@see \App\Http\Middleware\AuthRequiredMiddleware}）。
     */
    #[OA\Get(
        path: '/api/user',
        operationId: 'userMe',
        summary: '当前登录用户',
        description: <<<'MD'
根据合法访问令牌解析 `User`，成功时返回脱敏后的用户字段。

须携带与 `BearerTokenAuthenticator` / `AuthRequiredMiddleware` 一致的凭证：**请求头** `Authorization: Bearer {token}` **或** 查询参数 `token={token}`。

未携带令牌、令牌无效或过期、`tokenable` 非 `User` 时，**HTTP 仍为 200**，`code === 401`（见 `AuthRequiredMiddleware`）；本动作内的兜底同上。
MD,
        security: [['bearerAuth' => []]],
        tags: ['User'],
        responses: [
            new OA\Response(
                response: 200,
                description: <<<'MD'
**HTTP 始终 200**。

- `code === 0`：`data` 为当前用户快照。
- `code === 401`：缺少/无效令牌等（中间件或本方法兜底），仅 `msg`。
MD,
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'code', description: '业务码：0 成功；401 未授权', type: 'integer', example: 0),
                        new OA\Property(property: 'msg', description: '`code` 非 0 时出现', type: 'string', nullable: true, example: 'Unauthorized'),
                        new OA\Property(
                            property: 'data',
                            description: '仅 `code === 0` 时返回',
                            type: 'object',
                            nullable: true,
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'demo'),
                                new OA\Property(property: 'status', description: '用户状态；具体含义见业务定义', type: 'integer', example: 1),
                                new OA\Property(property: 'avatar', type: 'string', nullable: true),
                                new OA\Property(
                                    property: 'created_at',
                                    type: 'string',
                                    format: 'date-time',
                                    nullable: true,
                                    example: '2026-01-01T12:00:00+08:00'
                                ),
                                new OA\Property(
                                    property: 'updated_at',
                                    type: 'string',
                                    format: 'date-time',
                                    nullable: true,
                                    example: '2026-05-01T12:00:00+08:00'
                                ),
                            ]
                        ),
                    ]
                )
            ),
        ]
    )]
    public function me(Request $request)
    {
        $user = $request->getAttribute('user');
        if (! $user instanceof User) {
            return $this->jsonBizError(401, 'Unauthorized');
        }

        return Response::json([
            'code' => 0,
            'data' => $this->userToArray($user),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function userToArray(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'status' => $user->status,
            'avatar' => $user->avatar,
            'created_at' => $user->createdAt?->format(\DateTimeInterface::ATOM),
            'updated_at' => $user->updatedAt?->format(\DateTimeInterface::ATOM),
            'access_token' => $user->currentAccessToken(),
        ];
    }

    /**
     * 业务结果：HTTP 始终 200，用 `code` 表示成功（0）或错误。
     */
    private function jsonBizError(int $bizCode, string $msg): Response
    {
        return Response::json([
            'code' => $bizCode,
            'msg' => $msg,
        ]);
    }
}
