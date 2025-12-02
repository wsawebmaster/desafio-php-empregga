<?php
declare(strict_types=1);

namespace App\Repository;

use App\Domain\Contact;

interface ContactRepositoryInterface
{
    /**
     * Create a new contact with associated phones.
     *
     * @throws \PDOException On database error
     */
    public function create(string $name, string $email, ?string $address): Contact;

    /**
     * Get a contact by ID with all associated phones.
     */
    public function get(int $id): ?Contact;

    /**
     * List contacts with pagination and search.
     *
     * @return Contact[]
     */
    public function list(string $search, int $page, int $perPage): array;

    /**
     * Count total contacts matching search criteria.
     */
    public function count(string $search): int;

    /**
     * Update contact fields.
     *
     * @param array<string, mixed> $data Fields to update (name, email, address)
     */
    public function update(int $id, array $data): ?Contact;

    /**
     * Delete a contact and all associated phones.
     */
    public function delete(int $id): void;
}
