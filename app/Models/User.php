<?php

namespace App\Models;

use Cycle\Annotated\Annotation as Cycle;

#[Cycle\Entity(table: 'users', repository: \App\Repositories\UserPersistRepository::class)]
class User
{
    #[Cycle\Column(type: 'primary')]
    public int $id;

    #[Cycle\Column(type: 'string')]
    public string $name;

    #[Cycle\Column(type: 'smallInteger')]
    public int $status = 0;

    #[Cycle\Column(type: 'string', nullable: true)]
    public ?string $avatar = null;
    
    #[Cycle\Column(type: 'datetime', nullable: true)]
    public ?\DateTimeInterface $createdAt = null;

    #[Cycle\Column(type: 'datetime', nullable: true)]
    public ?\DateTimeInterface $updatedAt = null;
}