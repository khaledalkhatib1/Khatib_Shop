<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';


$featured_query = "SELECT * FROM products WHERE featured = 1 LIMIT 4";
$featured_result = $conn->query($featured_query);
$featured_products = [];
if ($featured_result) {
    while ($row = $featured_result->fetch_assoc()) {
        $featured_products[] = $row;
    }
}


$new_arrivals_query = "SELECT * FROM products WHERE is_new = 1 ORDER BY id DESC LIMIT 4";
$new_arrivals_result = $conn->query($new_arrivals_query);
$new_arrivals = [];
if ($new_arrivals_result) {
    while ($row = $new_arrivals_result->fetch_assoc()) {
        $new_arrivals[] = $row;
    }
}


$categories_query = "SELECT * FROM categories LIMIT 4";
$categories_result = $conn->query($categories_query);
$categories = [];
if ($categories_result) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ElectroShop - Your One-Stop Electronics Store</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/footer-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
   
    <?php include 'includes/header.php'; ?>

   
    <section class="hero">
        <div class="hero-content">
            <h1>Next-Gen Electronics for Modern Living</h1>
            <p>Discover cutting-edge appliances, TVs, and energy solutions that transform your home and lifestyle.</p>
            <div class="hero-buttons">
                <a href="products.php" class="btn btn-primary">Shop Now</a>
                <a href="products.php?category=solar-systems" class="btn btn-secondary">Explore Solar Solutions</a>
            </div>
        </div>
    </section>

    
    <section class="featured-categories">
        <div class="container">
            <div class="section-header">
                <h2>Featured Categories</h2>
                <a href="products.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="category-grid">
                <?php foreach ($categories as $category): ?>
                <div class="category-card">
                    <a href="products.php?category=<?php echo $category['slug']; ?>">
                        <img src="<?php echo !empty($category['image']) ? $category['image'] : 'images/categories/placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($category['name']); ?>">
                        <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

  
    <section class="new-arrivals">
        <div class="container">
            <div class="section-header">
                <h2>New Arrivals</h2>
                <a href="new-arrivals.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="product-grid">
                <?php foreach ($new_arrivals as $product): ?>
                <div class="product-card">
                    <div class="product-badge">New</div>
                    <div class="product-image">
                        <img src="<?php echo !empty($product['image']) ? $product['image'] : 'images/products/placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </div>
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <div class="product-price">
                            <span class="current-price">$<?php echo number_format($product['price'], 2); ?></span>
                            <?php if ($product['original_price'] > $product['price']): ?>
                            <span class="original-price">$<?php echo number_format($product['original_price'], 2); ?></span>
                            <span class="discount"><?php echo round(($product['original_price'] - $product['price']) / $product['original_price'] * 100); ?>% OFF</span>
                            <?php endif; ?>
                        </div>
                        <div class="product-actions">
                            <button class="add-to-cart" data-id="<?php echo $product['id']; ?>"><i class="fas fa-shopping-cart"></i> Add to Cart</button>
                            <button class="quick-view" data-id="<?php echo $product['id']; ?>"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    
    <section class="featured-products">
        <div class="container">
            <div class="section-header">
                <h2>Featured Products</h2>
                <a href="products.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="product-grid">
                <?php foreach ($featured_products as $product): ?>
                <div class="product-card">
                    <div class="product-badge featured">Featured</div>
                    <div class="product-image">
                        <img src="<?php echo !empty($product['image']) ? $product['image'] : 'images/products/placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </div>
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <div class="product-price">
                            <span class="current-price">$<?php echo number_format($product['price'], 2); ?></span>
                            <?php if ($product['original_price'] > $product['price']): ?>
                            <span class="original-price">$<?php echo number_format($product['original_price'], 2); ?></span>
                            <span class="discount"><?php echo round(($product['original_price'] - $product['price']) / $product['original_price'] * 100); ?>% OFF</span>
                            <?php endif; ?>
                        </div>
                        <div class="product-actions">
                            <button class="add-to-cart" data-id="<?php echo $product['id']; ?>"><i class="fas fa-shopping-cart"></i> Add to Cart</button>
                            <button class="quick-view" data-id="<?php echo $product['id']; ?>"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    
    <section class="benefits">
        <div class="container">
            <div class="benefits-grid">
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <h3>Free Shipping</h3>
                    <p>On all orders over $100</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>24/7 Support</h3>
                    <p>Get help when you need it</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-undo"></i>
                    </div>
                    <h3>Easy Returns</h3>
                    <p>30-day money back guarantee</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Secure Payment</h3>
                    <p>Your data is protected</p>
                </div>
            </div>
        </div>
    </section>

    
    <section class="newsletter">
        <div class="container">
            <div class="newsletter-content">
                <h2>Subscribe to Our Newsletter</h2>
                <p>Get the latest updates on new products and special promotions</p>
                <form action="index.php" method="POST" class="newsletter-form">
                    <input type="email" name="email" placeholder="Your email address" required>
                    <button type="submit" class="btn btn-primary">Subscribe</button>
                </form>
            </div>
        </div>
    </section>

    
    <?php include 'includes/footer.php'; ?>

   
    <div class="modal" id="quickViewModal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div class="product-quick-view">
                <div class="product-quick-view-image">
                    <img src="/placeholder.svg" alt="Product Image" id="modalProductImage">
                </div>
                <div class="product-quick-view-info">
                    <h2 id="modalProductName"></h2>
                    <div class="product-price">
                        <span class="current-price" id="modalProductPrice"></span>
                        <span class="original-price" id="modalProductOriginalPrice"></span>
                        <span class="discount" id="modalProductDiscount"></span>
                    </div>
                    <div class="product-description" id="modalProductDescription"></div>
                    <div class="product-quantity">
                        <label for="quantity">Quantity:</label>
                        <div class="quantity-selector">
                            <button class="quantity-decrease">-</button>
                            <input type="number" id="quantity" value="1" min="1">
                            <button class="quantity-increase">+</button>
                        </div>
                    </div>
                    <button class="btn btn-primary add-to-cart-modal" id="modalAddToCart"><i class="fas fa-shopping-cart"></i> Add to Cart</button>
                    <a href="#" class="btn btn-secondary" id="modalViewDetails">View Details</a>
                </div>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script src="js/header-scroll.js"></script>
</body>
</html>
