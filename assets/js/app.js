// E-commerce Platform - Vanilla JavaScript

// API Service
class ApiService {
    static async request(url, method = 'GET', data = null) {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        if (data && (method === 'POST' || method === 'PUT')) {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(url, options);
            const result = await response.json();
            
            if (!response.ok) {
                throw new Error(result.message || 'Something went wrong');
            }
            
            return result;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    static get(url) {
        return this.request(url);
    }

    static post(url, data) {
        return this.request(url, 'POST', data);
    }

    static put(url, data) {
        return this.request(url, 'PUT', data);
    }

    static delete(url) {
        return this.request(url, 'DELETE');
    }
}

// Product Service
class ProductService {
    static async getProducts(category = null, search = null) {
        let url = '/new shopping/api/products.php';
        const params = new URLSearchParams();
        
        if (category) params.append('category', category);
        if (search) params.append('search', search);
        
        if (params.toString()) {
            url += '?' + params.toString();
        }
        
        return await ApiService.get(url);
    }

    static async getProduct(id) {
        return await ApiService.get(`/new shopping/api/products.php?id=${id}`);
    }

    static async addToCart(productId, quantity = 1) {
        return await ApiService.post('/new shopping/api/cart.php', {
            product_id: productId,
            quantity: quantity
        });
    }
}

// Cart Service
class CartService {
    static async getCart() {
        return await ApiService.get('/new shopping/api/cart.php');
    }

    static async updateQuantity(itemId, quantity) {
        return await ApiService.put('/new shopping/api/cart.php', {
            cart_id: itemId,
            quantity: quantity
        });
    }

    static async removeItem(itemId) {
        return await ApiService.delete(`/new shopping/api/cart.php?id=${itemId}`);
    }

    static async checkout(shippingAddress, paymentMethod) {
        return await ApiService.post('/new shopping/api/orders.php', {
            shipping_address: shippingAddress,
            payment_method: paymentMethod
        });
    }
}

// UI Components
class UIComponents {
    static showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background: ${type === 'success' ? '#4CAF50' : '#f44336'};
            color: white;
            border-radius: 4px;
            z-index: 9999;
            animation: slideIn 0.3s ease;
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    static createProductCard(product) {
        const card = document.createElement('div');
        card.className = 'product-card';
        card.innerHTML = `
            <img src="${product.image_url || '/assets/images/placeholder.jpg'}" alt="${product.product_name}">
            <h3>${product.product_name}</h3>
            <p class="price">$${product.price}</p>
            <p class="stock">${product.stock_quantity > 0 ? 'In Stock' : 'Out of Stock'}</p>
            <button onclick="handleAddToCart(${product.product_id})" ${product.stock_quantity === 0 ? 'disabled' : ''}>
                Add to Cart
            </button>
        `;
        return card;
    }

    static createCartItem(item) {
        const div = document.createElement('div');
        div.className = 'cart-item';
        div.innerHTML = `
            <div class="cart-item-info">
                <h4>${item.product_name}</h4>
                <p>Price: $${item.price}</p>
                <div class="quantity-control">
                    <button onclick="handleUpdateQuantity(${item.cart_id}, ${item.quantity - 1})">-</button>
                    <span>${item.quantity}</span>
                    <button onclick="handleUpdateQuantity(${item.cart_id}, ${item.quantity + 1})">+</button>
                </div>
            </div>
            <div class="cart-item-total">
                <p>$${item.price * item.quantity}</p>
                <button onclick="handleRemoveFromCart(${item.cart_id})" class="remove-btn">Remove</button>
            </div>
        `;
        return div;
    }
}

// Global Functions
window.handleAddToCart = async function(productId) {
    try {
        const response = await ProductService.addToCart(productId);
        UIComponents.showNotification('Product added to cart successfully');
        updateCartCount();
    } catch (error) {
        UIComponents.showNotification(error.message, 'error');
    }
};

window.handleUpdateQuantity = async function(cartId, newQuantity) {
    if (newQuantity < 1) {
        await handleRemoveFromCart(cartId);
        return;
    }
    
    try {
        const response = await CartService.updateQuantity(cartId, newQuantity);
        UIComponents.showNotification('Cart updated successfully');
        loadCart();
    } catch (error) {
        UIComponents.showNotification(error.message, 'error');
    }
};

window.handleRemoveFromCart = async function(cartId) {
    try {
        const response = await CartService.removeItem(cartId);
        UIComponents.showNotification('Item removed from cart');
        loadCart();
        updateCartCount();
    } catch (error) {
        UIComponents.showNotification(error.message, 'error');
    }
};

async function updateCartCount() {
    try {
        const response = await CartService.getCart();
        const cartItems = document.querySelector('.cart-count');
        if (cartItems) {
            cartItems.textContent = response.cart?.length || 0;
        }
    } catch (error) {
        console.error('Failed to update cart count:', error);
    }
}

// Form Validation
class FormValidator {
    static validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    static validatePassword(password) {
        return password.length >= 6;
    }

    static validatePhone(phone) {
        const re = /^[\d\s-+()]{10,}$/;
        return re.test(phone);
    }

    static showError(input, message) {
        const formGroup = input.closest('.form-group');
        let error = formGroup.querySelector('.error-message');
        
        if (!error) {
            error = document.createElement('span');
            error.className = 'error-message';
            formGroup.appendChild(error);
        }
        
        error.textContent = message;
        input.classList.add('error');
    }

    static clearError(input) {
        const formGroup = input.closest('.form-group');
        const error = formGroup.querySelector('.error-message');
        
        if (error) {
            error.remove();
        }
        input.classList.remove('error');
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize cart count
    updateCartCount();
    
    // Setup search functionality
    const searchForm = document.getElementById('search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const searchInput = this.querySelector('input[name="search"]');
            const categorySelect = this.querySelector('select[name="category"]');
            
            try {
                const products = await ProductService.getProducts(
                    categorySelect?.value,
                    searchInput.value
                );
                
                const productGrid = document.getElementById('product-grid');
                if (productGrid) {
                    productGrid.innerHTML = '';
                    products.forEach(product => {
                        productGrid.appendChild(UIComponents.createProductCard(product));
                    });
                }
            } catch (error) {
                UIComponents.showNotification('Failed to load products', 'error');
            }
        });
    }
    
    // Setup checkout form
    const checkoutForm = document.getElementById('checkout-form');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const shippingAddress = this.querySelector('[name="shipping_address"]').value;
            const paymentMethod = this.querySelector('[name="payment_method"]:checked')?.value;
            
            if (!shippingAddress || !paymentMethod) {
                UIComponents.showNotification('Please fill all required fields', 'error');
                return;
            }
            
            try {
                const response = await CartService.checkout(shippingAddress, paymentMethod);
                UIComponents.showNotification('Order placed successfully!');
                window.location.href = '/new shopping/profile.php';
            } catch (error) {
                UIComponents.showNotification(error.message, 'error');
            }
        });
    }
});

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .error-message {
        color: #f44336;
        font-size: 0.875rem;
        margin-top: 0.25rem;
        display: block;
    }
    
    input.error {
        border-color: #f44336 !important;
    }
    
    .notification {
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
`;
document.head.appendChild(style);