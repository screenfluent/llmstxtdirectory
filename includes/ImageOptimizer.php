<?php

class ImageOptimizer
{
    private const MAX_SIZE = 64; // Maximum width or height
    private const WEBP_QUALITY = 95; // WebP quality (0-100)
    private const ALLOWED_TYPES = [
        "image/jpeg",
        "image/png",
        "image/gif",
        "image/svg+xml",
    ];

    private string $uploadDir;

    public function __construct(string $uploadDir = "public/logos")
    {
        // Convert relative path to absolute if needed
        if (strpos($uploadDir, "/") !== 0) {
            $uploadDir = __DIR__ . "/../" . $uploadDir;
        }
        $this->uploadDir = rtrim($uploadDir, "/");

        // Ensure GD is available
        if (!extension_loaded("gd")) {
            throw new RuntimeException(
                "GD extension is required for image processing"
            );
        }
    }

    /**
     * Process and optimize an uploaded image
     *
     * @param array $uploadedFile $_FILES array item
     * @param string $name Implementation name for the filename
     * @return array{success: bool, filename?: string, error?: string}
     */
    public function processUploadedImage(
        array $uploadedFile,
        string $name
    ): array {
        try {
            // Validate upload
            if (
                !isset($uploadedFile["tmp_name"]) ||
                !is_uploaded_file($uploadedFile["tmp_name"])
            ) {
                throw new RuntimeException("Invalid upload");
            }

            // Validate mime type
            $mimeType = mime_content_type($uploadedFile["tmp_name"]);
            if (!in_array($mimeType, self::ALLOWED_TYPES)) {
                throw new RuntimeException("Invalid file type");
            }

            // Ensure upload directory exists and is writable
            if (!$this->ensureUploadDirectory()) {
                throw new RuntimeException(
                    "Failed to create or access upload directory"
                );
            }

            // Generate safe filename
            $safeName = strtolower(preg_replace("/[^a-zA-Z0-9]/", "", $name));
            // Add a unique identifier to prevent collisions
            $safeName .= '_' . substr(md5(uniqid()), 0, 8);

            // Clean up any existing logo files for this implementation
            $this->cleanupExistingLogos($safeName);

            // Handle based on file type
            if ($mimeType === "image/svg+xml") {
                return $this->handleSvgUpload($uploadedFile, $safeName);
            } else {
                return $this->handleRasterImageUpload($uploadedFile, $safeName);
            }
        } catch (RuntimeException $e) {
            $this->logError("Image processing error: " . $e->getMessage());
            return ["success" => false, "error" => $e->getMessage()];
        }
    }

    /**
     * Ensure upload directory exists and is writable
     */
    private function ensureUploadDirectory(): bool
    {
        if (!is_dir($this->uploadDir)) {
            if (!mkdir($this->uploadDir, 0775, true)) {
                return false;
            }
        }

        if (!is_writable($this->uploadDir)) {
            chmod($this->uploadDir, 0775);
            if (!is_writable($this->uploadDir)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Handle SVG file upload
     */
    private function handleSvgUpload(array $file, string $safeName): array
    {
        $filename = $safeName . ".svg";
        $targetPath = $this->uploadDir . "/" . $filename;

        // Delete existing file
        if (file_exists($targetPath)) {
            if (!is_writable($targetPath)) {
                chmod($targetPath, 0664);
            }
            if (!unlink($targetPath)) {
                throw new RuntimeException("Failed to delete existing file");
            }
        }

        // Move uploaded file
        if (!move_uploaded_file($file["tmp_name"], $targetPath)) {
            throw new RuntimeException("Failed to move uploaded file");
        }

        // Set permissions
        chmod($targetPath, 0664);

        return [
            "success" => true,
            "filename" => $filename,
        ];
    }

    /**
     * Handle raster image upload (PNG, JPEG, GIF)
     */
    private function handleRasterImageUpload(
        array $file,
        string $safeName
    ): array {
        // Create image from uploaded file
        $sourceImage = match (mime_content_type($file["tmp_name"])) {
            "image/jpeg" => imagecreatefromjpeg($file["tmp_name"]),
            "image/png" => imagecreatefrompng($file["tmp_name"]),
            "image/gif" => imagecreatefromgif($file["tmp_name"]),
            default => throw new RuntimeException("Unsupported image type"),
        };

        if (!$sourceImage) {
            throw new RuntimeException("Failed to create image resource");
        }

        try {
            // Process image
            $newImage = $this->processImage($sourceImage);

            // Save as WebP
            $filename = $safeName . ".webp";
            $targetPath = $this->uploadDir . "/" . $filename;

            // Delete existing file with more detailed error handling
            if (file_exists($targetPath)) {
                error_log("Attempting to delete existing file: " . $targetPath);
                
                if (!is_writable($targetPath)) {
                    error_log("File is not writable, attempting to change permissions: " . $targetPath);
                    chmod($targetPath, 0664);
                }
                
                if (!unlink($targetPath)) {
                    $error = error_get_last();
                    throw new RuntimeException(
                        "Failed to delete existing file: " . ($error['message'] ?? 'Unknown error')
                    );
                }
                error_log("Successfully deleted existing file: " . $targetPath);
            }

            // Save new image with error handling
            if (!imagewebp($newImage, $targetPath, self::WEBP_QUALITY)) {
                $error = error_get_last();
                throw new RuntimeException(
                    "Failed to save WebP image: " . ($error['message'] ?? 'Unknown error')
                );
            }

            // Set permissions with error handling
            if (!chmod($targetPath, 0664)) {
                $error = error_get_last();
                error_log("Warning: Failed to set permissions on new file: " . ($error['message'] ?? 'Unknown error'));
            }

            error_log("Successfully saved new image: " . $targetPath);
            return [
                "success" => true,
                "filename" => $filename,
            ];
        } finally {
            // Clean up resources
            imagedestroy($sourceImage);
            if (isset($newImage)) {
                imagedestroy($newImage);
            }
        }
    }

    /**
     * Process image - resize and optimize
     */
    private function processImage($sourceImage)
    {
        $origWidth = imagesx($sourceImage);
        $origHeight = imagesy($sourceImage);

        // Calculate new dimensions
        if ($origWidth > $origHeight) {
            $newWidth = min($origWidth, self::MAX_SIZE);
            $newHeight = (int) ($origHeight * ($newWidth / $origWidth));
        } else {
            $newHeight = min($origHeight, self::MAX_SIZE);
            $newWidth = (int) ($origWidth * ($newHeight / $origHeight));
        }

        // Create new image
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // Enable alpha channel
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);

        // Fill with transparent background
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle(
            $newImage,
            0,
            0,
            $newWidth,
            $newHeight,
            $transparent
        );

        // Copy and resize
        imagecopyresampled(
            $newImage,
            $sourceImage,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $origWidth,
            $origHeight
        );

        return $newImage;
    }

    /**
     * Remove all existing logo files for a given implementation name
     * This ensures we don't have stale files when updating logos
     */
    private function cleanupExistingLogos(string $safeName): void {
        $extensions = ["webp", "svg", "png", "jpg", "jpeg"];
        
        foreach ($extensions as $ext) {
            $pattern = $this->uploadDir . "/" . $safeName . "*." . $ext;
            $files = glob($pattern);
            
            if ($files === false) {
                error_log("Warning: Failed to search for existing files with pattern: " . $pattern);
                continue;
            }
            
            foreach ($files as $file) {
                error_log("Found existing logo file to clean up: " . $file);
                if (is_writable($file)) {
                    if (!unlink($file)) {
                        error_log("Warning: Failed to delete existing logo file: " . $file);
                    } else {
                        error_log("Successfully deleted existing logo file: " . $file);
                    }
                } else {
                    error_log("Warning: Existing logo file not writable: " . $file);
                }
            }
        }
    }

    /**
     * Log error message and data
     *
     * @param string $message Error message to log
     * @param array $data Additional data to log
     * @return void
     */
    private function logError($message, $data = []): void
    {
        $timestamp = date("Y-m-d H:i:s");
        error_log(
            "[$timestamp] ImageOptimizer: $message " . json_encode($data)
        );
    }

    /**
     * Clean up old images that are no longer referenced
     *
     * @param array $referencedFiles Array of filenames that are still in use
     * @return int Number of files deleted
     */
    public function cleanupUnusedImages(array $referencedFiles): int
    {
        $deleted = 0;
        $defaultLogo = "default.svg";

        // Scan directory for all WebP files
        $files = glob($this->uploadDir . "/*.webp");

        foreach ($files as $file) {
            $filename = basename($file);
            // Skip if file is referenced or is default logo
            if (
                $filename === $defaultLogo ||
                in_array($filename, $referencedFiles)
            ) {
                continue;
            }

            if (unlink($file)) {
                $deleted++;
            }
        }

        return $deleted;
    }
}
