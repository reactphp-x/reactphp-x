<?php

namespace App\Models\Concerns;

use App\Models\NewAccessToken;
use App\Models\PersonalAccessToken;
use App\Models\Contracts\TokenableInterface;
use Cycle\ORM\EntityManager;
use DateTimeInterface;

/**
 * API token helpers aligned with Laravel Sanctum's {@see \Laravel\Sanctum\HasApiTokens}.
 */
trait HasApiTokens
{
    protected ?PersonalAccessToken $accessToken = null;

    public function tokenCan(string $ability): bool
    {
        return $this->accessToken !== null && $this->accessToken->can($ability);
    }

    public function tokenCant(string $ability): bool
    {
        return ! $this->tokenCan($ability);
    }

    /**
     * @param  list<string>|array<string, mixed>  $abilities
     */
    public function createToken(string $name, array $abilities = ['*'], ?DateTimeInterface $expiresAt = null): NewAccessToken
    {
        $plainTextToken = $this->generateTokenString();

        $token = new PersonalAccessToken();
        $token->name = $name;
        $token->token = hash('sha256', $plainTextToken);
        $token->abilities = json_encode(array_values($abilities), JSON_THROW_ON_ERROR);
        $token->expiresAt = $expiresAt;

        if (! $this instanceof TokenableInterface) {
            throw new \LogicException('HasApiTokens must be used on a class implementing TokenableInterface.');
        }
        $token->tokenable = $this;

        $now = new \DateTimeImmutable('now');
        $token->createdAt = $now;
        $token->updatedAt = $now;

        $orm = app('orm');
        $em = new EntityManager($orm);
        $em->persist($token, true);
        $em->run();

        return new NewAccessToken($token, $token->id . '|' . $plainTextToken);
    }

    public function generateTokenString(): string
    {
        $tokenEntropy = self::randomAlphaNumeric(40);

        return sprintf(
            '%s%s%s',
            (string) config('sanctum.token_prefix', ''),
            $tokenEntropy,
            hash('crc32b', $tokenEntropy)
        );
    }

    public function currentAccessToken(): ?PersonalAccessToken
    {
        return $this->accessToken;
    }

    public function withAccessToken(?PersonalAccessToken $accessToken): static
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    private static function randomAlphaNumeric(int $length): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $max = \strlen($chars) - 1;
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $chars[random_int(0, $max)];
        }

        return $out;
    }
}
