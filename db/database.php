<?php
class Database {
    private $db;

    public function __construct() {
        $this->db = new SQLite3(__DIR__ . '/votes.db');
        $this->initDatabase();
    }

    private function initDatabase() {
        try {
            // Create tables if they don't exist
            $schema = file_get_contents(__DIR__ . '/schema.sql');
            $this->db->exec($schema);
        } catch (Exception $e) {
            error_log("Database initialization warning: " . $e->getMessage());
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
            return $result->fetchArray(SQLITE3_ASSOC) ? $result : [];
        } catch (Exception $e) {
            error_log("Database error: Failed to fetch implementations");
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

    public function addImplementation($data) {
        try {
            $stmt = $this->db->prepare('INSERT INTO implementations (
                name, logo_url, description, llms_txt_url, has_full, 
                is_featured, is_requested, votes, is_draft
            ) VALUES (
                :name, :logo_url, :description, :llms_txt_url, :has_full,
                :is_featured, :is_requested, :votes, :is_draft
            )');
            
            $stmt->bindValue(':name', $data['name'], SQLITE3_TEXT);
            $stmt->bindValue(':logo_url', $data['logo_url'], SQLITE3_TEXT);
            $stmt->bindValue(':description', $data['description'], SQLITE3_TEXT);
            $stmt->bindValue(':llms_txt_url', $data['llms_txt_url'], SQLITE3_TEXT);
            $stmt->bindValue(':has_full', $data['has_full'], SQLITE3_INTEGER);
            $stmt->bindValue(':is_featured', $data['is_featured'] ?? 0, SQLITE3_INTEGER);
            $stmt->bindValue(':is_requested', $data['is_requested'], SQLITE3_INTEGER);
            $stmt->bindValue(':votes', $data['votes'] ?? 0, SQLITE3_INTEGER);
            $stmt->bindValue(':is_draft', $data['is_draft'] ?? 1, SQLITE3_INTEGER);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Failed to add implementation: " . $e->getMessage());
            return false;
        }
    }

    public function updateImplementation($id, $data) {
        try {
            $fields = [];
            $values = [];
            
            foreach ($data as $key => $value) {
                if ($key !== 'id') {
                    $fields[] = "$key = :$key";
                    $values[":$key"] = $value;
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
            return false;
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
            
            if ($result === false) {
                error_log("Database error: Failed to fetch implementation by URL");
                return null;
            }
            
            return $result->fetchArray(SQLITE3_ASSOC);
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            return null;
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
            return $result->fetchArray(SQLITE3_ASSOC) ? $result : [];
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            return [];
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
