<?php
declare(strict_types=1);

namespace App\Repository;

use App\Infrastructure\Database;
use App\Domain\Contact;
use PDO;

class ContactRepository implements ContactRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function create(string $name, string $email, ?string $address): Contact
    {
        $now = gmdate('c');
        $stmt = $this->pdo->prepare('INSERT INTO contacts(name,email,address,created_at,updated_at) VALUES(?,?,?,?,?)');
        $stmt->execute([$name, $email, $address, $now, $now]);
        $id = (int)$this->pdo->lastInsertId();
        return $this->get($id) ?? throw new \RuntimeException('Failed to create contact');
    }

    public function get(int $id): ?Contact
    {
        $stmt = $this->pdo->prepare('SELECT * FROM contacts WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->map($row) : null;
    }

    public function list(string $search, int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $sql = 'SELECT * FROM contacts';
        
        if ($search !== '') {
            $sql .= ' WHERE name LIKE ? OR email LIKE ?';
            $like = '%' . $search . '%';
            $params = [$like, $like];
        }
        
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $sql .= $driver === 'sqlite' 
            ? ' ORDER BY name COLLATE NOCASE ASC'
            : ' ORDER BY name ASC';
        
        $sql .= ' LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(fn($r) => $this->map($r), $rows);
    }

    public function count(string $search): int
    {
        $params = [];
        $sql = 'SELECT COUNT(*) AS cnt FROM contacts';
        
        if ($search !== '') {
            $sql .= ' WHERE name LIKE ? OR email LIKE ?';
            $like = '%' . $search . '%';
            $params = [$like, $like];
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)($row['cnt'] ?? 0);
    }

    public function update(int $id, array $data): ?Contact
    {
        $fields = [];
        $params = [];
        
        foreach (['name', 'email', 'address'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (!$fields) {
            return $this->get($id);
        }
        
        $fields[] = 'updated_at = ?';
        $params[] = gmdate('c');
        $params[] = $id;
        
        $sql = 'UPDATE contacts SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $this->get($id);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM contacts WHERE id = ?');
        $stmt->execute([$id]);
    }

    private function map(array $row): Contact
    {
        return new Contact(
            id: (int)$row['id'],
            name: $row['name'],
            email: $row['email'],
            address: $row['address'] ?? null,
            createdAt: $row['created_at'],
            updatedAt: $row['updated_at'],
        );
    }
}
