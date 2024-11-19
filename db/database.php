<?php
require_once __DIR__ . '/../includes/monitoring.php';

class Database {
    public $db;
    private $dbPath;

    public function __construct() {
        $this->dbPath = __DIR__ . '/directory.db';
        $this->connect();
    }

    private function connect() {
        try {
            $this->db = new SQLite3($this->dbPath);
            $this->db->enableExceptions(true);
        } catch (Exception $e) {
            logError('Failed to connect to database', [
                'path' => $this->dbPath,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function executeQuery($query, $params = []) {
        $start = microtime(true);
        try {
            $stmt = $this->db->prepare($query);
            
            if ($stmt === false) {
                throw new Exception($this->db->lastErrorMsg());
            }
            
            foreach ($params as $param => $value) {
                if (is_int($value)) {
                    $stmt->bindValue($param, $value, SQLITE3_INTEGER);
                } else {
                    $stmt->bindValue($param, $value, SQLITE3_TEXT);
                }
            }
            
            $result = $stmt->execute();
            
            if ($result === false) {
                throw new Exception($this->db->lastErrorMsg());
            }
            
            return $result;
        } finally {
            $duration = (microtime(true) - $start) * 1000; // Convert to milliseconds
            logDatabaseQuery($query, $duration);
        }
    }

    private function fetchAll($query, $params = []) {
        $result = $this->executeQuery($query, $params);
        $rows = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

    private function fetchOne($query, $params = []) {
        $result = $this->executeQuery($query, $params);
        return $result->fetchArray(SQLITE3_ASSOC);
    }

    public function recreateDatabase() {
        try {
            // Close existing connection
            if ($this->db) {
                $this->db->close();
            }

            // Delete existing database file
            if (file_exists($this->dbPath)) {
                unlink($this->dbPath);
            }

            // Reconnect to create new database
            $this->connect();
            return true;
        } catch (Exception $e) {
            logError('Failed to recreate database', [
                'path' => $this->dbPath,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function executeRawSQL($sql) {
        return $this->executeQuery($sql);
    }

    public function initializeDatabase() {
        try {
            $schema = file_get_contents(__DIR__ . '/schema.sql');
            if ($schema === false) {
                throw new Exception('Could not read schema file');
            }

            error_log("Initializing database with schema");
            
            // Split schema into individual statements
            $statements = array_filter(
                array_map('trim', explode(';', $schema)),
                function($sql) { return !empty($sql); }
            );

            // Execute each statement
            foreach ($statements as $sql) {
                $result = $this->db->exec($sql);
                if ($result === false) {
                    throw new Exception($this->db->lastErrorMsg());
                }
            }

            return true;
        } catch (Exception $e) {
            error_log("Failed to initialize database: " . $e->getMessage());
            throw $e;
        }
    }

    public function getImplementations($isAdmin = false) {
        try {
            $query = '
                SELECT * FROM implementations 
                ' . ($isAdmin ? '' : 'WHERE is_draft = 0') . '
                ORDER BY is_featured DESC, name ASC
            ';
            $result = $this->executeQuery($query);
            $implementations = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $implementations[] = $row;
            }
            return $implementations;
        } catch (Exception $e) {
            logError('Failed to get implementations', [
                'error' => $e->getMessage()
            ]);
            return [];
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
            logError('Failed to get featured implementations', [
                'error' => $e->getMessage()
            ]);
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
            logError('Failed to get requested implementations', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function getImplementationById($id) {
        try {
            $query = 'SELECT * FROM implementations WHERE id = :id LIMIT 1';
            $params = [':id' => $id];
            $result = $this->executeQuery($query, $params);
            
            if ($result === false) {
                logError('Database query failed: Failed to fetch implementation', [
                    'id' => $id,
                    'query' => $query
                ]);
                return null;
            }
            
            return $result->fetchArray(SQLITE3_ASSOC);
        } catch (Exception $e) {
            logError('Failed to get implementation by ID', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function deleteImplementation($id) {
        try {
            // Delete the implementation
            $query = 'DELETE FROM implementations WHERE id = :id';
            $params = [':id' => $id];
            $result = $this->executeQuery($query, $params);
            
            // Check if any rows were affected
            if ($this->db->changes() === 0) {
                logError('No implementation found to delete', [
                    'id' => $id
                ]);
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            logError('Failed to delete implementation', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getRecentlyAddedImplementations($limit = 12) {
        try {
            $query = "SELECT * FROM implementations WHERE is_draft = 0 ORDER BY id DESC LIMIT :limit";
            $params = [':limit' => $limit];
            $result = $this->executeQuery($query, $params);
            
            if ($result === false) {
                logError('Database query failed: Failed to fetch recent implementations', [
                    'limit' => $limit,
                    'query' => $query
                ]);
                return [];
            }
            
            $implementations = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $implementations[] = $row;
            }
            
            return $implementations;
        } catch (Exception $e) {
            logError('Failed to get recent implementations', [
                'limit' => $limit,
                'error' => $e->getMessage()
            ]);
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
            logError('Failed to get implementation by URL', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
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
            logError('Failed to get recent implementations', [
                'limit' => $limit,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function isUrlTaken($url, $excludeId = null) {
        try {
            $query = 'SELECT id FROM implementations WHERE llms_txt_url = :url';
            $params = [':url' => $url];
            
            if ($excludeId !== null) {
                $query .= ' AND id != :id';
                $params[':id'] = $excludeId;
            }
            
            $result = $this->executeQuery($query, $params);
            $row = $result->fetchArray(SQLITE3_ASSOC);
            return $row !== false;
        } catch (Exception $e) {
            logError('Failed to check URL existence', [
                'url' => $url,
                'excludeId' => $excludeId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function addImplementation($data) {
        try {
            // Check if URL already exists
            if ($this->isUrlTaken($data['llms_txt_url'])) {
                return false;
            }

            // Set default values for optional fields
            $defaults = [
                'logo_url' => null,
                'description' => null,
                'has_full' => 0,
                'is_featured' => 0,
                'is_requested' => 0,
                'is_draft' => 1
            ];

            // Merge defaults with provided data
            $data = array_merge($defaults, $data);

            $query = '
                INSERT INTO implementations (
                    name, logo_url, description, llms_txt_url, 
                    has_full, is_featured, is_requested, 
                    is_draft
                ) VALUES (
                    :name, :logo_url, :description, :llms_txt_url,
                    :has_full, :is_featured, :is_requested,
                    :is_draft
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
                ':is_draft' => (int)$data['is_draft']
            ];
            return $this->executeQuery($query, $params);
        } catch (Exception $e) {
            logError('Failed to add implementation', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function updateImplementation($id, $data) {
        try {
            // Check if URL is taken by another implementation
            if ($this->isUrlTaken($data['llms_txt_url'], $id)) {
                return false;
            }

            $fields = [];
            $values = [];
            
            // Only include fields that are actually provided
            $allowedFields = [
                'name', 'logo_url', 'description', 'llms_txt_url',
                'has_full', 'is_featured', 'is_requested', 'is_draft'
            ];
            
            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $fields[] = "$key = :$key";
                    if (in_array($key, ['has_full', 'is_featured', 'is_requested', 'is_draft'])) {
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
            logError('Failed to update implementation', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function addSubmission($data) {
        try {
            $query = '
                INSERT INTO submissions (url, email, is_maintainer, ip_address, submitted_at)
                VALUES (:url, :email, :is_maintainer, :ip_address, :submitted_at)
            ';

            error_log("Adding submission: " . json_encode($data));

            $result = $this->executeQuery($query, [
                ':url' => $data['url'],
                ':email' => $data['email'],
                ':is_maintainer' => $data['is_maintainer'] ? 1 : 0,
                ':ip_address' => $data['ip_address'],
                ':submitted_at' => $data['submitted_at']
            ]);

            if ($result === false) {
                throw new Exception($this->db->lastErrorMsg());
            }

            return true;
        } catch (Exception $e) {
            error_log("Database error in addSubmission: " . $e->getMessage());
            throw $e;
        }
    }

    public function getSubmissions() {
        try {
            $query = 'SELECT * FROM submissions ORDER BY submitted_at DESC';
            return $this->fetchAll($query);
        } catch (Exception $e) {
            error_log("Failed to get submissions: " . $e->getMessage());
            return [];
        }
    }

    public function getSubmissionById($id) {
        try {
            $query = 'SELECT * FROM submissions WHERE id = :id';
            return $this->fetchOne($query, [':id' => $id]);
        } catch (Exception $e) {
            error_log("Failed to get submission: " . $e->getMessage());
            return null;
        }
    }

    public function updateSubmissionStatus($id, $status) {
        try {
            $query = 'UPDATE submissions SET status = :status WHERE id = :id';
            return $this->executeQuery($query, [
                ':id' => $id,
                ':status' => $status
            ]);
        } catch (Exception $e) {
            error_log("Failed to update submission status: " . $e->getMessage());
            return false;
        }
    }
}
