
<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update profile information
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $city = trim($_POST['city']);
        $state = trim($_POST['state']);
        $zip = trim($_POST['zip']);
        $country = trim($_POST['country']);
        
        // Validate input
        if (empty($name)) {
            $error_message = 'Name is required.';
        } elseif (empty($email)) {
            $error_message = 'Email is required.';
        } else {
            // Check if email already exists (for another user)
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error_message = 'Email address is already in use.';
            } else {
                // Update user data
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ?, city = ?, state = ?, zip = ?, country = ? WHERE id = ?");
                $stmt->bind_param("ssssssssi", $name, $email, $phone, $address, $city, $state, $zip, $country, $user_id);
                
                if ($stmt->execute()) {
                    $success_message = 'Profile updated successfully.';
                    
                    // Update session data
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    
                    // Refresh user data
                    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                } else {
                    $error_message = 'Failed to update profile.';
                }
            }
        }
    } elseif (isset($_POST['change_password'])) {
        // Change password
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate input
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = 'All password fields are required.';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'New passwords do not match.';
        } elseif (strlen($new_password) < 6) {
            $error_message = 'New password must be at least 6 characters long.';
        } else {
            // Verify current password
            if (password_verify($current_password, $user['password'])) {
                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed_password, $user_id);
                
                if ($stmt->execute()) {
                    $success_message = 'Password changed successfully.';
                } else {
                    $error_message = 'Failed to change password.';
                }
            } else {
                $error_message = 'Current password is incorrect.';
            }
        }
    } elseif (isset($_POST['upload_avatar'])) {
        // Handle profile picture upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'images/avatars/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = basename($_FILES['avatar']['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Generate unique filename
            $new_file_name = 'avatar_' . $user_id . '_' . uniqid() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_file_name;
            
            // Check if file is an image
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($file_ext, $allowed_types)) {
                $error_message = 'Only JPG, JPEG, PNG, and GIF files are allowed.';
            } else {
                // Move uploaded file
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                    // Update user avatar in database
                    $avatar_path = $upload_path;
                    $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                    $stmt->bind_param("si", $avatar_path, $user_id);
                    
                    if ($stmt->execute()) {
                        $success_message = 'Profile picture updated successfully.';
                        
                        // Refresh user data
                        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $user = $result->fetch_assoc();
                    } else {
                        $error_message = 'Failed to update profile picture.';
                    }
                } else {
                    $error_message = 'Failed to upload image.';
                }
            }
        } else {
            $error_message = 'Please select an image to upload.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - ElectroShop</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1 class="page-title">My Profile</h1>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="profile-container">
            <div class="profile-sidebar">
                <div class="profile-avatar">
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="<?php echo $user['avatar']; ?>" alt="Profile Picture">
                    <?php else: ?>
                        <div class="avatar-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                    
                    <form action="profile.php" method="POST" enctype="multipart/form-data" class="avatar-form">
                        <label for="avatar" class="btn btn-sm">Change Picture</label>
                        <input type="file" id="avatar" name="avatar" accept="image/*" style="display: none;">
                        <button type="submit" name="upload_avatar" id="upload-avatar-btn" style="display: none;">Upload</button>
                    </form>
                </div>
                
                <div class="profile-menu">
                    <ul>
                        <li class="active"><a href="#profile-info" data-tab="profile-info">Profile Information</a></li>
                        <li><a href="#change-password" data-tab="change-password">Change Password</a></li>
                        <li><a href="#order-history" data-tab="order-history">Order History</a></li>
                        <li><a href="#wishlist" data-tab="wishlist">Wishlist</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="profile-content">
                <div class="profile-tab active" id="profile-info">
                    <h2>Profile Information</h2>
                    
                    <form action="profile.php" method="POST" class="profile-form">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Address</label>
                                <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="city">City</label>
                                <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="state">State/Province</label>
                                <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($user['state'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="zip">ZIP/Postal Code</label>
                                <input type="text" id="zip" name="zip" value="<?php echo htmlspecialchars($user['zip'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="country">Country</label>
                                <select id="country" name="country">
                                    <option value="">Select Country</option>
                                    <option value="US" <?php echo (isset($user['country']) && $user['country'] === 'US') ? 'selected' : ''; ?>>Lebanon</option>
                                    <option value="CA" <?php echo (isset($user['country']) && $user['country'] === 'CA') ? 'selected' : ''; ?>>Syria</option>
                                    <option value="UK" <?php echo (isset($user['country']) && $user['country'] === 'UK') ? 'selected' : ''; ?>>Jordan</option>
                                    <option value="AU" <?php echo (isset($user['country']) && $user['country'] === 'AU') ? 'selected' : ''; ?>>Dubai</option>
                                    <!-- Add more countries as needed -->
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
                
                <div class="profile-tab" id="change-password">
                    <h2>Change Password</h2>
                    
                    <form action="profile.php" method="POST" class="password-form">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required>
                            <small>Password must be at least 6 characters long.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                        </div>
                    </form>
                </div>
                
                <div class="profile-tab" id="order-history">
                    <h2>Order History</h2>
                    
                    <?php
                    // Get user orders
                    $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $orders = [];
                    
                    while ($row = $result->fetch_assoc()) {
                        $orders[] = $row;
                    }
                    ?>
                    
                    <?php if (empty($orders)): ?>
                        <div class="empty-state">
                            <i class="fas fa-shopping-bag"></i>
                            <p>You haven't placed any orders yet.</p>
                            <a href="products.php" class="btn btn-primary">Start Shopping</a>
                        </div>
                    <?php else: ?>
                        <div class="orders-table-container">
                            <table class="orders-table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Date</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo $order['id']; ?></td>
                                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                            <td><?php echo format_price($order['total_amount']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm">View Details</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="profile-tab" id="wishlist">
                    <h2>My Wishlist</h2>
                    
                    <div class="empty-state">
                        <i class="fas fa-heart"></i>
                        <p>Your wishlist is empty.</p>
                        <a href="products.php" class="btn btn-primary">Browse Products</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab navigation
            const tabLinks = document.querySelectorAll('.profile-menu a');
            const tabContents = document.querySelectorAll('.profile-tab');
            
            tabLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Remove active class from all links and tabs
                    tabLinks.forEach(l => l.parentElement.classList.remove('active'));
                    tabContents.forEach(tab => tab.classList.remove('active'));
                    
                    // Add active class to clicked link
                    this.parentElement.classList.add('active');
                    
                    // Show corresponding tab
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                });
            });
            
            // Profile picture upload
            const avatarInput = document.getElementById('avatar');
            const uploadButton = document.getElementById('upload-avatar-btn');
            
            if (avatarInput) {
                avatarInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        // Show preview
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const avatarImg = document.querySelector('.profile-avatar img');
                            if (avatarImg) {
                                avatarImg.src = e.target.result;
                            } else {
                                const placeholder = document.querySelector('.avatar-placeholder');
                                if (placeholder) {
                                    placeholder.innerHTML = `<img src="${e.target.result}" alt="Profile Picture">`;
                                }
                            }
                        }
                        reader.readAsDataURL(this.files[0]);
                        
                        // Trigger upload
                        uploadButton.click();
                    }
                });
            }
        });
    </script>
    <script src="js/header-scroll.js"></script>
</body>
</html>