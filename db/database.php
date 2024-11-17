<?php
class Database {
    public $db;

    public function __construct() {
        try {
            $this->db = new SQLite3(__DIR__ . '/votes.db');
            $this->db->enableExceptions(true);
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw $e;
        }
    }

    public function getImplementations() {
        try {
            $stmt = $this->db->prepare('
                SELECT * FROM implementations 
                WHERE is_draft = 0 
                ORDER BY is_featured DESC, name ASC
            ');
            $result = $stmt->execute();
            $implementations = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $implementations[] = $row;
            }
            return $implementations;
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            return [];
        }
    }

    public function addImplementation($data) {
        try {
            // Check if implementation with this URL already exists
            $existing = $this->getImplementationByUrl($data['llms_txt_url']);
            if ($existing) {
                error_log("Implementation with URL {$data['llms_txt_url']} already exists");
                return false;
            }

            // Set default values for optional fields
            $defaults = [
                'logo_url' => null,
                'description' => null,
                'has_full' => 0,
                'is_featured' => 0,
                'is_requested' => 0,
                'is_draft' => 1,
                'votes' => 0
            ];

            // Merge defaults with provided data
            $data = array_merge($defaults, $data);

            $stmt = $this->db->prepare('
                INSERT INTO implementations (
                    name, logo_url, description, llms_txt_url, 
                    has_full, is_featured, is_requested, 
                    is_draft, votes
                ) VALUES (
                    :name, :logo_url, :description, :llms_txt_url,
                    :has_full, :is_featured, :is_requested,
                    :is_draft, :votes
                )
            ');
            
            $stmt->bindValue(':name', $data['name'], SQLITE3_TEXT);
            $stmt->bindValue(':logo_url', $data['logo_url'], SQLITE3_TEXT);
            $stmt->bindValue(':description', $data['description'], SQLITE3_TEXT);
            $stmt->bindValue(':llms_txt_url', $data['llms_txt_url'], SQLITE3_TEXT);
            $stmt->bindValue(':has_full', (int)$data['has_full'], SQLITE3_INTEGER);
            $stmt->bindValue(':is_featured', (int)$data['is_featured'], SQLITE3_INTEGER);
            $stmt->bindValue(':is_requested', (int)$data['is_requested'], SQLITE3_INTEGER);
            $stmt->bindValue(':is_draft', (int)$data['is_draft'], SQLITE3_INTEGER);
            $stmt->bindValue(':votes', (int)$data['votes'], SQLITE3_INTEGER);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Failed to add implementation: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateImplementation($id, $data) {
        try {
            $fields = [];
            $values = [];
            
            // Only include fields that are actually provided
            $allowedFields = [
                'name', 'logo_url', 'description', 'llms_txt_url',
                'has_full', 'is_featured', 'is_requested', 'is_draft', 'votes'
            ];
            
            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $fields[] = "$key = :$key";
                    if (in_array($key, ['has_full', 'is_featured', 'is_requested', 'is_draft', 'votes'])) {
                        $values[":$key"] = (int)$value;
                    } else {
                        $values[":$key"] = $value;
                    }
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $values[':id'] = $id;
            
            $query = "UPDATE implementations SET " . implode(', ', $fields) . " WHERE id = :id";
            $stmt = $this->db->prepare($query);
            
            foreach ($values as $key => $value) {
                $type = is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT;
                $stmt->bindValue($key, $value, $type);
            }
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Failed to update implementation: " . $e->getMessage());
            throw $e;
        }
    }

    public function getFeaturedImplementations() {
        try {
            $stmt = $this->db->prepare('
                SELECT * FROM implementations 
                WHERE is_featured = 1 AND is_draft = 0 
                ORDER BY name ASC
            ');
            $result = $stmt->execute();
            $implementations = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $implementations[] = $row;
            }
            return $implementations;
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            return [];
        }
    }

    public function getRequestedImplementations() {
        try {
            $stmt = $this->db->prepare('
                SELECT * FROM implementations 
                WHERE is_requested = 1 AND is_draft = 0 
                ORDER BY name ASC
            ');
            $result = $stmt->execute();
            return $result->fetchArray(SQLITE3_ASSOC) ? $result : [];
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            return [];
        }
    }

    public function getImplementationById($id) {
        try {
            $stmt = $this->db->prepare('SELECT * FROM implementations WHERE id = :id LIMIT 1');
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $result = $stmt->execute();
            
            if ($result === false) {
                error_log("Database error: Failed to fetch implementation by ID");
                return null;
            }
            
            return $result->fetchArray(SQLITE3_ASSOC);
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            return null;
        }
    }

    public function deleteImplementation($id) {
        try {
            // Begin transaction
            $this->db->exec('BEGIN');
            
            // Delete votes first
            $stmt = $this->db->prepare('DELETE FROM votes WHERE implementation_id = :id');
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->execute();
            
            // Then delete the implementation
            $stmt = $this->db->prepare('DELETE FROM implementations WHERE id = :id');
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->execute();
            
            // Commit transaction
            $this->db->exec('COMMIT');
            return true;
        } catch (Exception $e) {
            $this->db->exec('ROLLBACK');
            error_log("Failed to delete implementation: " . $e->getMessage());
            return false;
        }
    }

    public function addVote($implementationId) {
        $userIp = $_SERVER['REMOTE_ADDR'];
        
        // Check if user already voted
        $stmt = $this->db->prepare('SELECT COUNT(*) as count FROM votes WHERE implementation_id = :impl_id AND user_ip = :user_ip');
        $stmt->bindValue(':impl_id', $implementationId, SQLITE3_INTEGER);
        $stmt->bindValue(':user_ip', $userIp, SQLITE3_TEXT);
        $result = $stmt->execute()->fetchArray();
        
        if ($result['count'] > 0) {
            return ['error' => 'Already voted'];
        }
        
        // Begin transaction
        $this->db->exec('BEGIN');
        
        try {
            // Add vote record
            $stmt = $this->db->prepare('INSERT INTO votes (implementation_id, user_ip) VALUES (:impl_id, :user_ip)');
            $stmt->bindValue(':impl_id', $implementationId, SQLITE3_INTEGER);
            $stmt->bindValue(':user_ip', $userIp, SQLITE3_TEXT);
            $stmt->execute();
            
            // Update vote count
            $stmt = $this->db->prepare('UPDATE implementations SET votes = votes + 1 WHERE id = :impl_id');
            $stmt->bindValue(':impl_id', $implementationId, SQLITE3_INTEGER);
            $stmt->execute();
            
            $this->db->exec('COMMIT');
            return ['success' => true];
        } catch (Exception $e) {
            $this->db->exec('ROLLBACK');
            return ['error' => $e->getMessage()];
        }
    }

    public function getRecentlyAddedImplementations($limit = 6) {
        try {
            $query = "SELECT * FROM implementations WHERE is_draft = 0 ORDER BY id DESC LIMIT :limit";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
            $result = $stmt->execute();
            
            if ($result === false) {
                error_log("Database error: Failed to fetch recent implementations");
                return [];
            }
            
            $implementations = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $implementations[] = $row;
            }
            
            return $implementations;
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            return [];
        }
    }

    public function getImplementationByUrl($url) {
        try {
            $stmt = $this->db->prepare('SELECT * FROM implementations WHERE llms_txt_url = :url LIMIT 1');
            $stmt->bindValue(':url', $url, SQLITE3_TEXT);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);
            return $row ?: null;
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            return null;
        }
    }

    public function getRecentImplementations($limit = 5) {
        try {
            $stmt = $this->db->prepare('
                SELECT * FROM implementations 
                WHERE is_draft = 0 
                ORDER BY created_at DESC 
                LIMIT :limit
            ');
            $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
            $result = $stmt->execute();
            return $result->fetchArray(SQLITE3_ASSOC) ? $result : [];
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            return [];
        }
    }
}
