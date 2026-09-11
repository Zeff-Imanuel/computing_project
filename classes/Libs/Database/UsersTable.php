<?php
namespace Libs\Database;

use PDO;
use PDOException;

class UsersTable
{
    private PDO $db;

    public function __construct(MySQL $mysql)
    {
        $this->db = $mysql->connect();
    }

    public function all()
    {
        $result = $this->db->query(
            "SELECT users.*, roles.name AS role
            FROM users LEFT JOIN roles
            ON users.role_id = roles.id"
        );
        return $result->fetchAll();
    }

    public function find($email, $password)
    {
        try {
            $statement = $this->db->prepare("SELECT * FROM users WHERE email = :email");
            $statement->execute([":email" => $email]);
            $user = $statement->fetch();

            if ($user && password_verify($password, $user->password)) {
                return $user;
            }
            return false;
        } catch (PDOException $e) {
            echo $e->getMessage();
            exit();
        }
    }

    public function findByEmail($email)
    {
        try {
            $statement = $this->db->prepare("SELECT * FROM users WHERE email = :email");
            $statement->execute([":email" => $email]);
            return $statement->fetch();
        } catch (PDOException $e) {
            echo $e->getMessage();
            exit();
        }
    }

    public function insert(array $data)
    {
        try {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            $statement = $this->db->prepare(
                "INSERT INTO users (name, email, phone, address, password, role_id, created_at)
                 VALUES (:name, :email, :phone, :address, :password, :role_id, NOW())"
            );
            $statement->execute([
                ":name" => $data['name'],
                ":email" => $data['email'],
                ":phone" => $data['phone'],
                ":address" => $data['address'],
                ":password" => $data['password'],
                ":role_id" => $data['role_id'] ?? 1
            ]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            echo $e->getMessage();
            exit();
        }
    }

    public function getAgents()
    {
        $statement = $this->db->prepare(
            "SELECT * FROM users WHERE role_id = 2 OR role_id = 3 ORDER BY name"
        );
        $statement->execute();
        return $statement->fetchAll();
    }

    public function getStats()
    {
        try {
            $statement = $this->db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN role_id = 1 THEN 1 ELSE 0 END) as users,
                    SUM(CASE WHEN role_id = 2 THEN 1 ELSE 0 END) as agents,
                    SUM(CASE WHEN role_id = 3 THEN 1 ELSE 0 END) as admins,
                    SUM(CASE WHEN suspended = 1 THEN 1 ELSE 0 END) as suspended
                FROM users
            ");
            return $statement->fetch();
        } catch (PDOException $e) {
            echo $e->getMessage();
            exit();
        }
    }

    public function updatePhoto(string $id, string $photo)
    {
        $statement = $this->db->prepare(
            "UPDATE users SET photo = :photo WHERE id = :id"
        );
        $statement->execute([
            ":photo" => $photo,
            ":id" => $id
        ]);
        return $statement->rowCount();
    }

    public function changeRole(string $id, string $role_id)
    {
        $statement = $this->db->prepare(
            "UPDATE users SET role_id = :role_id WHERE id = :id"
        );
        $statement->execute([
            ":role_id" => $role_id,
            ":id" => $id
        ]);
        return $statement->rowCount();
    }

    public function suspend(string $id)
    {
        $statement = $this->db->prepare("UPDATE users SET suspended = 1 WHERE id = :id");
        $statement->execute([":id" => $id]);
        return $statement->rowCount();
    }

    public function unsuspend(string $id)
    {
        $statement = $this->db->prepare("UPDATE users SET suspended = 0 WHERE id = :id");
        $statement->execute([":id" => $id]);
        return $statement->rowCount();
    }

    public function delete(string $id)
    {
        $statement = $this->db->prepare("DELETE FROM users WHERE id = :id");
        $statement->execute([":id" => $id]);
        return $statement->rowCount();
    }
   public function getPaginated($limit, $offset)
{
    try {
        $statement = $this->db->prepare("
            SELECT users.*, roles.name AS role
            FROM users  
            LEFT JOIN roles ON users.role_id = roles.id
            ORDER BY users.id DESC
            LIMIT :limit OFFSET :offset
        ");
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    } catch (PDOException $e) {
        echo $e->getMessage();
        exit();
    }
}

// Get recent users
public function getRecent($limit = 5)
{
    try {
        $sql = "
            SELECT users.*, roles.name AS role
            FROM users 
            LEFT JOIN roles ON users.role_id = roles.id
            ORDER BY users.created_at DESC
            LIMIT " . intval($limit) . "
        ";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

// Get monthly registrations
public function getMonthlyRegistrations()
{
    try {
        $stmt = $this->db->query("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as count
            FROM users
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}



}
?>