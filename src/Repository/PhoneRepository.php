<?php
declare(strict_types=1);

namespace App\Repository;

use App\Infrastructure\Database;
use App\Domain\Phone;
use PDO;

class PhoneRepository implements PhoneRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function add(int $contactId, string $number, ?string $label): Phone
    {
        $stmt = $this->pdo->prepare('INSERT INTO phones(contact_id, number, label) VALUES(?,?,?)');
        $stmt->execute([$contactId, $number, $label]);
        $id = (int)$this->pdo->lastInsertId();
        return $this->get($id) ?? throw new \RuntimeException('Failed to create phone');
    }

    public function get(int $id): ?Phone
    {
        $stmt = $this->pdo->prepare('SELECT * FROM phones WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->map($row) : null;
    }

    public function listByContact(int $contactId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM phones WHERE contact_id = ? ORDER BY id ASC');
        $stmt->execute([$contactId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($r) => $this->map($r), $rows);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM phones WHERE id = ?');
        $stmt->execute([$id]);
    }

    private function map(array $row): Phone
    {
        return new Phone(
            id: (int)$row['id'],
            contactId: (int)$row['contact_id'],
            number: $row['number'],
            label: $row['label'] ?? null,
        );
    }
}
