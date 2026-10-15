<?php
namespace Libs\Database;

use PDO;
use PDOException;

class AppointmentsTable
{
    private PDO $db;

    public function __construct(MySQL $mysql)
    {
        $this->db = $mysql->connect();
    }

    // ============================================
    // GET METHODS
    // ============================================

    // Get all appointments with property + user + agent info
    public function all()
    {
        try {
            $stmt = $this->db->query("
                SELECT a.*,
                       p.title AS property_title,
                       p.city  AS property_city,
                       p.photo AS property_photo,
                       u.name  AS user_name,
                       u.email AS user_email,
                       ag.name AS agent_name
                FROM appointments a
                LEFT JOIN properties p  ON a.property_id = p.id
                LEFT JOIN users u       ON a.user_id     = u.id
                LEFT JOIN users ag      ON a.agent_id    = ag.id
                ORDER BY a.created_at DESC
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get appointments by user (customer)
    public function getByUser($user_id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*,
                       p.title AS property_title,
                       p.city  AS property_city,
                       p.photo AS property_photo,
                       ag.name AS agent_name
                FROM appointments a
                LEFT JOIN properties p  ON a.property_id = p.id
                LEFT JOIN users ag      ON a.agent_id    = ag.id
                WHERE a.user_id = :user_id
                ORDER BY a.created_at DESC
            ");
            $stmt->execute([':user_id' => $user_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get appointments by agent (owner of the property)
    public function getByAgent($agent_id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*,
                       p.title AS property_title,
                       p.city  AS property_city,
                       p.photo AS property_photo,
                       u.name  AS user_name,
                       u.email AS user_email
                FROM appointments a
                LEFT JOIN properties p  ON a.property_id = p.id
                LEFT JOIN users u       ON a.user_id     = u.id
                WHERE a.agent_id = :agent_id
                ORDER BY a.created_at DESC
            ");
            $stmt->execute([':agent_id' => $agent_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get appointments by property
    public function getByProperty($property_id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*,
                       u.name  AS user_name,
                       u.email AS user_email
                FROM appointments a
                LEFT JOIN users u ON a.user_id = u.id
                WHERE a.property_id = :property_id
                ORDER BY a.created_at DESC
            ");
            $stmt->execute([':property_id' => $property_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get single appointment by ID
    public function getById($id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*,
                       p.title AS property_title,
                       p.city  AS property_city,
                       u.name  AS user_name,
                       ag.name AS agent_name
                FROM appointments a
                LEFT JOIN properties p  ON a.property_id = p.id
                LEFT JOIN users u       ON a.user_id     = u.id
                LEFT JOIN users ag      ON a.agent_id    = ag.id
                WHERE a.id = :id
            ");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }

    // Get recent appointments
    public function getRecent($limit = 5)
    {
        try {
            $sql = "
                SELECT a.*,
                       p.title AS property_title,
                       u.name  AS user_name
                FROM appointments a
                LEFT JOIN properties p  ON a.property_id = p.id
                LEFT JOIN users u       ON a.user_id     = u.id
                ORDER BY a.created_at DESC
                LIMIT " . intval($limit) . "
            ";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get appointments by status
    public function getByStatus($status)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*,
                       p.title AS property_title,
                       u.name  AS user_name
                FROM appointments a
                LEFT JOIN properties p  ON a.property_id = p.id
                LEFT JOIN users u       ON a.user_id     = u.id
                WHERE a.status = :status
                ORDER BY a.created_at DESC
            ");
            $stmt->execute([':status' => $status]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // ============================================
    // INSERT / UPDATE / DELETE
    // ============================================

    // ✅ Book a new appointment — FIXED
    public function insert(array $data)
    {
        try {
            // Validate required fields
            if (empty($data['property_id']) || empty($data['user_id']) || 
                empty($data['agent_id']) || empty($data['appointment_date'])) {
                error_log("AppointmentsTable::insert — missing required fields");
                return false;
            }

            $stmt = $this->db->prepare("
                INSERT INTO appointments (
                    property_id, user_id, agent_id, 
                    appointment_date, message, status, created_at
                ) VALUES (
                    :property_id, :user_id, :agent_id,
                    :appointment_date, :message, :status, NOW()
                )
            ");

            $stmt->execute([
                ':property_id'      => (int)$data['property_id'],
                ':user_id'          => (int)$data['user_id'],
                ':agent_id'         => (int)$data['agent_id'],
                ':appointment_date' => $data['appointment_date'],
                ':message'          => $data['message'] ?? null,
                ':status'           => $data['status'] ?? 'pending'
            ]);

            $id = $this->db->lastInsertId();

            // ✅ Return positive ID, not '0' string or false
            return ((int)$id > 0) ? (int)$id : false;

        } catch (PDOException $e) {
            error_log("AppointmentsTable::insert error: " . $e->getMessage());
            return false;
        }
    }

    // Update appointment status
    public function updateStatus($id, $status)
    {
        try {
            if (!in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'])) {
                return false;
            }

            $stmt = $this->db->prepare("
                UPDATE appointments
                SET status = :status, updated_at = NOW()
                WHERE id = :id
            ");

            $result = $stmt->execute([
                ':id'     => $id,
                ':status' => $status
            ]);

            return $result && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("AppointmentsTable::updateStatus error: " . $e->getMessage());
            return false;
        }
    }

    // Delete appointment
    public function delete($id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM appointments WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    // ============================================
    // STATS
    // ============================================

    // Get stats for dashboard
    public function getStats()
    {
        try {
            $stmt = $this->db->query("
                SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'pending'   THEN 1 ELSE 0 END) AS pending,
                    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN DATE(appointment_date) = CURDATE() THEN 1 ELSE 0 END) AS today
                FROM appointments
            ");
            $result = $stmt->fetch();

            if (!$result || $result->total == 0) {
                return (object) [
                    'total' => 0, 'pending' => 0, 'confirmed' => 0,
                    'cancelled' => 0, 'completed' => 0, 'today' => 0
                ];
            }

            foreach (['pending','confirmed','cancelled','completed','today','total'] as $k) {
                $result->$k = (int)($result->$k ?? 0);
            }
            return $result;
        } catch (PDOException $e) {
            return (object) [
                'total' => 0, 'pending' => 0, 'confirmed' => 0,
                'cancelled' => 0, 'completed' => 0, 'today' => 0
            ];
        }
    }

    // Get pending count (for admin badge)
    public function getPendingCount()
    {
        try {
            $stmt = $this->db->query("
                SELECT COUNT(*) FROM appointments WHERE status = 'pending'
            ");
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    // Get pending count for a specific agent
    public function getPendingCountByAgent($agent_id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM appointments
                WHERE agent_id = :agent_id AND status = 'pending'
            ");
            $stmt->execute([':agent_id' => $agent_id]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    // Get monthly appointment counts (for charts)
    public function getMonthlyAppointments($months = 12)
    {
        try {
            $months = max(1, intval($months));
            $stmt = $this->db->prepare("
                SELECT
                    DATE_FORMAT(created_at, '%Y-%m') AS month,
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed
                FROM appointments
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
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