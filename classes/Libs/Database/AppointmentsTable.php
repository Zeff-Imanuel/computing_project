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

    // Get all appointments (Admin)
    public function all()
    {
        try {
            $stmt = $this->db->query("
                SELECT a.*, 
                       p.title as property_title,
                       p.price as property_price,
                       p.city as property_city,
                       p.photo as property_photo,
                       u.name as user_name,
                       u.email as user_email,
                       u.phone as user_phone,
                       ag.name as agent_name
                FROM appointments a
                LEFT JOIN properties p ON a.property_id = p.id
                LEFT JOIN users u ON a.user_id = u.id
                LEFT JOIN users ag ON a.agent_id = ag.id
                ORDER BY a.created_at DESC
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get appointments by User
    public function getByUser($user_id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, 
                       p.title as property_title,
                       p.price as property_price,
                       p.city as property_city,
                       p.photo as property_photo,
                       ag.name as agent_name
                FROM appointments a
                LEFT JOIN properties p ON a.property_id = p.id
                LEFT JOIN users ag ON a.agent_id = ag.id
                WHERE a.user_id = :user_id
                ORDER BY a.created_at DESC
            ");
            $stmt->execute([':user_id' => $user_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get appointments by Agent
    public function getByAgent($agent_id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, 
                       p.title as property_title,
                       p.price as property_price,
                       p.city as property_city,
                       p.photo as property_photo,
                       u.name as user_name,
                       u.email as user_email,
                       u.phone as user_phone
                FROM appointments a
                LEFT JOIN properties p ON a.property_id = p.id
                LEFT JOIN users u ON a.user_id = u.id
                WHERE a.agent_id = :agent_id
                ORDER BY a.created_at DESC
            ");
            $stmt->execute([':agent_id' => $agent_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Get appointment by ID
    public function getById($id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, 
                       p.title as property_title,
                       u.name as user_name,
                       u.email as user_email,
                       u.phone as user_phone,
                       ag.name as agent_name
                FROM appointments a
                LEFT JOIN properties p ON a.property_id = p.id
                LEFT JOIN users u ON a.user_id = u.id
                LEFT JOIN users ag ON a.agent_id = ag.id
                WHERE a.id = :id
            ");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }

    // Insert new appointment
    public function insert(array $data)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO appointments 
                (property_id, user_id, agent_id, appointment_date, message, status, created_at)
                VALUES 
                (:property_id, :user_id, :agent_id, :appointment_date, :message, 'pending', NOW())
            ");
            return $stmt->execute([
                ':property_id' => $data['property_id'],
                ':user_id' => $data['user_id'],
                ':agent_id' => $data['agent_id'],
                ':appointment_date' => $data['appointment_date'],
                ':message' => $data['message'] ?? ''
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    // Update status
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
            return $stmt->execute([
                ':id' => $id,
                ':status' => $status
            ]);
        } catch (PDOException $e) {
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

    // Get stats
    public function getStats()
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                FROM appointments
            ");
            $result = $stmt->fetch();
            
            if (!$result || $result->total == 0) {
                return (object) [
                    'total' => 0,
                    'pending' => 0,
                    'confirmed' => 0,
                    'completed' => 0,
                    'cancelled' => 0
                ];
            }
            
            $result->total = (int)$result->total;
            $result->pending = (int)($result->pending ?? 0);
            $result->confirmed = (int)($result->confirmed ?? 0);
            $result->completed = (int)($result->completed ?? 0);
            $result->cancelled = (int)($result->cancelled ?? 0);
            
            return $result;
        } catch (PDOException $e) {
            return (object) [
                'total' => 0,
                'pending' => 0,
                'confirmed' => 0,
                'completed' => 0,
                'cancelled' => 0
            ];
        }
    }

    // Get pending count
    public function getPendingCount()
    {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'");
            return $stmt->fetch()->count ?? 0;
        } catch (PDOException $e) {
            return 0;
        }
    }
}
?>