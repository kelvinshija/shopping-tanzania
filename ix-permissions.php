<?php
// fix-permissions.php - Run this file once to fix directory permissions
require_once 'includes/config.php';

echo "<h1>Fixing Directory Permissions</h1>";

// Define directories to create
$directories = [
    'assets/images',
    'assets/images/products',
    'assets/images/categories',
    'assets/images/placeholders',
    'admin/uploads',
    'uploads',
    'uploads/products',
    'uploads/temp'
];

foreach ($directories as $dir) {
    $full_path = __DIR__ . '/' . $dir;
    
    // Create directory if it doesn't exist
    if (!file_exists($full_path)) {
        if (mkdir($full_path, 0777, true)) {
            echo "<p style='color: green;'>✓ Created directory: $dir</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to create directory: $dir</p>";
        }
    } else {
        echo "<p style='color: blue;'>✓ Directory already exists: $dir</p>";
    }
    
    // Set permissions on macOS
    if (file_exists($full_path)) {
        chmod($full_path, 0777);
        echo "<p style='color: green;'>  Set permissions to 0777 for: $dir</p>";
    }
}

// Create a placeholder image
$placeholder_dir = __DIR__ . '/assets/images/';
$placeholder_file = $placeholder_dir . 'placeholder.jpg';

if (!file_exists($placeholder_file)) {
    // Create a simple placeholder image
    $im = imagecreatetruecolor(300, 300);
    $bg = imagecolorallocate($im, 240, 240, 240);
    $text_color = imagecolorallocate($im, 100, 100, 100);
    
    imagefilledrectangle($im, 0, 0, 300, 300, $bg);
    imagestring($im, 5, 100, 140, 'No Image', $text_color);
    imagejpeg($im, $placeholder_file);
    imagedestroy($im);
    
    echo "<p style='color: green;'>✓ Created placeholder image</p>";
}

echo "<hr>";
echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>Delete this file (fix-permissions.php) after running it</li>";
echo "<li>Try uploading products again</li>";
echo "<li>If still having issues, check the alternative solution below</li>";
echo "</ol>";

echo "<p><a href='admin/products.php' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Admin Products</a></p>";
?>