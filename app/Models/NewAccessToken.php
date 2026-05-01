<?php

namespace App\Models;

/**
 * Plain token string is only available immediately after {@see User::createToken()}.
 * Mirrors \Laravel\Sanctum\NewAccessToken.
 */
class NewAccessToken
{
    public function __construct(
        public PersonalAccessToken $accessToken,
        public string $plainTextToken,
    ) {
    }

    /**
     * @return array{accessToken: PersonalAccessToken, plainTextToken: string}
     */
    public function toArray(): array
    {
        return [
            'accessToken' => $this->accessToken,
            'plainTextToken' => $this->plainTextToken,
        ];
    }

    public function toJson(int $options = 0): string
    {
        return (string) json_encode($this->toArray(), $options);
    }
}
