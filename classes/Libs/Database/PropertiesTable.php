<?php
namespace Libs\Database;

use PDO;
use PDOException;

class PropertiesTable
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
                SELECT p.*, u.name as agent_name, u.email as agent_email
                FROM properties p
                LEFT JOIN users u ON p.user_id = u.id
                ORDER BY p.created_at DESC
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getAllWithAgent()
    {
        try {
            $stmt = $this->db->query("
                SELECT p.*, u.name as agent_name, u.email as agent_email
                FROM properties p
                LEFT JOIN users u ON p.user_id = u.id
                ORDER BY p.created_at DESC
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getByListingType($listing_type)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, u.name as agent_name, u.email as agent_email
                FROM properties p
                LEFT JOIN users u ON p.user_id = u.id
                WHERE p.listing_type = :listing_type 
                AND p.status = 'available'
                ORDER BY p.created_at DESC
            ");
            $stmt->execute(['listing_type' => $listing_type]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getByStatus($status)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, u.name as agent_name
                FROM properties p
                LEFT JOIN users u ON p.user_id = u.id
                WHERE p.status = :status
                ORDER BY p.created_at DESC
            ");
            $stmt->execute([':status' => $status]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getLatest($limit = 12)
    {
        try {
            $sql = "
                SELECT p.*, u.name as agent_name, u.email as agent_email
                FROM properties p
                LEFT JOIN users u ON p.user_id = u.id
                WHERE p.status = 'available'
                ORDER BY p.created_at DESC
                LIMIT " . intval($limit) . "
            ";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getAvailable($limit = null)
    {
        try {
            $sql = "
                SELECT p.*, u.name as agent_name
                FROM properties p
                LEFT JOIN users u ON p.user_id = u.id
                WHERE p.status = 'available'
                ORDER BY p.created_at DESC
            ";
            if ($limit) {
                $sql .= " LIMIT " . intval($limit);
            }
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getFeatured($limit = 6)
    {
        try {
            $sql = "
                SELECT p.*, u.name as agent_name
                FROM properties p
                LEFT JOIN users u ON p.user_id = u.id
                WHERE p.status = 'available' AND p.featured = 1
                ORDER BY p.created_at DESC
                LIMIT " . intval($limit) . "
            ";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getRecent($limit = 5)
    {
        try {
            $sql = "
                SELECT p.*, u.name as agent_name
                FROM properties p
                LEFT JOIN users u ON p.user_id = u.id
                ORDER BY p.created_at DESC
                LIMIT " . intval($limit) . "
            ";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getById($id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, u.name as agent_name, u.email, u.phone
                FROM properties p
                LEFT JOIN users u ON p.user_id = u.id
                WHERE p.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }

    public function getWithPhotos($id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, u.name as agent_name, u.email, u.phone
                FROM properties p
                LEFT JOIN users u ON p.user_id = u.id
                WHERE p.id = ?
            ");
            $stmt->execute([$id]);
            $property = $stmt->fetch();

            if ($property) {
                $photo_table = new PropertyPhotosTable(new MySQL());
                $property->photos = $photo_table->getByProperty($id);
            }

            return $property;
        } catch (PDOException $e) {
            return null;
        }
    }

    // ============================================
    // INSERT / UPDATE / DELETE
    // ============================================

    public function insert(array $data)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO properties (
                    user_id, title, description, price, bedrooms, bathrooms,
                    area_sqft, address, city, state, zip, property_type,
                    listing_type, status, photo, featured, created_at
                ) VALUES (
                    :user_id, :title, :description, :price, :bedrooms, :bathrooms,
                    :area_sqft, :address, :city, :state, :zip, :property_type,
                    :listing_type, :status, :photo, :featured, NOW()
                )
            ");

            $stmt->execute([
                ':user_id'       => $data['user_id'],
                ':title'         => $data['title'],
                ':description'   => $data['description'],
                ':price'         => $data['price'],
                ':bedrooms'      => $data['bedrooms'] ?? 0,
                ':bathrooms'     => $data['bathrooms'] ?? 0,
                ':area_sqft'     => $data['area_sqft'] ?? 0,
                ':address'       => $data['address'],
                ':city'          => $data['city'] ?? '',
                ':state'         => $data['state'] ?? '',
                ':zip'           => $data['zip'] ?? '',
                ':property_type' => $data['property_type'] ?? 'house',
                ':listing_type'  => $data['listing_type'] ?? 'sale',
                ':status'        => $data['status'] ?? 'available',
                ':photo'         => $data['photo'] ?? null,
                ':featured'      => $data['featured'] ?? 0
            ]);

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            echo $e->getMessage();
            exit();
        }
    }

    public function updatePhoto($id, $photo)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE properties SET photo = :photo WHERE id = :id
            ");
            return $stmt->execute([
                ':photo' => $photo,
                ':id'    => $id
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function update($id, array $data)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE properties SET
                    title         = :title,
                    description   = :description,
                    price         = :price,
                    bedrooms      = :bedrooms,
                    bathrooms     = :bathrooms,
                    area_sqft     = :area_sqft,
                    address       = :address,
                    city          = :city,
                    state         = :state,
                    zip           = :zip,
                    property_type = :property_type,
                    listing_type  = :listing_type,
                    status        = :status,
                    updated_at    = NOW()
                WHERE id = :id
            ");
            $data['id'] = $id;
            return $stmt->execute($data);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function updateStatus($id, $status)
    {
        try {
            if (!in_array($status, ['available', 'pending', 'sold', 'rented'])) {
                return false;
            }

            // Prevent sale→rented / rent→sold
            $stmt = $this->db->prepare("SELECT listing_type FROM properties WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $listing_type = $stmt->fetchColumn();

            if ($listing_type === 'sale' && $status === 'rented') return false;
            if ($listing_type === 'rent' && $status === 'sold')   return false;

            // Set sold_at / rented_at only when relevant
            $sold_at   = ($status === 'sold')   ? 'NOW()' : 'NULL';
            $rented_at = ($status === 'rented') ? 'NOW()' : 'NULL';

            $stmt = $this->db->prepare("
                UPDATE properties 
                SET status     = :status,
                    sold_at    = {$sold_at},
                    rented_at  = {$rented_at},
                    updated_at = NOW()
                WHERE id = :id
            ");

            $result = $stmt->execute([
                ":id"     => $id,
                ":status" => $status
            ]);

            return $result && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("updateStatus error: " . $e->getMessage());
            return false;
        }
    }

    public function toggleFeatured($id)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE properties 
                SET featured = IF(featured = 1, 0, 1), updated_at = NOW()
                WHERE id = :id
            ");

            $result = $stmt->execute([":id" => $id]);
            return $result && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("toggleFeatured error: " . $e->getMessage());
            return false;
        }
    }

    public function delete($id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM properties WHERE id = :id");
            return $stmt->execute([":id" => $id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    // ============================================
    // SEARCH
    // ============================================

    public function search($keyword = '', $type = '', $city = '', $listing_type = '', $bedrooms = '', $price_min = '', $price_max = '')
    {
        try {
            $sql = "
                SELECT p.*, u.name as agent_name, u.email as agent_email
                FROM properties p
                LEFT JOIN users u ON p.user_id = u.id
                WHERE p.status = 'available'
            ";
            $params = [];

            if (!empty($keyword)) {
                $sql .= " AND (p.title LIKE :keyword OR p.description LIKE :keyword OR p.city LIKE :keyword)";
                $params['keyword'] = "%$keyword%";
            }
            if (!empty($type)) {
                $sql .= " AND p.property_type = :type";
                $params['type'] = $type;
            }
            if (!empty($city)) {
                $sql .= " AND p.city = :city";
                $params['city'] = $city;
            }
            if (!empty($listing_type)) {
                $sql .= " AND p.listing_type = :listing_type";
                $params['listing_type'] = $listing_type;
            }
            if (!empty($bedrooms)) {
                $sql .= " AND p.bedrooms >= :bedrooms";
                $params['bedrooms'] = $bedrooms;
            }
            if (!empty($price_min)) {
                $sql .= " AND p.price >= :price_min";
                $params['price_min'] = $price_min;
            }
            if (!empty($price_max)) {
                $sql .= " AND p.price <= :price_max";
                $params['price_max'] = $price_max;
            }

            $sql .= " ORDER BY p.created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // ============================================
    // STATS
    // ============================================

    public function getTypeBreakdown()
    {
        try {
            $stmt = $this->db->query("
                SELECT property_type, COUNT(*) as count 
                FROM properties 
                GROUP BY property_type
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getStats()
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                    SUM(CASE WHEN status = 'pending'   THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'sold'      THEN 1 ELSE 0 END) as sold,
                    SUM(CASE WHEN status = 'rented'    THEN 1 ELSE 0 END) as rented,
                    SUM(CASE WHEN listing_type = 'sale' THEN 1 ELSE 0 END) as for_sale,
                    SUM(CASE WHEN listing_type = 'rent' THEN 1 ELSE 0 END) as for_rent
                FROM properties
            ");
            $result = $stmt->fetch();

            if (!$result || $result->total == 0) {
                return (object) [
                    'total' => 0, 'available' => 0, 'pending' => 0,
                    'sold' => 0, 'rented' => 0, 'for_sale' => 0, 'for_rent' => 0
                ];
            }

            foreach (['available','pending','sold','rented','for_sale','for_rent'] as $k) {
                $result->$k = (int)($result->$k ?? 0);
            }
            $result->total = (int)$result->total;

            return $result;
        } catch (PDOException $e) {
            return (object) [
                'total' => 0, 'available' => 0, 'pending' => 0,
                'sold' => 0, 'rented' => 0, 'for_sale' => 0, 'for_rent' => 0
            ];
        }
    }

    public function getTotalViews()
    {
        try {
            $stmt = $this->db->query("SELECT SUM(views) as total FROM properties");
            $result = $stmt->fetch();
            return $result->total ?? 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function getMonthlyListings()
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as count
                FROM properties
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month ASC
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // ============================================
    // MONTHLY SOLD / RENTED (chart data)
    // ============================================

    public function getMonthlySoldRented($months = 12)
    {
        try {
            $months = max(1, intval($months));

            $stmt = $this->db->prepare("
                SELECT
                    DATE_FORMAT(updated_at, '%Y-%m') AS month,
                    SUM(CASE WHEN status = 'sold'   THEN 1 ELSE 0 END) AS sold_count,
                    SUM(CASE WHEN status = 'rented' THEN 1 ELSE 0 END) AS rented_count
                FROM properties
                WHERE status IN ('sold', 'rented')
                  AND updated_at >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                GROUP BY DATE_FORMAT(updated_at, '%Y-%m')
                ORDER BY month ASC
            ");
            $stmt->bindValue(':months', $months, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("getMonthlySoldRented error: " . $e->getMessage());
            return [];
        }
    }

    public function getMonthlySoldRentedByUser($user_id, $months = 12)
    {
        try {
            $months = max(1, intval($months));

            $stmt = $this->db->prepare("
                SELECT
                    DATE_FORMAT(updated_at, '%Y-%m') AS month,
                    SUM(CASE WHEN status = 'sold'   THEN 1 ELSE 0 END) AS sold_count,
                    SUM(CASE WHEN status = 'rented' THEN 1 ELSE 0 END) AS rented_count
                FROM properties
                WHERE user_id = :uid
                  AND status IN ('sold', 'rented')
                  AND updated_at >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                GROUP BY DATE_FORMAT(updated_at, '%Y-%m')
                ORDER BY month ASC
            ");
            $stmt->bindValue(':uid',    $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':months', $months,  PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("getMonthlySoldRentedByUser error: " . $e->getMessage());
            return [];
        }
    }

    // ============================================
    // REVENUE (dollar amounts)
    // ============================================

    public function getRevenueStats()
    {
        try {
            $stmt = $this->db->query("
                SELECT
                    COALESCE(SUM(CASE WHEN status = 'sold'   THEN price ELSE 0 END), 0) AS total_sales,
                    COALESCE(SUM(CASE WHEN status = 'rented' THEN price ELSE 0 END), 0) AS total_rentals,
                    COALESCE(SUM(CASE WHEN status IN ('sold','rented') THEN price ELSE 0 END), 0) AS total_revenue,

                    COALESCE(SUM(CASE WHEN status = 'sold'
                                      AND YEAR(updated_at) = YEAR(CURDATE())
                                 THEN price ELSE 0 END), 0) AS year_sales,
                    COALESCE(SUM(CASE WHEN status = 'rented'
                                      AND YEAR(updated_at) = YEAR(CURDATE())
                                 THEN price ELSE 0 END), 0) AS year_rentals,

                    COALESCE(SUM(CASE WHEN status IN ('sold','rented')
                                      AND YEAR(updated_at)  = YEAR(CURDATE())
                                      AND MONTH(updated_at) = MONTH(CURDATE())
                                 THEN price ELSE 0 END), 0) AS month_revenue
                FROM properties
            ");
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("getRevenueStats error: " . $e->getMessage());
            return (object)[
                'total_sales' => 0, 'total_rentals' => 0, 'total_revenue' => 0,
                'year_sales' => 0, 'year_rentals' => 0, 'month_revenue' => 0
            ];
        }
    }

    public function getRevenueStatsByUser($user_id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    COALESCE(SUM(CASE WHEN status = 'sold'   THEN price ELSE 0 END), 0) AS total_sales,
                    COALESCE(SUM(CASE WHEN status = 'rented' THEN price ELSE 0 END), 0) AS total_rentals,
                    COALESCE(SUM(CASE WHEN status IN ('sold','rented') THEN price ELSE 0 END), 0) AS total_revenue,

                    COALESCE(SUM(CASE WHEN status IN ('sold','rented')
                                      AND YEAR(updated_at) = YEAR(CURDATE())
                                 THEN price ELSE 0 END), 0) AS year_revenue,

                    COALESCE(SUM(CASE WHEN status IN ('sold','rented')
                                      AND YEAR(updated_at)  = YEAR(CURDATE())
                                      AND MONTH(updated_at) = MONTH(CURDATE())
                                 THEN price ELSE 0 END), 0) AS month_revenue
                FROM properties
                WHERE user_id = :uid
            ");
            $stmt->execute([':uid' => $user_id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("getRevenueStatsByUser error: " . $e->getMessage());
            return (object)[
                'total_sales' => 0, 'total_rentals' => 0, 'total_revenue' => 0,
                'year_revenue' => 0, 'month_revenue' => 0
            ];
        }
    }

    public function getMonthlyRevenue($months = 12)
    {
        try {
            $months = max(1, intval($months));
            $stmt = $this->db->prepare("
                SELECT
                    DATE_FORMAT(updated_at, '%Y-%m') AS month,
                    COALESCE(SUM(CASE WHEN status = 'sold'   THEN price ELSE 0 END), 0) AS sales_revenue,
                    COALESCE(SUM(CASE WHEN status = 'rented' THEN price ELSE 0 END), 0) AS rental_revenue,
                    COALESCE(SUM(price), 0) AS total_revenue
                FROM properties
                WHERE status IN ('sold','rented')
                  AND updated_at >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                GROUP BY DATE_FORMAT(updated_at, '%Y-%m')
                ORDER BY month ASC
            ");
            $stmt->bindValue(':months', $months, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("getMonthlyRevenue error: " . $e->getMessage());
            return [];
        }
    }

    public function getMonthlyRevenueByUser($user_id, $months = 12)
    {
        try {
            $months = max(1, intval($months));
            $stmt = $this->db->prepare("
                SELECT
                    DATE_FORMAT(updated_at, '%Y-%m') AS month,
                    COALESCE(SUM(CASE WHEN status = 'sold'   THEN price ELSE 0 END), 0) AS sales_revenue,
                    COALESCE(SUM(CASE WHEN status = 'rented' THEN price ELSE 0 END), 0) AS rental_revenue,
                    COALESCE(SUM(price), 0) AS total_revenue
                FROM properties
                WHERE user_id = :uid
                  AND status IN ('sold','rented')
                  AND updated_at >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                GROUP BY DATE_FORMAT(updated_at, '%Y-%m')
                ORDER BY month ASC
            ");
            $stmt->bindValue(':uid',    $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':months', $months,  PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("getMonthlyRevenueByUser error: " . $e->getMessage());
            return [];
        }
    }
}
?>