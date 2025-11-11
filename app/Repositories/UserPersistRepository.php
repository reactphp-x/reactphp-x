<?php

namespace App\Repositories;


use Cycle\ORM\Select;
use Cycle\ORM\EntityManager;
use Cycle\ORM\ORMInterface;
use App\Models\User;

class UserPersistRepository extends Select\Repository
{
    private EntityManager $entityManager;

    public function __construct(Select $select, ORMInterface $orm)
    {
        parent::__construct($select);
        $this->entityManager = new EntityManager($orm);
    }


    public function withActive(): self
    {
        $repository = clone $this;
        $repository->select->where('status', 1);

        return $repository;
    }

    public function save(User $user, bool $cascade = true)
    {
        $this->entityManager->persist(
            $user,
            $cascade
        );

        // entity manager is clean after run
        $this->entityManager->run();
    }
}
