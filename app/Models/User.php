<?php

namespace App\Models;

use App\Models\Concerns\HasApiTokens;
use App\Models\Contracts\TokenableInterface;
use Cycle\Annotated\Annotation as Cycle;
use Cycle\Annotated\Annotation\Relation\Morphed\MorphedHasMany;
use Cycle\Annotated\Annotation\Relation\Morphed\MorphedHasOne;
use App\Scopes\NotDeletedScope;

#[Cycle\Entity(
    table: 'users', 
    repository: \App\Repositories\UserPersistRepository::class, 
    scope: NotDeletedScope::class,
)]
class User implements TokenableInterface
{
    use HasApiTokens;

    #[Cycle\Column(type: 'primary')]
    public int $id;

    #[Cycle\Column(type: 'string')]
    public string $name;

    #[Cycle\Column(type: 'string')]
    public string $email;

    #[Cycle\Column(type: 'smallInteger')]
    public int $status = 0;

    #[Cycle\Column(type: 'string', nullable: true)]
    public ?string $avatar = null;
    
    #[Cycle\Column(type: 'datetime', nullable: true)]
    public ?\DateTimeInterface $createdAt = null;

    #[Cycle\Column(type: 'datetime', nullable: true)]
    public ?\DateTimeInterface $updatedAt = null;

    #[Cycle\Column(type: 'datetime', nullable: true)]
    public ?\DateTimeInterface $deletedAt = null;

    #[MorphedHasOne(
        target: PersonalAccessToken::class,
        outerKey: 'tokenable_id',
        morphKey: 'tokenable_type',
        morphKeyLength: 255,
        indexCreate: false,
        nullable: true,
    )]
    public ?PersonalAccessToken $personalAccessToken = null;

    #[MorphedHasMany(
        target: PersonalAccessToken::class,
        outerKey: 'tokenable_id',
        morphKey: 'tokenable_type',
        morphKeyLength: 255,
        indexCreate: false,
    )]
    public array $personalAccessTokens = [];
}