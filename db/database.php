<?php
require_once __DIR__ . '/../includes/monitoring.php';

class Database {
    public $db;

    public function __construct() {
        $this->db = new SQLite3(__DIR__ . '/votes.db');
        $this->db->enableExceptions(true);
    }

    public function executeRawSQL($sql) {
        try {
            return $this->db->exec($sql);
        } catch (Exception $e) {
            logError('Database error executing raw SQL: ' . $e->getMessage());
            throw $e;
        }
    }

    public function initializeDatabase() {
        try {
            // Create implementations table
            $this->executeRawSQL('
                CREATE TABLE IF NOT EXISTS implementations (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    description TEXT,
                    llms_txt_url TEXT UNIQUE NOT NULL,
                    logo_url TEXT,
                    has_full INTEGER DEFAULT 0,
                    is_featured INTEGER DEFAULT 0,
                    is_draft INTEGER DEFAULT 0,
                    is_requested INTEGER DEFAULT 0,
                    votes INTEGER DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ');

            // Add sample data if table is empty
            $result = $this->executeQuery('SELECT COUNT(*) as count FROM implementations');
            $count = $result->fetchArray(SQLITE3_ASSOC)['count'];

            if ($count === 0) {
                $sampleData = [
                    [
                        'name' => 'Example Implementation',
                        'description' => 'This is a sample implementation of llms.txt',
                        'llms_txt_url' => 'https://example.com/llms.txt',
                        'logo_url' => '/logos/example.png',
                        'has_full' => 1,
                        'is_featured' => 1,
                        'votes' => 10
                    ],
                    // Add more sample entries as needed
                ];

                foreach ($sampleData as $data) {
                    $this->addImplementation($data);
                }
            }

            return true;
        } catch (Exception $e) {
            logError('Database initialization error: ' . $e->getMessage());
            return false;
        }
    }

    private function executeQuery($query, $params = []) {
        $start = microtime(true);
        try {
            $stmt = $this->db->prepare($query);
            
            foreach ($params as $param => $value) {
                if (is_int($value)) {
                    $stmt->bindValue($param, $value, SQLITE3_INTEGER);
                } else {
                    $stmt->bindValue($param, $value, SQLITE3_TEXT);
                }
            }
            
            $result = $stmt->execute();
            $duration = (microtime(true) - $start) * 1000; // Convert to milliseconds
            logDatabaseQuery($query, $duration);
            
            return $result;
        } catch (Exception $e) {
            logError('Database error: ' . $e->getMessage(), [
                'query' => $query,
                'params' => $params
            ]);
            throw $e;
        }
    }

    public function getImplementations() {
        try {
            $query = '
                SELECT * FROM implementations 
                WHERE is_draft = 0 
                ORDER BY is_featured DESC, name ASC
            ';
            $result = $this->executeQuery($query);
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

            $query = '
                INSERT INTO implementations (
                    name, logo_url, description, llms_txt_url, 
                    has_full, is_featured, is_requested, 
                    is_draft, votes
                ) VALUES (
                    :name, :logo_url, :description, :llms_txt_url,
                    :has_full, :is_featured, :is_requested,
                    :is_draft, :votes
                )
            ';
            $params = [
                ':name' => $data['name'],
                ':logo_url' => $data['logo_url'],
                ':description' => $data['description'],
                ':llms_txt_url' => $data['llms_txt_url'],
                ':has_full' => (int)$data['has_full'],
                ':is_featured' => (int)$data['is_featured'],
                ':is_requested' => (int)$data['is_requested'],
                ':is_draft' => (int)$data['is_draft'],
                ':votes' => (int)$data['votes']
            ];
            return $this->executeQuery($query, $params);
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
            return $this->executeQuery($query, $values);
        } catch (Exception $e) {
            error_log("Failed to update implementation: " . $e->getMessage());
            throw $e;
        }
    }

    public function getFeaturedImplementations() {
        try {
            $query = '
                SELECT * FROM implementations 
                WHERE is_featured = 1 AND is_draft = 0 
                ORDER BY name ASC
            ';
            $result = $this->executeQuery($query);
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
            $query = '
                SELECT * FROM implementations 
                WHERE is_requested = 1 AND is_draft = 0 
                ORDER BY name ASC
            ';
            $result = $this->executeQuery($query);
            return $result->fetchArray(SQLITE3_ASSOC) ? $result : [];
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            return [];
        }
    }

    public function getImplementationById($id) {
        try {
            $query = 'SELECT * FROM implementations WHERE id = :id LIMIT 1';
            $params = [':id' => $id];
            $result = $this->executeQuery($query, $params);
            
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
            $query = 'DELETE FROM votes WHERE implementation_id = :id';
            $params = [':id' => $id];
            $this->executeQuery($query, $params);
            
            // Then delete the implementation
            $query = 'DELETE FROM implementations WHERE id = :id';
            $params = [':id' => $id];
            $this->executeQuery($query, $params);
            
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
        $query = 'SELECT COUNT(*) as count FROM votes WHERE implementation_id = :impl_id AND user_ip = :user_ip';
        $params = [
            ':impl_id' => $implementationId,
            ':user_ip' => $userIp
        ];
        $result = $this->executeQuery($query, $params)->fetchArray();
        
        if ($result['count'] > 0) {
            return ['error' => 'Already voted'];
        }
        
        // Begin transaction
        $this->db->exec('BEGIN');
        
        try {
            // Add vote record
            $query = 'INSERT INTO votes (implementation_id, user_ip) VALUES (:impl_id, :user_ip)';
            $params = [
                ':impl_id' => $implementationId,
                ':user_ip' => $userIp
            ];
            $this->executeQuery($query, $params);
            
            // Update vote count
            $query = 'UPDATE implementations SET votes = votes + 1 WHERE id = :impl_id';
            $params = [':impl_id' => $implementationId];
            $this->executeQuery($query, $params);
            
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
            $params = [':limit' => $limit];
            $result = $this->executeQuery($query, $params);
            
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
            $query = 'SELECT * FROM implementations WHERE llms_txt_url = :url LIMIT 1';
            $params = [':url' => $url];
            $result = $this->executeQuery($query, $params);
            $row = $result->fetchArray(SQLITE3_ASSOC);
            return $row ?: null;
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            return null;
        }
    }

    public function getRecentImplementations($limit = 5) {
        try {
            $query = '
                SELECT * FROM implementations 
                WHERE is_draft = 0 
                ORDER BY created_at DESC 
                LIMIT :limit
            ';
            $params = [':limit' => $limit];
            $result = $this->executeQuery($query, $params);
            return $result->fetchArray(SQLITE3_ASSOC) ? $result : [];
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            return [];
        }
    }
}
