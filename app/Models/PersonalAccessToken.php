<?php

namespace App\Models;

use App\Models\Contracts\TokenableInterface;
use Cycle\Annotated\Annotation as Cycle;
use Cycle\Annotated\Annotation\Relation\Morphed\BelongsToMorphed;

#[Cycle\Entity(table: 'personal_access_tokens')]
class PersonalAccessToken
{
    /**
     * @return array<string, mixed>|null
     */
    private static function fetchTokenRow(string $token): ?array
    {
        if (! str_contains($token, '|')) {
            $rows = table('personal_access_tokens')
                ->select()
                ->where('token', hash('sha256', $token))
                ->limit(1)
                ->fetchAll();

            return $rows[0] ?? null;
        }

        [$id, $plain] = explode('|', $token, 2);
        if (! ctype_digit($id) || $plain === '') {
            return null;
        }

        $rows = table('personal_access_tokens')
            ->select()
            ->where('id', (int) $id)
            ->limit(1)
            ->fetchAll();

        $row = $rows[0] ?? null;
        if ($row === null) {
            return null;
        }

        if (! hash_equals((string) ($row['token'] ?? ''), hash('sha256', $plain))) {
            return null;
        }

        return $row;
    }

    private static function rowIsExpired(array $row): bool
    {
        $raw = $row['expires_at'] ?? null;
        if ($raw === null || $raw === '') {
            return false;
        }

        $ts = strtotime((string) $raw);

        return $ts !== false && $ts < time();
    }

    /**
     * Resolves the token row via DBAL {@see table()}, then loads entities with ORM and sets {@see $tokenable}.
     * Avoids Select::with('tokenable') because bundled BelongsToMorphed omits a relation loader in RelationConfig.
     */
    public static function findToken(string $token): ?self
    {
        $row = self::fetchTokenRow($token);
        if ($row === null || self::rowIsExpired($row)) {
            return null;
        }

        $userId = (int) ($row['tokenable_id'] ?? 0);
        if ($userId < 1) {
            return null;
        }

        $user = app('orm')->getRepository(User::class)->findByPK($userId);
        if (! $user instanceof User) {
            return null;
        }

        $entity = app('orm')->getRepository(self::class)->findByPK((int) ($row['id'] ?? 0));
        if (! $entity instanceof self) {
            return null;
        }

        if ($entity->isExpired()) {
            return null;
        }

        $entity->tokenable = $user;

        return $entity;
    }

    #[Cycle\Column(type: 'bigPrimary', unsigned: true)]
    public int $id;

    #[Cycle\Column(type: 'text')]
    public string $name;

    #[Cycle\Column(type: 'string', size: 64)]
    public string $token;

    #[Cycle\Column(type: 'text', nullable: true)]
    public ?string $abilities = null;

    #[Cycle\Column(type: 'timestamp', nullable: true)]
    public ?\DateTimeInterface $lastUsedAt = null;

    #[Cycle\Column(type: 'timestamp', nullable: true)]
    public ?\DateTimeInterface $expiresAt = null;

    #[Cycle\Column(type: 'timestamp', nullable: true)]
    public ?\DateTimeInterface $createdAt = null;

    #[Cycle\Column(type: 'timestamp', nullable: true)]
    public ?\DateTimeInterface $updatedAt = null;

    #[BelongsToMorphed(
        target: TokenableInterface::class,
        nullable: false,
        innerKey: 'tokenable_id',
        outerKey: 'id',
        morphKey: 'tokenable_type',
        morphKeyLength: 255,
        indexCreate: false,
    )]
    public ?TokenableInterface $tokenable = null;

    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return $this->expiresAt <= new \DateTimeImmutable('now');
    }

    /**
     * @return list<string>
     */
    public function getAbilities(): array
    {
        if ($this->abilities === null || $this->abilities === '') {
            return [];
        }

        $decoded = json_decode($this->abilities, true);

        return \is_array($decoded) ? array_values($decoded) : [];
    }

    public function can(string $ability): bool
    {
        $abilities = $this->getAbilities();

        return \in_array('*', $abilities, true)
            || \array_key_exists($ability, array_flip($abilities));
    }

    public function cant(string $ability): bool
    {
        return ! $this->can($ability);
    }
}
