<!-- Sehemu ya kuonyesha picha kubwa -->
<img src="<?php echo htmlspecialchars($product['image_url'] ?? BASE_URL . 'assets/images/placeholder.jpg'); ?>" 
     alt="<?php echo htmlspecialchars($product['product_name']); ?>"
     class="product-main-image"
     onerror="this.src='<?php echo BASE_URL; ?>assets/images/placeholder.jpg'">