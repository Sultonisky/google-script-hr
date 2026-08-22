<?php

namespace App\Repositories\Contracts;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?array;

    public function findByIdentifier(string $identifier): ?array;

    public function getAll(): array;

    public function create(array $data): void;

    public function updateByEmail(string $email, array $data): void;

    public function deleteByEmail(string $email): void;

    public function isEmpty(): bool;

    public function updateLastLogin(string $email): void;
}
