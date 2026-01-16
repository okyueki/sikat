<?php

if (!function_exists('getInventarisImageBase64')) {
    /**
     * Get inventaris image as base64 encoded string
     * Similar to radiologi implementation for security
     * 
     * @param string $photoPath Path to photo (relative path from inventaris storage)
     * @return string|null Base64 encoded image data URL or null if failed
     */
    function getInventarisImageBase64($photoPath)
    {
        if (empty($photoPath)) {
            return null;
        }

        // Get base URL from .env (default to current setup)
        $baseUrl = env('INVENTARIS_IMAGE_BASE_URL', 'http://192.168.10.74');
        $basePath = env('INVENTARIS_IMAGE_PATH', '/webapps2/inventaris');
        
        // Construct full URL
        $fullUrl = $baseUrl . $basePath . '/' . $photoPath;
        
        try {
            // Get image content using file_get_contents (like radiologi example)
            $imageContent = @file_get_contents($fullUrl);
            
            if ($imageContent === false || empty($imageContent)) {
                return null;
            }
            
            // Detect mime type from content
            $imageInfo = @getimagesizefromstring($imageContent);
            $mimeType = 'image/jpeg'; // default
            
            if ($imageInfo && isset($imageInfo['mime'])) {
                $mimeType = $imageInfo['mime'];
            } else {
                // Try to detect from file extension
                $extension = strtolower(pathinfo($photoPath, PATHINFO_EXTENSION));
                $mimeTypes = [
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp'
                ];
                $mimeType = $mimeTypes[$extension] ?? 'image/jpeg';
            }
            
            // Encode to base64 (like radiologi: data:image/jpeg;base64,...)
            $base64 = base64_encode($imageContent);
            
            return 'data:' . $mimeType . ';base64,' . $base64;
        } catch (\Exception $e) {
            \Log::error('Failed to load inventaris image: ' . $e->getMessage() . ' | URL: ' . $fullUrl);
            return null;
        }
    }
}
