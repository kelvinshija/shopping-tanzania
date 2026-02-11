    </main>
    
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>About Us</h3>
                    <p>Your premier online shopping destination for quality products at great prices in Tanzania (ShoppingTz)</p>
                </div>
                
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="<?php echo BASE_URL; ?>index.php">Home</a></li>
                        <li><a href="<?php echo BASE_URL; ?>products.php">Products</a></li>
                        <li><a href="<?php echo BASE_URL; ?>cart.php">Cart</a></li>
                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <li><a href="<?php echo BASE_URL; ?>login.php">Login</a></li>
                            <li><a href="<?php echo BASE_URL; ?>register.php">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Contact Info</h3>
                    <ul>
                        <li>Email: hashunique8@gmail.com</li>
                        <li>Phone: 255 765 191 323</li>
                        <li>Address: Dar-es-salaam, Tanzania</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <script src="<?php echo BASE_URL; ?>assets/js/app.js"></script>
</body>
</html>