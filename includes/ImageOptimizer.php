<?php

class ImageOptimizer {
    private const MAX_WIDTH = 400;  // Maximum width for logos
    private const MAX_HEIGHT = 200; // Maximum height for logos
    private const WEBP_QUALITY = 85; // WebP quality (0-100)
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif'];
    
    private string $uploadDir;
    
    public function __construct(string $uploadDir = 'public/logos') {
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
     * @return array{success: bool, filename?: string, error?: string}
     */
    public function processUploadedImage(array $uploadedFile): array {
        try {
            // Validate upload
            if (!isset($uploadedFile['tmp_name']) || !is_uploaded_file($uploadedFile['tmp_name'])) {
                throw new RuntimeException('Invalid upload');
            }
            
            // Validate mime type
            $mimeType = mime_content_type($uploadedFile['tmp_name']);
            if (!in_array($mimeType, self::ALLOWED_TYPES)) {
                throw new RuntimeException('Invalid file type. Allowed types: JPEG, PNG, GIF');
            }
            
            // Create image from uploaded file
            $sourceImage = match($mimeType) {
                'image/jpeg' => imagecreatefromjpeg($uploadedFile['tmp_name']),
                'image/png' => imagecreatefrompng($uploadedFile['tmp_name']),
                'image/gif' => imagecreatefromgif($uploadedFile['tmp_name']),
                default => throw new RuntimeException('Unsupported image type')
            };
            
            if (!$sourceImage) {
                throw new RuntimeException('Failed to create image resource');
            }
            
            // Get original dimensions
            $origWidth = imagesx($sourceImage);
            $origHeight = imagesy($sourceImage);
            
            // Calculate new dimensions while maintaining aspect ratio
            $ratio = min(self::MAX_WIDTH / $origWidth, self::MAX_HEIGHT / $origHeight);
            $newWidth = (int)($origWidth * $ratio);
            $newHeight = (int)($origHeight * $ratio);
            
            // Create new image with calculated dimensions
            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Handle transparency for PNG images
            if ($mimeType === 'image/png') {
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
                imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
            }
            
            // Resize image
            imagecopyresampled(
                $newImage, $sourceImage,
                0, 0, 0, 0,
                $newWidth, $newHeight,
                $origWidth, $origHeight
            );
            
            // Generate unique filename
            $filename = sprintf(
                '%s/%s.webp',
                $this->uploadDir,
                substr(md5(uniqid(mt_rand(), true)), 0, 12)
            );
            
            // Save as WebP
            if (!imagewebp($newImage, $filename, self::WEBP_QUALITY)) {
                throw new RuntimeException('Failed to save WebP image');
            }
            
            // Cleanup
            imagedestroy($sourceImage);
            imagedestroy($newImage);
            
            return [
                'success' => true,
                'filename' => basename($filename)
            ];
            
        } catch (RuntimeException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
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
