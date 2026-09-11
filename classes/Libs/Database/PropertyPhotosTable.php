<?php
namespace Libs\Database;

use PDO;
use PDOException;

class PropertyPhotosTable
{
    private PDO $db;

    public function __construct(MySQL $mysql)
    {
        $this->db = $mysql->connect();
    }

    // Insert a photo
    public function insert(array $data)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO property_photos (property_id, photo_path, is_primary)
                VALUES (:property_id, :photo_path, :is_primary)
            ");
            $stmt->execute([
                ':property_id' => $data['property_id'],
                ':photo_path' => $data['photo_path'],
                ':is_primary' => $data['is_primary'] ?? 0
            ]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            echo $e->getMessage();
            exit();
        }
    }

    // Get all photos for a property
    public function getByProperty($property_id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM property_photos 
                WHERE property_id = :property_id 
                ORDER BY is_primary DESC, id ASC
            ");
            $stmt->execute([':property_id' => $property_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            echo $e->getMessage();
            exit();
        }
    }

    // Get primary photo for a property
    public function getPrimary($property_id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM property_photos 
                WHERE property_id = :property_id AND is_primary = 1
                LIMIT 1
            ");
            $stmt->execute([':property_id' => $property_id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            echo $e->getMessage();
            exit();
        }
    }

    // Delete a photo
    public function delete($id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM property_photos WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            echo $e->getMessage();
            exit();
        }
    }

    // Delete all photos for a property
    public function deleteByProperty($property_id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM property_photos WHERE property_id = :property_id");
            return $stmt->execute([':property_id' => $property_id]);
        } catch (PDOException $e) {
            echo $e->getMessage();
            exit();
        }
    }

    // Set a photo as primary
    public function setPrimary($id, $property_id)
    {
        try {
            // First, unset all primary for this property
            $stmt = $this->db->prepare("
                UPDATE property_photos SET is_primary = 0 WHERE property_id = :property_id
            ");
            $stmt->execute([':property_id' => $property_id]);

            // Then set the selected one as primary
            $stmt = $this->db->prepare("
                UPDATE property_photos SET is_primary = 1 WHERE id = :id
            ");
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            echo $e->getMessage();
            exit();
        }
    }
}
?>