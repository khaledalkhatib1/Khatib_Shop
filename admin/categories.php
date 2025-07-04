<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is admin
require_admin();

// Handle category deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $category_id = $_GET['delete'];
    
    // Get category image path
    $stmt = $conn->prepare("SELECT image FROM categories WHERE id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $category = $result->fetch_assoc();
        
        // Delete category from database
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $category_id);
        
        if ($stmt->execute()) {
            // Delete category image if it exists
            if (!empty($category['image']) && file_exists('../' . $category['image'])) {
                unlink('../' . $category['image']);
            }
            
            // Update products to remove category_id
            $stmt = $conn->prepare("UPDATE products SET category_id = NULL WHERE category_id = ?");
            $stmt->bind_param("i", $category_id);
            $stmt->execute();
            
            // Redirect to categories page with success message
            header('Location: categories.php?success=Category deleted successfully.');
            exit;
        } else {
            // Redirect to categories page with error message
            header('Location: categories.php?error=Failed to delete category.');
            exit;
        }
    } else {
        // Redirect to categories page with error message
        header('Location: categories.php?error=Category not found.');
        exit;
    }
}

// Get filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 10;

// Build query
$query = "SELECT * FROM categories";
$count_query = "SELECT COUNT(*) as total FROM categories";

$where_clauses = [];
$params = [];
$param_types = "";

// Add search filter
if (!empty($search)) {
    $where_clauses[] = "(name LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "ss";
}

// Combine where clauses
if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
    $count_query .= " WHERE " . implode(" AND ", $where_clauses);
}

// Add sorting
switch ($sort) {
    case 'name_desc':
        $query .= " ORDER BY name DESC";
        break;
    case 'newest':
        $query .= " ORDER BY id DESC";
        break;
    case 'oldest':
        $query .= " ORDER BY id ASC";
        break;
    case 'name_asc':
    default:
        $query .= " ORDER BY name ASC";
        break;
}

// Get total categories count
$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_categories = $row['total'];
$total_pages = ceil($total_categories / $per_page);

// Ensure page is within valid range
if ($page < 1) $page = 1;
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;

// Add pagination
$offset = ($page - 1) * $per_page;
$query .= " LIMIT ?, ?";
$params[] = $offset;
$params[] = $per_page;
$param_types .= "ii";

// Get categories
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - ElectroShop Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1>Manage Categories</h1>
                <a href="add-category.php" class="btn btn-primary">Add New Category</a>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            
            <div class="admin-filters">
                <form action="categories.php" method="GET" class="filter-form">
                    <div class="filter-group">
                        <input type="text" name="search" placeholder="Search categories..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <select name="sort">
                            <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                            <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn">Apply Filters</button>
                    <a href="categories.php" class="btn btn-secondary">Clear Filters</a>
                </form>
            </div>
            
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Products</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No categories found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $category): ?>
                                <?php
                                // Get product count for this category
                                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
                                $stmt->bind_param("i", $category['id']);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $product_count = $result->fetch_assoc()['count'];
                                ?>
                                <tr>
                                    <td><?php echo $category['id']; ?></td>
                                    <td>
                                        <img src="<?php echo !empty($category['image']) ? '../' . $category['image'] : '../images/categories/placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" class="product-thumbnail">
                                    </td>
                                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                                    <td><?php echo htmlspecialchars($category['slug']); ?></td>
                                    <td><?php echo $product_count; ?></td>
                                    <td class="actions">
                                        <a href="../products.php?category=<?php echo $category['slug']; ?>" class="btn btn-sm" title="View Products" target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit-category.php?id=<?php echo $category['id']; ?>" class="btn btn-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $category['id']; ?>, '<?php echo addslashes($category['name']); ?>')" class="btn btn-sm btn-danger" title="Delete">
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
            if (confirm(`Are you sure you want to delete "${name}"? This will remove the category from all products. This action cannot be undone.`)) {
                window.location.href = `categories.php?delete=${id}`;
            }
        }
    </script>
    
    <script src="../js/admin.js"></script>
</body>
</html>
