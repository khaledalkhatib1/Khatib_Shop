<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: products.php');
    exit;
}

$product_id = $_GET['id'];

$stmt = $conn->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: products.php');
    exit;
}

$product = $result->fetch_assoc();

$product_images = [];
$stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $product_images[] = $row;
}

$related_products = [];
if ($product['category_id']) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? LIMIT 4");
    $stmt->bind_param("ii", $product['category_id'], $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $related_products[] = $row;
    }
}

if (count($related_products) < 4) {
    $limit = 4 - count($related_products);
    $stmt = $conn->prepare("SELECT * FROM products WHERE id != ? AND featured = 1 AND category_id != ? LIMIT ?");
    $stmt->bind_param("iii", $product_id, $product['category_id'], $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $related_products[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - ElectroShop</title>
    <meta name="description" content="<?php echo htmlspecialchars(substr(strip_tags($product['description']), 0, 160)); ?>">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/product.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="breadcrumbs">
            <a href="index.php">Home</a> &gt;
            <?php if ($product['category_id']): ?>
                <a href="products.php?category=<?php echo $product['category_slug']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a> &gt;
            <?php else: ?>
                <a href="products.php">Products</a> &gt;
            <?php endif; ?>
            <span><?php echo htmlspecialchars($product['name']); ?></span>
        </div>
        
        <div class="product-details">
            <div class="product-gallery">
                <div class="main-image">
                    <img id="main-product-image" src="<?php echo !empty($product['image']) ? $product['image'] : 'images/products/placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                </div>
                
                <?php if (!empty($product_images)): ?>
                    <div class="thumbnail-images">
                        <div class="thumbnail active" onclick="changeImage('<?php echo $product['image']; ?>')">
                            <img src="<?php echo !empty($product['image']) ? $product['image'] : 'images/products/placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </div>
                        
                        <?php foreach ($product_images as $image): ?>
                            <div class="thumbnail" onclick="changeImage('<?php echo $image['image']; ?>')">
                                <img src="<?php echo $image['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="product-info">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <div class="product-meta">
                    <?php if ($product['category_id']): ?>
                        <span class="product-category">
                            Category: <a href="products.php?category=<?php echo $product['category_slug']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a>
                        </span>
                    <?php endif; ?>
                    
                    <span class="product-stock <?php echo $product['stock'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                        <?php echo $product['stock'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                    </span>
                </div>
                
                <div class="product-price">
                    <span class="current-price"><?php echo format_price($product['price']); ?></span>
                    
                    <?php if ($product['original_price'] > $product['price']): ?>
                        <span class="original-price"><?php echo format_price($product['original_price']); ?></span>
                        <span class="discount"><?php echo calculate_discount($product['original_price'], $product['price']); ?>% OFF</span>
                    <?php endif; ?>
                </div>
                
                <div class="product-description">
                    <?php echo $product['description']; ?>
                </div>
                
                <?php if ($product['stock'] > 0): ?>
                    <div class="product-actions">
                        <div class="quantity-selector">
                            <button class="quantity-decrease">-</button>
                            <input type="number" id="product-quantity" value="1" min="1" max="<?php echo $product['stock']; ?>">
                            <button class="quantity-increase">+</button>
                        </div>
                        
                        <button class="btn btn-primary add-to-cart" data-id="<?php echo $product['id']; ?>">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                        
                        <button class="btn btn-light add-to-wishlist" data-id="<?php echo $product['id']; ?>">
                            <i class="far fa-heart"></i> Add to Wishlist
                        </button>
                    </div>
                <?php else: ?>
                    <div class="out-of-stock-message">
                        <i class="fas fa-exclamation-circle"></i> This product is currently out of stock.
                    </div>
                <?php endif; ?>
                
                <div class="product-share">
                    <span>Share:</span>
                    <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-pinterest"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
        
        <div class="product-tabs">
            <div class="tabs-header">
                <button class="tab-btn active" data-tab="description">Description</button>
                <button class="tab-btn" data-tab="specifications">Specifications</button>
                <button class="tab-btn" data-tab="reviews">Reviews</button>
            </div>
            
            <div class="tabs-content">
                <div class="tab-pane active" id="description">
                    <?php echo $product['description']; ?>
                </div>
                
                <div class="tab-pane" id="specifications">
                    <?php if (!empty($product['specifications'])): ?>
                        <?php echo $product['specifications']; ?>
                    <?php else: ?>
                        <p>No specifications available for this product.</p>
                    <?php endif; ?>
                </div>
                
                <div class="tab-pane" id="reviews">
                    <p>No reviews yet. Be the first to review this product!</p>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="review-form">
                            <h3>Write a Review</h3>
                            <form action="submit-review.php" method="POST">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                
                                <div class="form-group">
                                    <label for="rating">Rating</label>
                                    <div class="rating-selector">
                                        <i class="fas fa-star" data-rating="1"></i>
                                        <i class="fas fa-star" data-rating="2"></i>
                                        <i class="fas fa-star" data-rating="3"></i>
                                        <i class="fas fa-star" data-rating="4"></i>
                                        <i class="fas fa-star" data-rating="5"></i>
                                    </div>
                                    <input type="hidden" name="rating" id="rating" value="5">
                                </div>
                                
                                <div class="form-group">
                                    <label for="review">Your Review</label>
                                    <textarea id="review" name="review" rows="5" required></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Submit Review</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <p><a href="login.php?redirect=<?php echo urlencode('product.php?id=' . $product['id']); ?>">Login</a> to write a review.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if (!empty($related_products)): ?>
            <div class="related-products">
                <h2>Related Products</h2>
                
                <div class="product-grid">
                    <?php foreach ($related_products as $related): ?>
                        <div class="product-card">
                            <?php if ($related['original_price'] > $related['price']): ?>
                                <div class="product-badge">
                                    <?php echo calculate_discount($related['original_price'], $related['price']); ?>% OFF
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($related['is_new']): ?>
                                <div class="product-badge new">New</div>
                            <?php endif; ?>
                            
                            <div class="product-image">
                                <a href="product.php?id=<?php echo $related['id']; ?>">
                                    <img src="<?php echo !empty($related['image']) ? $related['image'] : 'images/products/placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($related['name']); ?>">
                                </a>
                                <div class="product-actions">
                                    <button class="quick-view" data-id="<?php echo $related['id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="add-to-wishlist" data-id="<?php echo $related['id']; ?>">
                                        <i class="far fa-heart"></i>
                                    </button>
                                    <button class="add-to-cart" data-id="<?php echo $related['id']; ?>">
                                        <i class="fas fa-shopping-cart"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="product-info">
                                <h3>
                                    <a href="product.php?id=<?php echo $related['id']; ?>">
                                        <?php echo htmlspecialchars($related['name']); ?>
                                    </a>
                                </h3>
                                <div class="product-price">
                                    <span class="current-price"><?php echo format_price($related['price']); ?></span>
                                    <?php if ($related['original_price'] > $related['price']): ?>
                                        <span class="original-price"><?php echo format_price($related['original_price']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        function changeImage(src) {
            document.getElementById('main-product-image').src = src;
            
            const thumbnails = document.querySelectorAll('.thumbnail');
            thumbnails.forEach(thumb => {
                thumb.classList.remove('active');
                if (thumb.querySelector('img').src.includes(src)) {
                    thumb.classList.add('active');
                }
            });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-btn');
            const tabPanes = document.querySelectorAll('.tab-pane');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabPanes.forEach(pane => pane.classList.remove('active'));
                    
                    this.classList.add('active');
                    document.getElementById(this.getAttribute('data-tab')).classList.add('active');
                });
            });
            
            const quantityDecrease = document.querySelector('.quantity-decrease');
            const quantityIncrease = document.querySelector('.quantity-increase');
            const quantityInput = document.getElementById('product-quantity');
            
            if (quantityDecrease && quantityIncrease && quantityInput) {
                quantityDecrease.addEventListener('click', function() {
                    let value = parseInt(quantityInput.value);
                    if (value > 1) {
                        quantityInput.value = value - 1;
                    }
                });
                
                quantityIncrease.addEventListener('click', function() {
                    let value = parseInt(quantityInput.value);
                    let max = parseInt(quantityInput.getAttribute('max'));
                    if (value < max) {
                        quantityInput.value = value + 1;
                    }
                });
                
                quantityInput.addEventListener('change', function() {
                    let value = parseInt(this.value);
                    let max = parseInt(this.getAttribute('max'));
                    
                    if (value < 1) {
                        this.value = 1;
                    } else if (value > max) {
                        this.value = max;
                    }
                });
            }
            
            const addToCartBtn = document.querySelector('.product-actions .add-to-cart');
            
            if (addToCartBtn) {
                addToCartBtn.addEventListener('click', function() {
                    const productId = this.getAttribute('data-id');
                    const quantity = parseInt(document.getElementById('product-quantity').value);
                    
                    const productName = document.querySelector('.product-info h1').textContent;
                    const productPrice = parseFloat(document.querySelector('.current-price').textContent.replace('$', ''));
                    const productImage = document.getElementById('main-product-image').src;
                    
                    let cart = JSON.parse(localStorage.getItem('cart')) || [];
                    
                    const existingProductIndex = cart.findIndex(item => item.id === parseInt(productId));
                    
                    if (existingProductIndex > -1) {
                        cart[existingProductIndex].quantity += quantity;
                    } else {
                        cart.push({
                            id: parseInt(productId),
                            name: productName,
                            price: productPrice,
                            image: productImage,
                            quantity: quantity
                        });
                    }
                    
                    localStorage.setItem('cart', JSON.stringify(cart));
                    
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) {
                        const totalItems = cart.reduce((total, item) => total + item.quantity, 0);
                        cartCount.textContent = totalItems;
                    }
                    
                    alert(`${quantity} × ${productName} added to cart!`);
                });
            }
            
            const ratingStars = document.querySelectorAll('.rating-selector i');
            const ratingInput = document.getElementById('rating');
            
            if (ratingStars.length > 0 && ratingInput) {
                ratingStars.forEach(star => {
                    star.addEventListener('click', function() {
                        const rating = parseInt(this.getAttribute('data-rating'));
                        ratingInput.value = rating;
                        
                        ratingStars.forEach((s, index) => {
                            if (index < rating) {
                                s.classList.add('active');
                            } else {
                                s.classList.remove('active');
                            }
                        });
                    });
                    
                    star.addEventListener('mouseover', function() {
                        const rating = parseInt(this.getAttribute('data-rating'));
                        
                        ratingStars.forEach((s, index) => {
                            if (index < rating) {
                                s.classList.add('hover');
                            } else {
                                s.classList.remove('hover');
                            }
                        });
                    });
                });
                
                document.querySelector('.rating-selector').addEventListener('mouseout', function() {
                    ratingStars.forEach(s => {
                        s.classList.remove('hover');
                    });
                    
                    const rating = parseInt(ratingInput.value);
                    ratingStars.forEach((s, index) => {
                        if (index < rating) {
                            s.classList.add('active');
                        } else {
                            s.classList.remove('active');
                        }
                    });
                });
            }
        });
    </script>
    <script src="js/header-scroll.js"></script>
</body>
</html>
