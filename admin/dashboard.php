<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is admin
require_admin();

// Get statistics
$stats = [
    'total_products' => 0,
    'total_orders' => 0,
    'total_users' => 0,
    'total_revenue' => 0,
    'recent_orders' => [],
    'low_stock_products' => []
];

// Get total products
$result = $conn->query("SELECT COUNT(*) as count FROM products");
if ($result && $row = $result->fetch_assoc()) {
    $stats['total_products'] = $row['count'];
}

// Get total orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders");
if ($result && $row = $result->fetch_assoc()) {
    $stats['total_orders'] = $row['count'];
}

// Get total users
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 0");
if ($result && $row = $result->fetch_assoc()) {
    $stats['total_users'] = $row['count'];
}

// Get total revenue
$result = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status != 'cancelled'");
if ($result && $row = $result->fetch_assoc()) {
    $stats['total_revenue'] = $row['total'] ?: 0;
}

// Get recent orders
$result = $conn->query("SELECT o.*, u.name as user_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $stats['recent_orders'][] = $row;
    }
}

// Get low stock products
$result = $conn->query("SELECT * FROM products WHERE stock < 10 ORDER BY stock ASC LIMIT 5");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $stats['low_stock_products'][] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ElectroShop</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1>Dashboard</h1>
                <div class="admin-user">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a href="../logout.php" class="btn btn-sm">Logout</a>
                </div>
            </div>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Products</h3>
                        <p><?php echo $stats['total_products']; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Orders</h3>
                        <p><?php echo $stats['total_orders']; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Users</h3>
                        <p><?php echo $stats['total_users']; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Revenue</h3>
                        <p><?php echo format_price($stats['total_revenue']); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-sections">
                <div class="dashboard-section">
                    <h2>Recent Orders</h2>
                    <?php if (empty($stats['recent_orders'])): ?>
                        <p>No orders found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['recent_orders'] as $order): ?>
                                        <tr>
                                            <td>#<?php echo $order['id']; ?></td>
                                            <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                            <td><?php echo format_price($order['total_amount']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="view-all">
                            <a href="orders.php" class="btn btn-sm">View All Orders</a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="dashboard-section">
                    <h2>Low Stock Products</h2>
                    <?php if (empty($stats['low_stock_products'])): ?>
                        <p>No low stock products found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['low_stock_products'] as $product): ?>
                                        <tr>
                                            <td>
                                                <div class="product-info">
                                                    <img src="<?php echo !empty($product['image']) ? '../' . $product['image'] : '../images/products/placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                                    <span><?php echo htmlspecialchars($product['name']); ?></span>
                                                </div>
                                            </td>
                                            <td><?php echo format_price($product['price']); ?></td>
                                            <td>
                                                <span class="stock-badge <?php echo $product['stock'] <= 5 ? 'critical' : 'low'; ?>">
                                                    <?php echo $product['stock']; ?> left
                                                </span>
                                            </td>
                                            <td>
                                                <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm">Edit</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="view-all">
                            <a href="products.php" class="btn btn-sm">View All Products</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../js/admin.js"></script>
</body>
</html>
