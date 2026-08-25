<?php

namespace App\Repositories\Contracts;

interface MprRequestorRepositoryInterface
{
    /**
     * Find a requestor by email address.
     */
    public function findByEmail(string $email): ?array;

    /**
     * Find a requestor by email OR username.
     */
    public function findByIdentifier(string $identifier): ?array;

    /**
     * Return all requestor rows.
     */
    public function getAll(): array;

    /**
     * Create a new requestor row.
     *
     * Keys expected in $data:
     *  requestorId, email, username, fullName, role, status,
     *  passwordHash, entity, branch, createdBy
     */
    public function create(array $data): void;

    /**
     * Update fields on an existing requestor row by email.
     *
     * Supported keys in $data: passwordHash, fullName, username,
     *  role, status, lastLogin, entity, branch
     */
    public function updateByEmail(string $email, array $data): void;

    /**
     * Soft-delete: set Status to Inactive.
     */
    public function deleteByEmail(string $email): void;

    /**
     * Returns true when the sheet has no data rows.
     */
    public function isEmpty(): bool;

    /**
     * Stamp Last Login timestamp for the given email.
     */
    public function updateLastLogin(string $email): void;

    /**
     * Generate the next Requestor ID in format MPR-REQ-NNN.
     */
    public function generateNextId(): string;
}
