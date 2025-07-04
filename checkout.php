<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if required tables exist
$tables_exist = true;
$missing_tables = [];

$required_tables = ['orders', 'order_items', 'users', 'products'];
foreach ($required_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        $tables_exist = false;
        $missing_tables[] = $table;
    }
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode('checkout.php'));
    exit;
}

// Get user data
$user_id = $_SESSION['user_id'];
$user = null;

if ($tables_exist) {
    $user_query = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($user_query);
    
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
    } else {
        $user_error = "Error preparing user query: " . $conn->error;
    }
}

// Process checkout form
$error = '';
$success = '';
$debug_info = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if tables exist
    if (!$tables_exist) {
        $error = 'Database tables missing: ' . implode(', ', $missing_tables) . '. Please run the setup script first.';
    } 
    // Validate CSRF token
    else if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $error = 'Security validation failed. Please try again.';
    } else {
        // Get form data
        $shipping_address = isset($_POST['shipping_address']) ? sanitize_input($_POST['shipping_address']) : '';
        $shipping_city = isset($_POST['shipping_city']) ? sanitize_input($_POST['shipping_city']) : '';
        $shipping_state = isset($_POST['shipping_state']) ? sanitize_input($_POST['shipping_state']) : '';
        $shipping_zip = isset($_POST['shipping_zip']) ? sanitize_input($_POST['shipping_zip']) : '';
        $shipping_country = isset($_POST['shipping_country']) ? sanitize_input($_POST['shipping_country']) : '';
        
        $billing_address = isset($_POST['billing_address']) ? sanitize_input($_POST['billing_address']) : '';
        $billing_city = isset($_POST['billing_city']) ? sanitize_input($_POST['billing_city']) : '';
        $billing_state = isset($_POST['billing_state']) ? sanitize_input($_POST['billing_state']) : '';
        $billing_zip = isset($_POST['billing_zip']) ? sanitize_input($_POST['billing_zip']) : '';
        $billing_country = isset($_POST['billing_country']) ? sanitize_input($_POST['billing_country']) : '';
        
        $payment_method = isset($_POST['payment_method']) ? sanitize_input($_POST['payment_method']) : '';
        
        // Validate input
        if (empty($shipping_address) || empty($shipping_city) || empty($shipping_state) || empty($shipping_zip) || empty($shipping_country)) {
            $error = 'Please fill in all shipping address fields.';
        } elseif (empty($billing_address) || empty($billing_city) || empty($billing_state) || empty($billing_zip) || empty($billing_country)) {
            $error = 'Please fill in all billing address fields.';
        } elseif (empty($payment_method)) {
            $error = 'Please select a payment method.';
        } else {
            // Get cart data from POST
            $cart_data = isset($_POST['cart_data']) ? json_decode($_POST['cart_data'], true) : [];
            
            if (empty($cart_data)) {
                $error = 'Your cart is empty.';
            } else {
                // Calculate order total
                $subtotal = 0;
                foreach ($cart_data as $item) {
                    $subtotal += $item['price'] * $item['quantity'];
                }
                
                // Calculate shipping (free for orders over $100)
                $shipping = ($subtotal >= 100) ? 0 : 10;
                
                // Apply discount if promo code is applied
                $discount = 0;
                if (isset($_POST['promo_code']) && $_POST['promo_code'] === 'WELCOME10') {
                    $discount = $subtotal * 0.1;
                }
                
                // Calculate total
                $total = $subtotal + $shipping - $discount;
                
                // Format addresses
                $shipping_address_full = "$shipping_address, $shipping_city, $shipping_state $shipping_zip, $shipping_country";
                
                // Note: We're storing billing address in the order notes or comments since there's no billing_address column
                $billing_address_full = "$billing_address, $billing_city, $billing_state $billing_zip, $billing_country";
                
                // Set default status values
                $status = 'pending';
                $payment_status = 'pending';
                
                // Create order - using direct query to avoid prepare statement issues
                // Note: We're only including columns that exist in the table
                $order_query = "INSERT INTO orders (user_id, total_amount, status, shipping_address, payment_method, payment_status) 
                               VALUES ('$user_id', '$total', '$status', '$shipping_address_full', '$payment_method', '$payment_status')";
                
                $debug_info .= "Order Query: $order_query\n";
                
                if ($conn->query($order_query)) {
                    $order_id = $conn->insert_id;
                    $debug_info .= "Order created with ID: $order_id\n";
                    
                    // Store billing address in order_notes table if it exists
                    $notes_table_exists = $conn->query("SHOW TABLES LIKE 'order_notes'");
                    if ($notes_table_exists->num_rows > 0) {
                        $note_query = "INSERT INTO order_notes (order_id, note_type, note_content) 
                                      VALUES ('$order_id', 'billing_address', '$billing_address_full')";
                        $conn->query($note_query);
                    }
                    
                    // Add order items - using direct queries for simplicity
                    $all_items_added = true;
                    
                    foreach ($cart_data as $item) {
                        $product_id = (int)$item['id'];
                        $quantity = (int)$item['quantity'];
                        $price = (float)$item['price'];
                        
                        $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                                      VALUES ('$order_id', '$product_id', '$quantity', '$price')";
                        
                        $debug_info .= "Item Query: $item_query\n";
                        
                        if (!$conn->query($item_query)) {
                            $all_items_added = false;
                            $debug_info .= "Error adding item: " . $conn->error . "\n";
                        }
                        
                        // Update product stock - using direct query
                        $stock_query = "UPDATE products SET stock = stock - $quantity WHERE id = $product_id AND stock >= $quantity";
                        $conn->query($stock_query);
                    }
                    
                    if ($all_items_added) {
                        // Clear cart
                        $success = 'Order placed successfully! Your order number is #' . $order_id;
                    } else {
                        $error = 'Some items could not be added to your order. Please contact support.';
                    }
                } else {
                    $error = 'Failed to place order: ' . $conn->error;
                    $debug_info .= "Error creating order: " . $conn->error . "\n";
                    
                    // Check table structure
                    $table_info = "DESCRIBE orders";
                    $result = $conn->query($table_info);
                    if ($result) {
                        $debug_info .= "Orders table structure:\n";
                        while ($row = $result->fetch_assoc()) {
                            $debug_info .= $row['Field'] . " - " . $row['Type'] . " - " . $row['Null'] . " - " . $row['Key'] . "\n";
                        }
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - ElectroShop</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/checkout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .debug-info {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            font-family: monospace;
            white-space: pre-wrap;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .alert-warning {
            color: #856404;
            background-color: #fff3cd;
            border-color: #ffeeba;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="breadcrumbs">
            <a href="index.php">Home</a> &gt;
            <a href="cart.php">Shopping Cart</a> &gt;
            <span>Checkout</span>
        </div>
        
        <h1>Checkout</h1>
        
        <?php if (!$tables_exist): ?>
            <div class="alert alert-warning">
                <h3>Database Setup Required</h3>
                <p>The following tables are missing: <?php echo implode(', ', $missing_tables); ?></p>
                <p>Please run the database setup script to create the required tables.</p>
                <p>If you've already run the setup script, please check your database connection settings.</p>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($debug_info)): ?>
            <div class="debug-info">
                <h4>Debug Information:</h4>
                <?php echo $debug_info; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
                <p>Thank you for your purchase! You will receive a confirmation email shortly.</p>
                <p><a href="index.php" class="btn btn-primary">Continue Shopping</a></p>
            </div>
        <?php else: ?>
            <div class="checkout-container">
                <div class="checkout-form">
                    <form action="checkout.php" method="POST" id="checkout-form">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="cart_data" id="cart-data">
                        <input type="hidden" name="promo_code" id="promo-code-hidden">
                        
                        <div class="form-section">
                            <h2>Shipping Information</h2>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="shipping_address">Address</label>
                                    <input type="text" id="shipping_address" name="shipping_address" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="shipping_city">City</label>
                                    <input type="text" id="shipping_city" name="shipping_city" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="shipping_state">State/Province</label>
                                    <input type="text" id="shipping_state" name="shipping_state" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="shipping_zip">ZIP/Postal Code</label>
                                    <input type="text" id="shipping_zip" name="shipping_zip" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="shipping_country">Country</label>
                                    <select id="shipping_country" name="shipping_country" required>
                                        <option value="">Select Country</option>
                                         <option value="United States">Lebanon</option>
                                        <option value="Canada">Syria</option>
                                        <option value="United Kingdom">Jordan</option>
                                        <option value="Australia">Turkiye</option>
                                        <option value="Germany">Dubai</option>
                                        <option value="France">Saudi Arabia</option>
                                        <option value="Japan">Egypt</option>

                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h2>Billing Information</h2>
                            
                            <div class="form-group checkbox-group">
                                <label>
                                    <input type="checkbox" id="same-as-shipping"> Same as shipping address
                                </label>
                            </div>
                            
                            <div id="billing-fields" class="form-grid">
                                <div class="form-group">
                                    <label for="billing_address">Address</label>
                                    <input type="text" id="billing_address" name="billing_address" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="billing_city">City</label>
                                    <input type="text" id="billing_city" name="billing_city" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="billing_state">State/Province</label>
                                    <input type="text" id="billing_state" name="billing_state" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="billing_zip">ZIP/Postal Code</label>
                                    <input type="text" id="billing_zip" name="billing_zip" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="billing_country">Country</label>
                                    <select id="billing_country" name="billing_country" required>
                                        <option value="">Select Country</option>
                                        <option value="United States">Lebanon</option>
                                        <option value="Canada">Syria</option>
                                        <option value="United Kingdom">Jordan</option>
                                        <option value="Australia">Turkiye</option>
                                        <option value="Germany">Dubai</option>
                                        <option value="France">Saudi Arabia</option>
                                        <option value="Japan">Egypt</option>

                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h2>Payment Method</h2>
                            
                            <div class="payment-methods">
                                <div class="payment-method">
                                    <input type="radio" id="credit-card" name="payment_method" value="credit_card" required>
                                    <label for="credit-card">
                                        <i class="fas fa-credit-card"></i>
                                        Credit Card
                                    </label>
                                </div>
                                
                                <div class="payment-method">
                                    <input type="radio" id="paypal" name="payment_method" value="paypal" required>
                                    <label for="paypal">
                                        <i class="fab fa-paypal"></i>
                                        PayPal
                                    </label>
                                </div>
                                
                                <div class="payment-method">
                                    <input type="radio" id="bank-transfer" name="payment_method" value="bank_transfer" required>
                                    <label for="bank-transfer">
                                        <i class="fas fa-university"></i>
                                        Bank Transfer
                                    </label>
                                </div>
                            </div>
                            
                            <div id="credit-card-fields" class="payment-fields">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="card_number">Card Number</label>
                                        <input type="text" id="card_number" placeholder="1234 5678 9012 3456">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="card_name">Name on Card</label>
                                        <input type="text" id="card_name">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="expiry_date">Expiry Date</label>
                                        <input type="text" id="expiry_date" placeholder="MM/YY">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="cvv">CVV</label>
                                        <input type="text" id="cvv" placeholder="123">
                                    </div>
                                </div>
                                <p class="payment-note">payment will be processed.</p>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-block">Place Order</button>
                            <a href="cart.php" class="btn btn-light btn-block">Back to Cart</a>
                        </div>
                    </form>
                </div>
                
                <div class="order-summary">
                    <h2>Order Summary</h2>
                    
                    <div id="order-items">
                        <!-- Order items will be loaded dynamically via JavaScript -->
                        <div class="loading">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p>Loading your cart...</p>
                        </div>
                    </div>
                    
                    <div class="summary-totals">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span id="order-subtotal">$0.00</span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Shipping</span>
                            <span id="order-shipping">$0.00</span>
                        </div>
                        
                        <div class="summary-row discount" id="discount-row" style="display: none;">
                            <span>Discount</span>
                            <span id="order-discount">-$0.00</span>
                        </div>
                        
                        <div class="summary-row total">
                            <span>Total</span>
                            <span id="order-total">$0.00</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Load cart from localStorage
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            const orderItemsContainer = document.getElementById('order-items');
            const orderSubtotal = document.getElementById('order-subtotal');
            const orderShipping = document.getElementById('order-shipping');
            const orderDiscount = document.getElementById('order-discount');
            const orderTotal = document.getElementById('order-total');
            const discountRow = document.getElementById('discount-row');
            const cartDataInput = document.getElementById('cart-data');
            const promoCodeInput = document.getElementById('promo-code-hidden');
            
            // Set cart data in hidden input
            if (cartDataInput) {
                cartDataInput.value = JSON.stringify(cart);
            }
            
            // Render order items
            renderOrderItems();
            
            // Same as shipping checkbox
            const sameAsShippingCheckbox = document.getElementById('same-as-shipping');
            if (sameAsShippingCheckbox) {
                sameAsShippingCheckbox.addEventListener('change', function() {
                    const billingFields = document.getElementById('billing-fields');
                    
                    if (this.checked) {
                        // Copy shipping address to billing address
                        document.getElementById('billing_address').value = document.getElementById('shipping_address').value;
                        document.getElementById('billing_city').value = document.getElementById('shipping_city').value;
                        document.getElementById('billing_state').value = document.getElementById('shipping_state').value;
                        document.getElementById('billing_zip').value = document.getElementById('shipping_zip').value;
                        document.getElementById('billing_country').value = document.getElementById('shipping_country').value;
                        
                        // Hide billing fields
                        billingFields.style.display = 'none';
                    } else {
                        // Show billing fields
                        billingFields.style.display = 'grid';
                    }
                });
            }
            
            // Payment method selection
            const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
            const creditCardFields = document.getElementById('credit-card-fields');
            
            if (paymentMethods.length > 0 && creditCardFields) {
                paymentMethods.forEach(method => {
                    method.addEventListener('change', function() {
                        if (this.value === 'credit_card') {
                            creditCardFields.style.display = 'block';
                        } else {
                            creditCardFields.style.display = 'none';
                        }
                    });
                });
            }
            
            function renderOrderItems() {
                if (!orderItemsContainer) return;
                
                if (cart.length === 0) {
                    // Cart is empty
                    orderItemsContainer.innerHTML = `
                        <div class="empty-cart-message">
                            <p>Your cart is empty.</p>
                            <a href="products.php" class="btn btn-primary">Start Shopping</a>
                        </div>
                    `;
                    
                    // Disable checkout form
                    const checkoutForm = document.getElementById('checkout-form');
                    if (checkoutForm) {
                        checkoutForm.querySelectorAll('input, select, button').forEach(element => {
                            element.disabled = true;
                        });
                    }
                } else {
                    // Render order items
                    let orderHTML = `<div class="order-items">`;
                    
                    cart.forEach(item => {
                        orderHTML += `
                            <div class="order-item">
                                <div class="item-image">
                                    <img src="${item.image}" alt="${item.name}">
                                    <span class="item-quantity">${item.quantity}</span>
                                </div>
                                <div class="item-details">
                                    <h3>${item.name}</h3>
                                    <div class="item-price">
                                        <span>${formatPrice(item.price)}</span>
                                    </div>
                                </div>
                                <div class="item-total">
                                    ${formatPrice(item.price * item.quantity)}
                                </div>
                            </div>
                        `;
                    });
                    
                    orderHTML += `</div>`;
                    
                    // Add promo code field
                    orderHTML += `
                        <div class="promo-code">
                            <input type="text" id="promo-code" placeholder="Promo code">
                            <button type="button" class="btn btn-secondary" id="apply-promo">Apply</button>
                        </div>
                    `;
                    
                    orderItemsContainer.innerHTML = orderHTML;
                    
                    // Apply promo code
                    document.getElementById('apply-promo').addEventListener('click', function() {
                        const promoCode = document.getElementById('promo-code').value.trim();
                        
                        if (promoCode === 'WELCOME10') {
                            // Apply 10% discount
                            const subtotal = calculateSubtotal();
                            const discount = subtotal * 0.1;
                            
                            orderDiscount.textContent = `-${formatPrice(discount)}`;
                            discountRow.style.display = 'flex';
                            
                            // Set promo code in hidden input
                            if (promoCodeInput) {
                                promoCodeInput.value = promoCode;
                            }
                            
                            updateOrderSummary();
                            
                            alert('Promo code applied successfully!');
                        } else {
                            alert('Invalid promo code. Please try again.');
                        }
                    });
                }
                
                // Update order summary
                updateOrderSummary();
            }
            
            function calculateSubtotal() {
                return cart.reduce((total, item) => total + (item.price * item.quantity), 0);
            }
            
            function updateOrderSummary() {
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
                orderSubtotal.textContent = formatPrice(subtotal);
                orderShipping.textContent = shipping > 0 ? formatPrice(shipping) : 'Free';
                orderTotal.textContent = formatPrice(total);
            }
            
            function formatPrice(price) {
                return '$' + price.toFixed(2);
            }
        });
    </script>
    <script src="js/header-scroll.js"></script>
</body>
</html>
