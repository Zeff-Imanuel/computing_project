<?php
namespace Libs\Database;

use PDO;
use PDOException;

class PaymentsTable
{
    private PDO $db;

    public function __construct(MySQL $mysql)
    {
        $this->db = $mysql->connect();
    }

    // ============================================
    // GET METHODS
    // ============================================

    public function all()
    {
        try {
            $stmt = $this->db->query("
                SELECT pay.*,
                       p.title AS property_title,
                       p.city  AS property_city,
                       p.listing_type,
                       u.name  AS user_name,
                       u.email AS user_email
                FROM payments pay
                LEFT JOIN properties p ON pay.property_id = p.id
                LEFT JOIN users u      ON pay.user_id     = u.id
                ORDER BY pay.payment_date DESC
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getByUser($user_id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT pay.*, p.title AS property_title, p.city AS property_city
                FROM payments pay
                LEFT JOIN properties p ON pay.property_id = p.id
                WHERE pay.user_id = :uid
                ORDER BY pay.payment_date DESC
            ");
            $stmt->execute([':uid' => $user_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getByProperty($property_id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT pay.*, u.name AS user_name, u.email AS user_email
                FROM payments pay
                LEFT JOIN users u ON pay.user_id = u.id
                WHERE pay.property_id = :pid
                ORDER BY pay.payment_date DESC
            ");
            $stmt->execute([':pid' => $property_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getByStatus($status)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT pay.*, p.title AS property_title, u.name AS user_name
                FROM payments pay
                LEFT JOIN properties p ON pay.property_id = p.id
                LEFT JOIN users u      ON pay.user_id     = u.id
                WHERE pay.status = :status
                ORDER BY pay.payment_date DESC
            ");
            $stmt->execute([':status' => $status]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getById($id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT pay.*,
                       p.title AS property_title,
                       u.name  AS user_name,
                       u.email AS user_email
                FROM payments pay
                LEFT JOIN properties p ON pay.property_id = p.id
                LEFT JOIN users u      ON pay.user_id     = u.id
                WHERE pay.id = :id
            ");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }

    public function getRecent($limit = 5)
    {
        try {
            $sql = "
                SELECT pay.*, p.title AS property_title, u.name AS user_name
                FROM payments pay
                LEFT JOIN properties p ON pay.property_id = p.id
                LEFT JOIN users u      ON pay.user_id     = u.id
                ORDER BY pay.payment_date DESC
                LIMIT " . intval($limit) . "
            ";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // ============================================
    // INSERT / UPDATE / DELETE
    // ============================================

    public function insert(array $data)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO payments (
                    property_id, user_id, amount, payment_method,
                    payment_date, status, notes, created_at
                ) VALUES (
                    :property_id, :user_id, :amount, :payment_method,
                    :payment_date, :status, :notes, NOW()
                )
            ");
            $stmt->execute([
                ':property_id'    => $data['property_id'],
                ':user_id'        => $data['user_id'],
                ':amount'         => $data['amount'],
                ':payment_method' => $data['payment_method'] ?? 'cash',
                ':payment_date'   => $data['payment_date'],
                ':status'         => $data['status'] ?? 'pending',
                ':notes'          => $data['notes'] ?? null,
            ]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("PaymentsTable::insert error: " . $e->getMessage());
            return false;
        }
    }

    public function updateStatus($id, $status)
    {
        try {
            if (!in_array($status, ['pending', 'completed', 'failed', 'refunded'])) {
                return false;
            }
            $stmt = $this->db->prepare("
                UPDATE payments SET status = :status, updated_at = NOW()
                WHERE id = :id
            ");
            $result = $stmt->execute([':id' => $id, ':status' => $status]);
            return $result && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function delete($id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM payments WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    // ============================================
    // STATS
    // ============================================

    public function getStats()
    {
        try {
            $stmt = $this->db->query("
                SELECT
                    COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) AS total_paid,
                    COALESCE(SUM(CASE WHEN status = 'pending'   THEN amount ELSE 0 END), 0) AS total_pending,
                    COALESCE(SUM(CASE WHEN status = 'failed'    THEN amount ELSE 0 END), 0) AS total_failed,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS count_completed,
                    SUM(CASE WHEN status = 'pending'   THEN 1 ELSE 0 END) AS count_pending
                FROM payments
            ");
            $result = $stmt->fetch();
            if (!$result || $result->total == 0) {
                return (object)[
                    'total' => 0, 'total_paid' => 0, 'total_pending' => 0,
                    'total_failed' => 0, 'count_completed' => 0, 'count_pending' => 0
                ];
            }
            return $result;
        } catch (PDOException $e) {
            return (object)[
                'total' => 0, 'total_paid' => 0, 'total_pending' => 0,
                'total_failed' => 0, 'count_completed' => 0, 'count_pending' => 0
            ];
        }
    }

    public function getMonthlyPayments($months = 12)
    {
        try {
            $months = max(1, intval($months));
            $stmt = $this->db->prepare("
                SELECT
                    DATE_FORMAT(payment_date, '%Y-%m') AS month,
                    COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) AS completed_amount,
                    COALESCE(SUM(CASE WHEN status = 'pending'   THEN amount ELSE 0 END), 0) AS pending_amount,
                    COUNT(*) AS count
                FROM payments
                WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
                ORDER BY month ASC
            ");
            $stmt->bindValue(':months', $months, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
}
?>