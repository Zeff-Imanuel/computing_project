<?php
namespace Libs\Database;

use PDO;
use PDOException;

class MessagesTable
{
    private PDO $db;

    public function __construct(MySQL $mysql)
    {
        $this->db = $mysql->connect();
    }

    public function insert(array $data)
    {
        try {
            $statement = $this->db->prepare("
                INSERT INTO messages (name, email, subject, message, created_at)
                VALUES (:name, :email, :subject, :message, :created_at)
            ");
            return $statement->execute($data);
        } catch (PDOException $e) {
            echo $e->getMessage();
            exit();
        }
    }

    public function all()
    {
        try {
            $statement = $this->db->query("
                SELECT * FROM messages 
                ORDER BY is_read ASC, created_at DESC
            ");
            return $statement->fetchAll();
        } catch (PDOException $e) {
            echo $e->getMessage();
            exit();
        }
    }

    public function markAsRead($id)
    {
        try {
            $statement = $this->db->prepare("
                UPDATE messages SET is_read = 1 WHERE id = :id
            ");
            return $statement->execute(['id' => $id]);
        } catch (PDOException $e) {
            echo $e->getMessage();
            exit();
        }
    }

    public function delete($id)
    {
        try {
            $statement = $this->db->prepare("
                DELETE FROM messages WHERE id = :id
            ");
            return $statement->execute(['id' => $id]);
        } catch (PDOException $e) {
            echo $e->getMessage();
            exit();
        }
    }

    public function getUnreadCount()
    {
        try {
            $statement = $this->db->query("
                SELECT COUNT(*) as count FROM messages WHERE is_read = 0
            ");
            return $statement->fetch()->count;
        } catch (PDOException $e) {
            echo $e->getMessage();
            exit();
        }
    }
    // Get stats
public function getStats()
{
    try {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
                SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as read_count
            FROM messages
        ");
        return $stmt->fetch();
    } catch (PDOException $e) {
        return (object) ['total' => 0, 'unread' => 0, 'read_count' => 0];
    }
}

// Get recent messages
public function getRecent($limit = 5)
{
    try {
        $sql = "
            SELECT * FROM messages 
            ORDER BY created_at DESC
            LIMIT " . intval($limit) . "
        ";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}
}
?>