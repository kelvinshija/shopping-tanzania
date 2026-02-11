#!/bin/bash
# fix-mac-permissions.sh

echo "Fixing permissions for XAMPP on macOS..."

# Navigate to project directory
cd /Applications/XAMPP/xamppfiles/htdocs/new\ shopping/

# Create directories
mkdir -p assets/images/products
mkdir -p assets/images/categories
mkdir -p admin/uploads

# Set permissions - 777 for development
chmod -R 777 assets/images
chmod -R 777 admin/uploads

# Change owner to daemon (XAMPP user on macOS)
sudo chown -R daemon:daemon assets/images
sudo chown -R daemon:daemon admin/uploads

echo "Creating placeholder image..."

# Create a simple placeholder image if it doesn't exist
if [ ! -f "assets/images/placeholder.jpg" ]; then
    convert -size 300x300 xc:lightgray -font Arial -pointsize 20 -fill gray -draw "text 100,150 'No Image'" assets/images/placeholder.jpg 2>/dev/null || \
    echo "ImageMagick not installed. Please create placeholder.jpg manually."
fi

echo "Restarting XAMPP..."
sudo /Applications/XAMPP/xamppfiles/xampp restart

echo "Done! Permissions fixed."