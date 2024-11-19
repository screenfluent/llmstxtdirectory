<?php

class ImageOptimizer {
    private const MAX_SIZE = 64;    // Maximum width or height
    private const WEBP_QUALITY = 95; // WebP quality (0-100)
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif'];
    
    private string $uploadDir;
    
    public function __construct(string $uploadDir = 'public/logos') {
        // Convert relative path to absolute if needed
        if (strpos($uploadDir, '/') !== 0) {
            $uploadDir = __DIR__ . '/../' . $uploadDir;
        }
        $this->uploadDir = rtrim($uploadDir, '/');
        
        // Ensure GD is available
        if (!extension_loaded('gd')) {
            throw new RuntimeException('GD extension is required for image processing');
        }
    }
    
    /**
     * Process and optimize an uploaded image
     * 
     * @param array $uploadedFile $_FILES array item
     * @param string $name Implementation name for the filename
     * @return array{success: bool, filename?: string, error?: string}
     */
    public function processUploadedImage(array $uploadedFile, string $name): array {
        error_log("Starting image processing for file: " . ($uploadedFile['name'] ?? 'unknown'));
        
        try {
            // Validate upload
            if (!isset($uploadedFile['tmp_name']) || !is_uploaded_file($uploadedFile['tmp_name'])) {
                error_log("Invalid upload - tmp_name not set or not an uploaded file");
                throw new RuntimeException('Invalid upload');
            }
            
            // Validate mime type
            $mimeType = mime_content_type($uploadedFile['tmp_name']);
            error_log("Detected mime type: " . $mimeType);
            if (!in_array($mimeType, self::ALLOWED_TYPES)) {
                error_log("Invalid file type. Allowed types: JPEG, PNG, GIF");
                throw new RuntimeException('Invalid file type. Allowed types: JPEG, PNG, GIF');
            }
            
            // Ensure upload directory exists and is writable
            error_log("Checking upload directory: " . $this->uploadDir);
            if (!is_dir($this->uploadDir)) {
                error_log("Upload directory does not exist, attempting to create");
                if (!mkdir($this->uploadDir, 0775, true)) {
                    error_log("Failed to create upload directory");
                    throw new RuntimeException('Failed to create upload directory');
                }
                chmod($this->uploadDir, 0775);
            } elseif (!is_writable($this->uploadDir)) {
                error_log("Upload directory is not writable: " . $this->uploadDir);
                chmod($this->uploadDir, 0775);
                if (!is_writable($this->uploadDir)) {
                    throw new RuntimeException('Upload directory is not writable');
                }
            }
            
            // Create image from uploaded file
            $sourceImage = match($mimeType) {
                'image/jpeg' => imagecreatefromjpeg($uploadedFile['tmp_name']),
                'image/png' => imagecreatefrompng($uploadedFile['tmp_name']),
                'image/gif' => imagecreatefromgif($uploadedFile['tmp_name']),
                default => throw new RuntimeException('Unsupported image type')
            };
            error_log("Created image resource from uploaded file");
            
            if (!$sourceImage) {
                error_log("Failed to create image resource");
                throw new RuntimeException('Failed to create image resource');
            }
            
            // Get original dimensions
            $origWidth = imagesx($sourceImage);
            $origHeight = imagesy($sourceImage);
            error_log("Original dimensions: " . $origWidth . "x" . $origHeight);
            
            // Calculate new dimensions while maintaining aspect ratio
            if ($origWidth > $origHeight) {
                $newWidth = min($origWidth, self::MAX_SIZE);
                $newHeight = (int)($origHeight * ($newWidth / $origWidth));
            } else {
                $newHeight = min($origHeight, self::MAX_SIZE);
                $newWidth = (int)($origWidth * ($newHeight / $origHeight));
            }
            error_log("Calculated new dimensions: " . $newWidth . "x" . $newHeight);
            
            // Create new image with calculated dimensions
            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            error_log("Created new image resource with calculated dimensions");
            
            // Enable alpha channel
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
            imagealphablending($newImage, true);
            
            // Resize image with high quality resampling
            imagecopyresampled(
                $newImage, $sourceImage,
                0, 0, 0, 0,
                $newWidth, $newHeight,
                $origWidth, $origHeight
            );
            error_log("Resized image with high quality resampling");
            
            // Generate filename from implementation name
            $safeName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
            $filename = sprintf(
                '%s/%s.webp',
                $this->uploadDir,
                $safeName
            );
            error_log("Generated filename: " . $filename);
            
            // Delete old file if it exists
            if (file_exists($filename)) {
                error_log("Old file exists, attempting to delete");
                if (!is_writable($filename)) {
                    chmod($filename, 0664);
                }
                if (!unlink($filename)) {
                    error_log("Failed to delete existing file: " . $filename);
                    throw new RuntimeException('Failed to delete existing file: ' . $filename);
                }
            }
            
            // Save as WebP
            if (!imagewebp($newImage, $filename, self::WEBP_QUALITY)) {
                error_log("Failed to save WebP image");
                throw new RuntimeException('Failed to save WebP image');
            }
            error_log("Saved WebP image");
            
            // Set proper permissions
            if (!chmod($filename, 0664)) {
                error_log("Failed to set file permissions");
                throw new RuntimeException('Failed to set file permissions');
            }
            error_log("Set file permissions");
            
            // Cleanup
            imagedestroy($sourceImage);
            imagedestroy($newImage);
            error_log("Destroyed image resources");
            
            return [
                'success' => true,
                'filename' => basename($filename)
            ];
            
        } catch (RuntimeException $e) {
            error_log("Error processing upload: " . $e->getMessage());
            error_log("Upload details: " . json_encode([
                'error' => $e->getMessage(),
                'file' => $uploadedFile['name'] ?? 'unknown',
                'upload_dir' => $this->uploadDir,
                'permissions' => [
                    'dir_exists' => is_dir($this->uploadDir),
                    'dir_writable' => is_writable($this->uploadDir),
                    'dir_perms' => decoct(fileperms($this->uploadDir) & 0777)
                ]
            ]));
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function logError($message, $data = []) {
        $timestamp = date('Y-m-d H:i:s');
        error_log("[$timestamp] ImageOptimizer: $message " . json_encode($data));
    }
    
    /**
     * Clean up old images that are no longer referenced
     * 
     * @param array $referencedFiles Array of filenames that are still in use
     * @return int Number of files deleted
     */
    public function cleanupUnusedImages(array $referencedFiles): int {
        $deleted = 0;
        $defaultLogo = 'default.svg';
        
        // Scan directory for all WebP files
        $files = glob($this->uploadDir . '/*.webp');
        
        foreach ($files as $file) {
            $filename = basename($file);
            // Skip if file is referenced or is default logo
            if ($filename === $defaultLogo || in_array($filename, $referencedFiles)) {
                continue;
            }
            
            if (unlink($file)) {
                $deleted++;
            }
        }
        
        return $deleted;
    }
}
