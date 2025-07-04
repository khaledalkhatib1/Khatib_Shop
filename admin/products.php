<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';


require_admin();

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = $_GET['delete'];
    
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        
        if ($stmt->execute()) {
            if (!empty($product['image']) && file_exists('../' . $product['image'])) {
                unlink('../' . $product['image']);
            }
            
            $stmt = $conn->prepare("SELECT image FROM product_images WHERE product_id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                if (!empty($row['image']) && file_exists('../' . $row['image'])) {
                    unlink('../' . $row['image']);
                }
            }
            
            $stmt = $conn->prepare("DELETE FROM product_images WHERE product_id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            
            header('Location: products.php?success=Product deleted successfully.');
            exit;
        } else {
            header('Location: products.php?error=Failed to delete product.');
            exit;
        }
    } else {
        header('Location: products.php?error=Product not found.');
        exit;
    }
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 10;

$query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id";
$count_query = "SELECT COUNT(*) as total FROM products p";

$where_clauses = [];
$params = [];
$param_types = "";

if (!empty($search)) {
    $where_clauses[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "ss";
}

if (!empty($category)) {
    $where_clauses[] = "p.category_id = ?";
    $params[] = $category;
    $param_types .= "i";
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
    $count_query .= " WHERE " . implode(" AND ", $where_clauses);
}

// Add sorting
switch ($sort) {
    case 'name_asc':
        $query .= " ORDER BY p.name ASC";
        break;
    case 'name_desc':
        $query .= " ORDER BY p.name DESC";
        break;
    case 'price_low':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'stock_low':
        $query .= " ORDER BY p.stock ASC";
        break;
    case 'stock_high':
        $query .= " ORDER BY p.stock DESC";
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
$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_products = $row['total'];
$total_pages = ceil($total_products / $per_page);

// Ensure page is within valid range
if ($page < 1) $page = 1;
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;

// Add pagination
$offset = ($page - 1) * $per_page;
$query .= " LIMIT ?, ?";
$params[] = $offset;
$params[] = $per_page;
$param_types .= "ii";

// Get products
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

// Get categories for filter
$categories = [];
$cat_result = $conn->query("SELECT * FROM categories ORDER BY name");
if ($cat_result) {
    while ($row = $cat_result->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - ElectroShop Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1>Manage Products</h1>
                <a href="add-product.php" class="btn btn-primary">Add New Product</a>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            
            <div class="admin-filters">
                <form action="products.php" method="GET" class="filter-form">
                    <div class="filter-group">
                        <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <select name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <select name="sort">
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                            <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                            <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price (Low to High)</option>
                            <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price (High to Low)</option>
                            <option value="stock_low" <?php echo $sort === 'stock_low' ? 'selected' : ''; ?>>Stock (Low to High)</option>
                            <option value="stock_high" <?php echo $sort === 'stock_high' ? 'selected' : ''; ?>>Stock (High to Low)</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn">Apply Filters</button>
                    <a href="products.php" class="btn btn-secondary">Clear Filters</a>
                </form>
            </div>
            
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Featured</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No products found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo $product['id']; ?></td>
                                    <td>
                                        <img src="<?php echo !empty($product['image']) ? '../' . $product['image'] : '../images/products/placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-thumbnail">
                                    </td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?: 'Uncategorized'); ?></td>
                                    <td><?php echo format_price($product['price']); ?></td>
                                    <td>
                                        <span class="stock-badge <?php echo $product['stock'] <= 5 ? 'critical' : ($product['stock'] <= 10 ? 'low' : ''); ?>">
                                            <?php echo $product['stock']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $product['featured'] ? 'badge-success' : 'badge-secondary'; ?>">
                                            <?php echo $product['featured'] ? 'Yes' : 'No'; ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="../product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm" title="View" target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>')" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="page-link">
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
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" class="page-link">1</a>
                        <?php if ($start_page > 2): ?>
                            <span class="page-ellipsis">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <span class="page-ellipsis">...</span>
                        <?php endif; ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>" class="page-link"><?php echo $total_pages; ?></a>
                    <?php endif; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="page-link">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function confirmDelete(id, name) {
            if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
                window.location.href = `products.php?delete=${id}`;
            }
        }
    </script>
    
    <script src="../js/admin.js"></script>
</body>
</html>
