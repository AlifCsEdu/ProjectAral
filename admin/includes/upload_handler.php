<?php
function handleImageUpload($imageFile, $imageUrl) {
    $uploadDir = '../assets/images/';
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB

    // If image URL is provided
    if (!empty($imageUrl)) {
        // Validate URL
        if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid image URL');
        }

        // Get file info from URL
        $headers = get_headers($imageUrl, 1);
        if ($headers === false) {
            throw new Exception('Could not access image URL');
        }

        // Check content type
        $contentType = $headers['Content-Type'];
        if (is_array($contentType)) {
            $contentType = end($contentType);
        }
        if (!in_array(strtolower($contentType), $allowedTypes)) {
            throw new Exception('Invalid image type from URL. Only JPG, PNG, GIF, and WebP are allowed');
        }

        // Get file extension from content type
        $extension = match(strtolower($contentType)) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => throw new Exception('Unsupported image type')
        };
        
        $newFileName = uniqid() . '_' . time() . '.' . $extension;
        $targetPath = $uploadDir . $newFileName;
        
        // Download and save the image
        $imageContent = file_get_contents($imageUrl);
        if ($imageContent === false) {
            throw new Exception('Failed to download image from URL');
        }
        
        if (file_put_contents('../' . $targetPath, $imageContent) === false) {
            throw new Exception('Failed to save image from URL');
        }
        
        return 'assets/images/' . $newFileName;
    }
    
    // If file is uploaded
    if (isset($imageFile['tmp_name']) && !empty($imageFile['tmp_name'])) {
        // Validate file size
        if ($imageFile['size'] > $maxFileSize) {
            throw new Exception('File is too large. Maximum size is 5MB');
        }

        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $imageFile['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('Invalid file type. Only JPG, PNG, GIF, and WebP are allowed');
        }

        // Generate unique filename
        $extension = match($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => throw new Exception('Unsupported image type')
        };
        $newFileName = uniqid() . '_' . time() . '.' . $extension;
        $targetPath = $uploadDir . $newFileName;

        // Move uploaded file
        if (!move_uploaded_file($imageFile['tmp_name'], '../' . $targetPath)) {
            throw new Exception('Failed to save uploaded file');
        }

        return 'assets/images/' . $newFileName;
    }

    throw new Exception('No image provided');
}
