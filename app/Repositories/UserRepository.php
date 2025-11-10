<?php

namespace App\Repositories;

class UserRepository extends \Cycle\ORM\Select\Repository
{
    public function withActive(): self
    {
        $repository = clone $this;
        $repository->select->where('status', 1);

        return $repository;
    }
}