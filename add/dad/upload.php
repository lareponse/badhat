<?php

function secure_file_upload(array $file, array $allowed_types, int $max_size): array
{
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Upload failed'];
    }

    // Validate file size
    if ($file['size'] > $max_size) {
        return ['error' => 'File too large'];
    }

    // Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed_types, true)) {
        return ['error' => 'Invalid file type'];
    }

    // Generate safe filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    $destination = 'uploads/' . date('Y/m/') . $filename;

    // Create directory if needed
    $dir = dirname($destination);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // Move file
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['error' => 'Failed to save file'];
    }

    return [
        'path' => $destination,
        'mime' => $mime,
        'size' => $file['size']
    ];
}