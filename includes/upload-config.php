<?php
// Upload configuration
define('UPLOAD_BASE_PATH', __DIR__ . '/../assets/images/');
define('PRODUCTS_UPLOAD_PATH', UPLOAD_BASE_PATH . 'products/');
define('CATEGORIES_UPLOAD_PATH', UPLOAD_BASE_PATH . 'categories/');
define('PLACEHOLDER_PATH', UPLOAD_BASE_PATH . 'placeholder.jpg');

// Create upload directories if they don't exist
function initUploadDirectories() {
    $directories = [
        PRODUCTS_UPLOAD_PATH,
        CATEGORIES_UPLOAD_PATH
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0777, true)) {
                error_log("Failed to create directory: $dir");
                return false;
            }
            chmod($dir, 0777);
        }
    }
    return true;
}

// Initialize on load
initUploadDirectories();
?>