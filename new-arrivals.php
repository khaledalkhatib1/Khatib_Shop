<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get filter parameters
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 12;

// Build query
$query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.is_new = 1";
$count_query = "SELECT COUNT(*) as total FROM products WHERE is_new = 1";

// Add sorting
switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'name_asc':
        $query .= " ORDER BY p.name ASC";
        break;
    case 'name_desc':
        $query .= " ORDER BY p.name DESC";
        break;
    case 'oldest':
        $query .= " ORDER BY p.id ASC";
        break;
    case 'newest':
    default:
        $query .= " ORDER BY p.id DESC";
        break;
}

// Get total products count
$result = $conn->query($count_query);
$row = $result->fetch_assoc();
$total_products = $row['total'];
$total_pages = ceil($total_products / $per_page);

// Ensure page is within valid range
if ($page < 1) $page = 1;
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;

// Add pagination
$offset = ($page - 1) * $per_page;
$query .= " LIMIT $offset, $per_page";

// Get products
$result = $conn->query($query);
$products = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Arrivals - ElectroShop</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/products.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="breadcrumbs">
            <a href="index.php">Home</a> &gt;
            <span>New Arrivals</span>
        </div>
        
        <div class="products-header">
            <h1>New Arrivals</h1>
            
            <div class="products-sort">
                <label for="sort">Sort by:</label>
                <select id="sort" onchange="window.location.href=this.value">
                    <option value="?sort=newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="?sort=oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="?sort=price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="?sort=price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                    <option value="?sort=name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name: A to Z</option>
                    <option value="?sort=name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name: Z to A</option>
                </select>
            </div>
            
            <div class="products-count">
                Showing <?php echo count($products); ?> of <?php echo $total_products; ?> products
            </div>
        </div>
        
        <?php if (empty($products)): ?>
            <div class="no-products">
                <i class="fas fa-box-open"></i>
                <h2>No new arrivals found</h2>
                <p>Check back soon for new products!</p>
                <a href="products.php" class="btn btn-primary">View All Products</a>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-badge new">New</div>
                        
                        <div class="product-image">
                            <a href="product.php?id=<?php echo $product['id']; ?>">
                                <img src="<?php echo !empty($product['image']) ? $product['image'] : 'images/products/placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            </a>
                            <div class="product-actions">
                                <button class="add-to-cart" data-id="<?php echo $product['id']; ?>"><i class="fas fa-shopping-cart"></i> Add to Cart</button>
                                <button class="quick-view" data-id="<?php echo $product['id']; ?>"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        
                        <div class="product-info">
                            <h3>
                                <a href="product.php?id=<?php echo $product['id']; ?>">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </a>
                            </h3>
                            <div class="product-price">
                                <span class="current-price"><?php echo format_price($product['price']); ?></span>
                                <?php if ($product['original_price'] > $product['price']): ?>
                                    <span class="original-price"><?php echo format_price($product['original_price']); ?></span>
                                    <span class="discount"><?php echo calculate_discount($product['original_price'], $product['price']); ?>% OFF</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-stock">
                                <?php if ($product['stock'] > 0): ?>
                                    <span class="in-stock">In Stock</span>
                                <?php else: ?>
                                    <span class="out-of-stock">Out of Stock</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?sort=<?php echo $sort; ?>&page=<?php echo $page - 1; ?>" class="page-link prev">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $start_page + 4);
                    if ($end_page - $start_page < 4) {
                        $start_page = max(1, $end_page - 4);
                    }
                    ?>
                    
                    <?php if ($start_page > 1): ?>
                        <a href="?sort=<?php echo $sort; ?>&page=1" class="page-link">1</a>
                        <?php if ($start_page > 2): ?>
                            <span class="page-ellipsis">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?sort=<?php echo $sort; ?>&page=<?php echo $i; ?>" class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <span class="page-ellipsis">...</span>
                        <?php endif; ?>
                        <a href="?sort=<?php echo $sort; ?>&page=<?php echo $total_pages; ?>" class="page-link"><?php echo $total_pages; ?></a>
                    <?php endif; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?sort=<?php echo $sort; ?>&page=<?php echo $page + 1; ?>" class="page-link next">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Quick View Modal -->
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
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="js/script.js"></script>
    <script src="js/header-scroll.js"></script>
</body>
</html>
