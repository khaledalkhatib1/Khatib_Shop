<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$category_slug = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$min_price = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? floatval($_GET['max_price']) : 10000;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 12;

$query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id";
$count_query = "SELECT COUNT(*) as total FROM products p LEFT JOIN categories c ON p.category_id = c.id";

$where_clauses = [];
$params = [];
$param_types = "";

if (!empty($category_slug)) {
    $where_clauses[] = "c.slug = ?";
    $params[] = $category_slug;
    $param_types .= "s";
}

if (!empty($search)) {
    $where_clauses[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "ss";
}

$where_clauses[] = "p.price BETWEEN ? AND ?";
$params[] = $min_price;
$params[] = $max_price;
$param_types .= "dd";

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
    $count_query .= " WHERE " . implode(" AND ", $where_clauses);
}

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

$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_products = $row['total'];
$total_pages = ceil($total_products / $per_page);

if ($page < 1) $page = 1;
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;

$offset = ($page - 1) * $per_page;
$query .= " LIMIT ?, ?";
$params[] = $offset;
$params[] = $per_page;
$param_types .= "ii";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

$categories = [];
$cat_result = $conn->query("SELECT * FROM categories ORDER BY name");
if ($cat_result) {
    while ($row = $cat_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

$price_range = [0, 10000];
$price_result = $conn->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM products");
if ($price_result && $row = $price_result->fetch_assoc()) {
    $price_range = [$row['min_price'], $row['max_price']];
}

$category_name = "All Products";
if (!empty($category_slug)) {
    $stmt = $conn->prepare("SELECT name FROM categories WHERE slug = ?");
    $stmt->bind_param("s", $category_slug);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $category_name = $result->fetch_assoc()['name'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category_name); ?> - ElectroShop</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/products.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="breadcrumbs">
            <a href="index.php">Home</a> &gt;
            <?php if (!empty($category_slug)): ?>
                <a href="products.php">Products</a> &gt;
                <span><?php echo htmlspecialchars($category_name); ?></span>
            <?php else: ?>
                <span>Products</span>
            <?php endif; ?>
        </div>
        
        <div class="products-container">
            <div class="filter-sidebar">
                <div class="filter-header">
                    <h3>Filters</h3>
                    <button id="close-filters" class="mobile-only"><i class="fas fa-times"></i></button>
                </div>
                
                <div class="filter-section">
                    <h4>Categories</h4>
                    <ul class="category-filter">
                        <li>
                            <a href="products.php" class="<?php echo empty($category_slug) ? 'active' : ''; ?>">All Products</a>
                        </li>
                        <?php foreach ($categories as $category): ?>
                            <li>
                                <a href="products.php?category=<?php echo $category['slug']; ?>" class="<?php echo $category_slug === $category['slug'] ? 'active' : ''; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="filter-section">
                    <h4>Price Range</h4>
                    <form action="products.php" method="GET" class="price-filter">
                        <?php if (!empty($category_slug)): ?>
                            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_slug); ?>">
                        <?php endif; ?>
                        <?php if (!empty($search)): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <?php endif; ?>
                        <?php if (!empty($sort)): ?>
                            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                        <?php endif; ?>
                        
                        <div class="price-slider-container">
                            <div class="price-inputs">
                                <div class="price-input">
                                    <label for="min_price">Min</label>
                                    <input type="number" id="min_price" name="min_price" value="<?php echo $min_price; ?>" min="<?php echo $price_range[0]; ?>" max="<?php echo $price_range[1]; ?>">
                                </div>
                                <div class="price-input">
                                    <label for="max_price">Max</label>
                                    <input type="number" id="max_price" name="max_price" value="<?php echo $max_price; ?>" min="<?php echo $price_range[0]; ?>" max="<?php echo $price_range[1]; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">Apply Filter</button>
                    </form>
                </div>
                
                <div class="filter-section">
                    <h4>Product Status</h4>
                    <div class="checkbox-filter">
                        <label>
                            <input type="checkbox" name="on_sale" value="1" <?php echo isset($_GET['on_sale']) ? 'checked' : ''; ?>>
                            On Sale
                        </label>
                        <label>
                            <input type="checkbox" name="in_stock" value="1" <?php echo isset($_GET['in_stock']) ? 'checked' : ''; ?>>
                            In Stock
                        </label>
                        <label>
                            <input type="checkbox" name="featured" value="1" <?php echo isset($_GET['featured']) ? 'checked' : ''; ?>>
                            Featured
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="products-content">
                <div class="products-header">
                    <h1><?php echo htmlspecialchars($category_name); ?></h1>
                    
                    <div class="mobile-only">
                        <button id="show-filters" class="btn btn-light"><i class="fas fa-filter"></i> Filters</button>
                    </div>
                    
                    <div class="products-sort">
                        <label for="sort">Sort by:</label>
                        <select id="sort" onchange="window.location.href=this.value">
                            <option value="<?php echo build_query_string(['sort' => 'newest']); ?>" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="<?php echo build_query_string(['sort' => 'oldest']); ?>" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="<?php echo build_query_string(['sort' => 'price_low']); ?>" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="<?php echo build_query_string(['sort' => 'price_high']); ?>" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="<?php echo build_query_string(['sort' => 'name_asc']); ?>" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name: A to Z</option>
                            <option value="<?php echo build_query_string(['sort' => 'name_desc']); ?>" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name: Z to A</option>
                        </select>
                    </div>
                    
                    <div class="products-count">
                        Showing <?php echo count($products); ?> of <?php echo $total_products; ?> products
                    </div>
                </div>
                
                <?php if (empty($products)): ?>
                    <div class="no-products">
                        <i class="fas fa-search"></i>
                        <h2>No products found</h2>
                        <p>Try adjusting your search or filter to find what you're looking for.</p>
                        <a href="products.php" class="btn btn-primary">View All Products</a>
                    </div>
                <?php else: ?>
                    <div class="product-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <?php if ($product['is_new']): ?>
                                    <div class="product-badge new">New</div>
                                <?php elseif ($product['featured']): ?>
                                    <div class="product-badge featured">Featured</div>
                                <?php elseif ($product['on_sale']): ?>
                                    <div class="product-badge">
                                        <?php echo calculate_discount($product['original_price'], $product['price']); ?>% OFF
                                    </div>
                                <?php endif; ?>
                                
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
                                <a href="<?php echo build_query_string(['page' => $page - 1]); ?>" class="page-link prev">
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
                                <a href="<?php echo build_query_string(['page' => 1]); ?>" class="page-link">1</a>
                                <?php if ($start_page > 2): ?>
                                    <span class="page-ellipsis">...</span>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <a href="<?php echo build_query_string(['page' => $i]); ?>" class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($end_page < $total_pages): ?>
                                <?php if ($end_page < $total_pages - 1): ?>
                                    <span class="page-ellipsis">...</span>
                                <?php endif; ?>
                                <a href="<?php echo build_query_string(['page' => $total_pages]); ?>" class="page-link"><?php echo $total_pages; ?></a>
                            <?php endif; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="<?php echo build_query_string(['page' => $page + 1]); ?>" class="page-link next">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
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
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile filters toggle
            const showFiltersBtn = document.getElementById('show-filters');
            const closeFiltersBtn = document.getElementById('close-filters');
            const filterSidebar = document.querySelector('.filter-sidebar');
            
            if (showFiltersBtn && closeFiltersBtn && filterSidebar) {
                showFiltersBtn.addEventListener('click', function() {
                    filterSidebar.style.transform = 'translateX(0)';
                    document.body.style.overflow = 'hidden';
                });
                
                closeFiltersBtn.addEventListener('click', function() {
                    filterSidebar.style.transform = 'translateX(-100%)';
                    document.body.style.overflow = '';
                });
            }
            
            // Quick View functionality
            const quickViewButtons = document.querySelectorAll('.quick-view');
            const modal = document.getElementById('quickViewModal');
            const closeModal = document.querySelector('.close-modal');
            
            if (quickViewButtons.length > 0 && modal) {
                quickViewButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const productId = this.getAttribute('data-id');
                        
                        // Fetch product details via AJAX
                        fetch(`get-product.php?id=${productId}`)
                            .then(response => response.json())
                            .then(product => {
                                // Update modal content
                                document.getElementById('modalProductName').textContent = product.name;
                                document.getElementById('modalProductPrice').textContent = `$${product.price}`;
                                document.getElementById('modalProductImage').src = product.image || 'images/products/placeholder.jpg';
                                document.getElementById('modalProductDescription').innerHTML = product.description;
                                document.getElementById('modalAddToCart').setAttribute('data-id', product.id);
                                document.getElementById('modalViewDetails').href = `product.php?id=${product.id}`;
                                
                                if (product.original_price > product.price) {
                                    document.getElementById('modalProductOriginalPrice').textContent = `$${product.original_price}`;
                                    document.getElementById('modalProductDiscount').textContent = `${Math.round((product.original_price - product.price) / product.original_price * 100)}% OFF`;
                                    document.getElementById('modalProductOriginalPrice').style.display = 'inline';
                                    document.getElementById('modalProductDiscount').style.display = 'inline';
                                } else {
                                    document.getElementById('modalProductOriginalPrice').style.display = 'none';
                                    document.getElementById('modalProductDiscount').style.display = 'none';
                                }
                                
                                // Show modal
                                modal.style.display = 'block';
                            })
                            .catch(error => {
                                console.error('Error fetching product details:', error);
                            });
                    });
                });
                
                // Close modal when X is clicked
                if (closeModal) {
                    closeModal.addEventListener('click', function() {
                        modal.style.display = 'none';
                    });
                }
                
                // Close modal when clicking outside the content
                window.addEventListener('click', function(event) {
                    if (event.target === modal) {
                        modal.style.display = 'none';
                    }
                });
            }
            
            // Add to cart functionality
            const addToCartButtons = document.querySelectorAll('.add-to-cart');
            
            addToCartButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.getAttribute('data-id');
                    const productCard = this.closest('.product-card');
                    const productName = productCard.querySelector('h3').textContent.trim();
                    const productPrice = productCard.querySelector('.current-price').textContent.replace('$', '');
                    const productImage = productCard.querySelector('img').src;
                    
                    // Add to cart
                    addToCart(productId, productName, productPrice, productImage, 1);
                });
            });
            
            // Add to cart from modal
            const modalAddToCartBtn = document.getElementById('modalAddToCart');
            
            if (modalAddToCartBtn) {
                modalAddToCartBtn.addEventListener('click', function() {
                    const productId = this.getAttribute('data-id');
                    const productName = document.getElementById('modalProductName').textContent;
                    const productPrice = document.getElementById('modalProductPrice').textContent.replace('$', '');
                    const productImage = document.getElementById('modalProductImage').src;
                    const quantity = parseInt(document.getElementById('quantity').value);
                    
                    // Add to cart
                    addToCart(productId, productName, productPrice, productImage, quantity);
                    
                    // Close modal
                    modal.style.display = 'none';
                });
            }
            
            // Quantity selector in modal
            const quantityDecrease = document.querySelector('.quantity-decrease');
            const quantityIncrease = document.querySelector('.quantity-increase');
            const quantityInput = document.getElementById('quantity');
            
            if (quantityDecrease && quantityIncrease && quantityInput) {
                quantityDecrease.addEventListener('click', function() {
                    let value = parseInt(quantityInput.value);
                    if (value > 1) {
                        quantityInput.value = value - 1;
                    }
                });
                
                quantityIncrease.addEventListener('click', function() {
                    let value = parseInt(quantityInput.value);
                    quantityInput.value = value + 1;
                });
            }
            
            // Helper function to add product to cart
            function addToCart(id, name, price, image, quantity) {
                // Get existing cart from localStorage
                let cart = JSON.parse(localStorage.getItem('cart')) || [];
                
                // Check if product already in cart
                const existingProductIndex = cart.findIndex(item => item.id === parseInt(id));
                
                if (existingProductIndex > -1) {
                    // Update quantity if product already in cart
                    cart[existingProductIndex].quantity += quantity;
                } else {
                    // Add new product to cart
                    cart.push({
                        id: parseInt(id),
                        name: name,
                        price: parseFloat(price),
                        image: image,
                        quantity: quantity
                    });
                }
                
                // Save cart to localStorage
                localStorage.setItem('cart', JSON.stringify(cart));
                
                // Update cart count in header
                const cartCount = document.querySelector('.cart-count');
                if (cartCount) {
                    const totalItems = cart.reduce((total, item) => total + item.quantity, 0);
                    cartCount.textContent = totalItems;
                }
                
                // Show success message
                alert(`${quantity} × ${name} added to cart!`);
            }
        });
    </script>
    <script src="js/header-scroll.js"></script>
</body>
</html>
