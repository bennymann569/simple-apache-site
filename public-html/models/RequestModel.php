<?php

require_once __DIR__ . '/../database.php';

class RequestModel
{
    public static function findById(string $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM requests WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $request = $stmt->fetch();
        return $request ?: null;
    }

    public static function create(array $data): bool
    {
        $stmt = Database::connect()->prepare(
            'INSERT INTO requests (id, timestamp, name, email, phone, service, details, status, updated_at, quote_amount, payment_status, payment_id) VALUES (:id, :timestamp, :name, :email, :phone, :service, :details, :status, :updated_at, :quote_amount, :payment_status, :payment_id)'
        );

        return $stmt->execute([
            'id' => $data['id'],
            'timestamp' => $data['timestamp'],
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'service' => $data['service'],
            'details' => $data['details'],
            'status' => $data['status'],
            'updated_at' => $data['updated_at'],
            'quote_amount' => $data['quote_amount'] ?? null,
            'payment_status' => $data['payment_status'] ?? 'Pending Quote',
            'payment_id' => $data['payment_id'] ?? null,
        ]);
    }

    public static function updateStatus(string $id, string $status): bool
    {
        $stmt = Database::connect()->prepare('UPDATE requests SET status = :status, updated_at = :updated_at WHERE id = :id');
        return $stmt->execute([
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
            'id' => $id,
        ]);
    }

    public static function setQuoteAmount(string $id, float $amount): bool
    {
        $stmt = Database::connect()->prepare('UPDATE requests SET quote_amount = :quote_amount, payment_status = :payment_status, updated_at = :updated_at WHERE id = :id');
        return $stmt->execute([
            'quote_amount' => number_format($amount, 2, '.', ''),
            'payment_status' => 'Awaiting Payment',
            'updated_at' => date('Y-m-d H:i:s'),
            'id' => $id,
        ]);
    }

    public static function markPaid(string $id, string $paymentId): bool
    {
        $stmt = Database::connect()->prepare('UPDATE requests SET payment_status = :payment_status, payment_id = :payment_id, updated_at = :updated_at WHERE id = :id');
        return $stmt->execute([
            'payment_status' => 'Completed',
            'payment_id' => $paymentId,
            'updated_at' => date('Y-m-d H:i:s'),
            'id' => $id,
        ]);
    }

    public static function all(array $filters = []): array
    {
        $sql = 'SELECT * FROM requests';
        $conditions = [];
        $params = [];

        if (!empty($filters['service']) && $filters['service'] !== 'All') {
            $conditions[] = 'service = :service';
            $params['service'] = $filters['service'];
        }

        if (!empty($filters['status']) && $filters['status'] !== 'All') {
            $conditions[] = 'status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['query'])) {
            $conditions[] = '(name LIKE :query OR email LIKE :query OR phone LIKE :query OR service LIKE :query OR details LIKE :query)';
            $params['query'] = '%' . $filters['query'] . '%';
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY timestamp DESC';
        $stmt = Database::connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
