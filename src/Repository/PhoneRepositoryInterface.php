<?php
declare(strict_types=1);

namespace App\Repository;

use App\Domain\Phone;

interface PhoneRepositoryInterface
{
    /**
     * Add a phone to a contact.
     *
     * @throws \PDOException On database error
     */
    public function add(int $contactId, string $number, ?string $label): Phone;

    /**
     * Get a phone by ID.
     */
    public function get(int $id): ?Phone;

    /**
     * Get all phones for a contact.
     *
     * @return Phone[]
     */
    public function listByContact(int $contactId): array;

    /**
     * Delete a phone.
     */
    public function delete(int $id): void;
}
