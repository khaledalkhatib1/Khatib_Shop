<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - ElectroShop</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/cart.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="breadcrumbs">
            <a href="index.php">Home</a> &gt;
            <span>Shopping Cart</span>
        </div>
        
        <h1>Shopping Cart</h1>
        
        <div id="cart-container" class="cart-container">
            <div id="cart-items">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading your cart...</p>
                </div>
            </div>
            
            <div class="cart-summary">
                <h2>Order Summary</h2>
                
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span id="cart-subtotal">$0.00</span>
                </div>
                
                <div class="summary-row">
                    <span>Shipping</span>
                    <span id="cart-shipping">$0.00</span>
                </div>
                
                <div class="summary-row discount" id="discount-row" style="display: none;">
                    <span>Discount</span>
                    <span id="cart-discount">-$0.00</span>
                </div>
                
                <div class="summary-row total">
                    <span>Total</span>
                    <span id="cart-total">$0.00</span>
                </div>
                
                <div class="promo-code">
                    <input type="text" id="promo-code" placeholder="Promo code">
                    <button class="btn btn-secondary" id="apply-promo">Apply</button>
                </div>
                
                <div class="cart-buttons">
                    <a href="checkout.php" class="btn btn-primary btn-block" id="checkout-btn">Proceed to Checkout</a>
                    <a href="products.php" class="btn btn-light btn-block">Continue Shopping</a>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            const cartItemsContainer = document.getElementById('cart-items');
            const cartSubtotal = document.getElementById('cart-subtotal');
            const cartShipping = document.getElementById('cart-shipping');
            const cartDiscount = document.getElementById('cart-discount');
            const cartTotal = document.getElementById('cart-total');
            const discountRow = document.getElementById('discount-row');
            const checkoutBtn = document.getElementById('checkout-btn');
            
            renderCart();
            
            document.getElementById('apply-promo').addEventListener('click', function() {
                const promoCode = document.getElementById('promo-code').value.trim();
                
                if (promoCode === 'WELCOME10') {
                    const subtotal = calculateSubtotal();
                    const discount = subtotal * 0.1;
                    
                    cartDiscount.textContent = `-${formatPrice(discount)}`;
                    discountRow.style.display = 'flex';
                    
                    updateCartSummary();
                    
                    alert('Promo code applied successfully!');
                } else {
                    alert('Invalid promo code. Please try again.');
                }
            });
            
            function renderCart() {
                if (cart.length === 0) {
                    // Cart is empty
                    cartItemsContainer.innerHTML = `
                        <div class="empty-cart-message">
                            <i class="fas fa-shopping-cart"></i>
                            <h2>Your cart is empty</h2>
                            <p>Looks like you haven't added any products to your cart yet.</p>
                            <a href="products.php" class="btn btn-primary">Start Shopping</a>
                        </div>
                    `;
                    
                    checkoutBtn.classList.add('disabled');
                    checkoutBtn.href = 'javascript:void(0)';
                } else {
                    let cartHTML = `
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                    `;
                    
                    cart.forEach((item, index) => {
                        cartHTML += `
                            <tr>
                                <td>
                                    <div class="product-info">
                                        <img src="${item.image}" alt="${item.name}">
                                        <div>
                                            <h3>${item.name}</h3>
                                        </div>
                                    </div>
                                </td>
                                <td class="product-price">${formatPrice(item.price)}</td>
                                <td>
                                    <div class="product-quantity">
                                        <div class="quantity-selector">
                                            <button class="quantity-decrease" data-index="${index}">-</button>
                                            <input type="number" value="${item.quantity}" min="1" data-index="${index}">
                                            <button class="quantity-increase" data-index="${index}">+</button>
                                        </div>
                                    </div>
                                </td>
                                <td class="product-total">${formatPrice(item.price * item.quantity)}</td>
                                <td class="product-remove">
                                    <button data-index="${index}"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        `;
                    });
                    
                    cartHTML += `
                            </tbody>
                        </table>
                        <div class="cart-actions">
                            <button class="btn btn-light" id="clear-cart">Clear Cart</button>
                        </div>
                    `;
                    
                    cartItemsContainer.innerHTML = cartHTML;
                    
                    checkoutBtn.classList.remove('disabled');
                    checkoutBtn.href = 'checkout.php';
                    
                    addCartEventListeners();
                }
                
                updateCartSummary();
            }
            
            function addCartEventListeners() {
                document.querySelectorAll('.quantity-decrease').forEach(button => {
                    button.addEventListener('click', function() {
                        const index = parseInt(this.getAttribute('data-index'));
                        if (cart[index].quantity > 1) {
                            cart[index].quantity--;
                            updateCart();
                        }
                    });
                });
                
                document.querySelectorAll('.quantity-increase').forEach(button => {
                    button.addEventListener('click', function() {
                        const index = parseInt(this.getAttribute('data-index'));
                        cart[index].quantity++;
                        updateCart();
                    });
                });
                
                // Quantity input fields
                document.querySelectorAll('.quantity-selector input').forEach(input => {
                    input.addEventListener('change', function() {
                        const index = parseInt(this.getAttribute('data-index'));
                        const quantity = parseInt(this.value);
                        
                        if (quantity >= 1) {
                            cart[index].quantity = quantity;
                            updateCart();
                        } else {
                            this.value = cart[index].quantity;
                        }
                    });
                });
                
                // Remove buttons
                document.querySelectorAll('.product-remove button').forEach(button => {
                    button.addEventListener('click', function() {
                        const index = parseInt(this.getAttribute('data-index'));
                        if (confirm('Are you sure you want to remove this item from your cart?')) {
                            cart.splice(index, 1);
                            updateCart();
                        }
                    });
                });
                
                // Clear cart button
                document.getElementById('clear-cart').addEventListener('click', function() {
                    if (confirm('Are you sure you want to clear your cart?')) {
                        cart.length = 0;
                        updateCart();
                    }
                });
            }
            
            function updateCart() {
                // Save cart to localStorage
                localStorage.setItem('cart', JSON.stringify(cart));
                
                // Update cart count in header
                const cartCount = document.querySelector('.cart-count');
                if (cartCount) {
                    const totalItems = cart.reduce((total, item) => total + item.quantity, 0);
                    cartCount.textContent = totalItems;
                }
                
                // Re-render cart
                renderCart();
            }
            
            function calculateSubtotal() {
                return cart.reduce((total, item) => total + (item.price * item.quantity), 0);
            }
            
            function updateCartSummary() {
                const subtotal = calculateSubtotal();
                let shipping = 0;
                
                // Calculate shipping (free for orders over $100)
                if (subtotal > 0 && subtotal < 100) {
                    shipping = 10;
                }
                
                // Apply discount if promo code is applied
                let discount = 0;
                if (discountRow.style.display === 'flex') {
                    discount = subtotal * 0.1;
                }
                
                // Calculate total
                const total = subtotal + shipping - discount;
                
                // Update summary
                cartSubtotal.textContent = formatPrice(subtotal);
                cartShipping.textContent = shipping > 0 ? formatPrice(shipping) : 'Free';
                cartTotal.textContent = formatPrice(total);
            }
            
            function formatPrice(price) {
                return '$' + price.toFixed(2);
            }
        });
    </script>
    <script src="js/header-scroll.js"></script>
</body>
</html>
