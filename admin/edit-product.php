<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is admin
require_admin();

$error = '';
$success = '';

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: products.php?error=Invalid product ID.');
    exit;
}

$product_id = $_GET['id'];

// Get product data
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: products.php?error=Product not found.');
    exit;
}

$product = $result->fetch_assoc();

// Get categories for dropdown
$categories = [];
$cat_result = $conn->query("SELECT * FROM categories ORDER BY name");
if ($cat_result) {
    while ($row = $cat_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Get product images
$product_images = [];
$stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $product_images[] = $row;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $error = 'Security validation failed. Please try again.';
    } else {
        // Get form data
        $name = sanitize_input($_POST['name']);
        $slug = sanitize_input($_POST['slug']);
        $description = $_POST['description']; // Allow HTML in description
        $price = floatval($_POST['price']);
        $original_price = !empty($_POST['original_price']) ? floatval($_POST['original_price']) : $price;
        $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
        $stock = intval($_POST['stock']);
        $featured = isset($_POST['featured']) ? 1 : 0;
        $is_new = isset($_POST['is_new']) ? 1 : 0;
        $on_sale = isset($_POST['on_sale']) ? 1 : 0;
        $specifications = $_POST['specifications']; // Allow HTML in specifications
        
        // Validate input
        if (empty($name)) {
            $error = 'Product name is required.';
        } elseif (empty($slug)) {
            $error = 'Product slug is required.';
        } elseif ($price <= 0) {
            $error = 'Price must be greater than zero.';
        } elseif ($original_price < $price) {
            $error = 'Original price cannot be less than current price.';
        } else {
            // Check if slug already exists for other products
            $stmt = $conn->prepare("SELECT id FROM products WHERE slug = ? AND id != ?");
            $stmt->bind_param("si", $slug, $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'A product with this slug already exists. Please choose a different slug.';
            } else {
                // Upload product image if provided
                $image_path = $product['image']; // Keep existing image by default
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../images/products/';
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_name = basename($_FILES['image']['name']);
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    
                    // Generate unique filename
                    $new_file_name = 'product_' . uniqid() . '.' . $file_ext;
                    $upload_path = $upload_dir . $new_file_name;
                    
                    // Check if file is an image
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                    if (!in_array($file_ext, $allowed_types)) {
                        $error = 'Only JPG, JPEG, PNG, and GIF files are allowed for the main image.';
                    } else {
                        // Move uploaded file
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                            // Delete old image if it exists
                            if (!empty($product['image']) && file_exists('../' . $product['image'])) {
                                unlink('../' . $product['image']);
                            }
                            
                            $image_path = 'images/products/' . $new_file_name;
                        } else {
                            $error = 'Failed to upload image.';
                        }
                    }
                }
                
                if (empty($error)) {
                    // Update product in database
                    $stmt = $conn->prepare("UPDATE products SET name = ?, slug = ?, description = ?, price = ?, original_price = ?, category_id = ?, image = ?, stock = ?, featured = ?, is_new = ?, on_sale = ?, specifications = ? WHERE id = ?");
                    $stmt->bind_param("sssddisiiiiis", $name, $slug, $description, $price, $original_price, $category_id, $image_path, $stock, $featured, $is_new, $on_sale, $specifications, $product_id);
                    
                    if ($stmt->execute()) {
                        // Upload additional product images
                        if (isset($_FILES['additional_images']) && is_array($_FILES['additional_images']['name'])) {
                            $upload_dir = '../images/products/';
                            
                            // Create directory if it doesn't exist
                            if (!file_exists($upload_dir)) {
                                mkdir($upload_dir, 0777, true);
                            }
                            
                            for ($i = 0; $i < count($_FILES['additional_images']['name']); $i++) {
                                if ($_FILES['additional_images']['error'][$i] === UPLOAD_ERR_OK) {
                                    $file_name = basename($_FILES['additional_images']['name'][$i]);
                                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                                    
                                    // Generate unique filename
                                    $new_file_name = 'product_' . $product_id . '_' . uniqid() . '.' . $file_ext;
                                    $upload_path = $upload_dir . $new_file_name;
                                    
                                    // Check if file is an image
                                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                                    if (in_array($file_ext, $allowed_types)) {
                                        // Move uploaded file
                                        if (move_uploaded_file($_FILES['additional_images']['tmp_name'][$i], $upload_path)) {
                                            $image_path = 'images/products/' . $new_file_name;
                                            
                                            // Insert image into product_images table
                                            $stmt = $conn->prepare("INSERT INTO product_images (product_id, image) VALUES (?, ?)");
                                            $stmt->bind_param("is", $product_id, $image_path);
                                            $stmt->execute();
                                        }
                                    }
                                }
                            }
                        }
                        
                        // Handle image deletions
                        if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
                            foreach ($_POST['delete_images'] as $image_id) {
                                // Get image path
                                $stmt = $conn->prepare("SELECT image FROM product_images WHERE id = ? AND product_id = ?");
                                $stmt->bind_param("ii", $image_id, $product_id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                
                                if ($result->num_rows > 0) {
                                    $image = $result->fetch_assoc();
                                    
                                    // Delete image file
                                    if (!empty($image['image']) && file_exists('../' . $image['image'])) {
                                        unlink('../' . $image['image']);
                                    }
                                    
                                    // Delete from database
                                    $stmt = $conn->prepare("DELETE FROM product_images WHERE id = ?");
                                    $stmt->bind_param("i", $image_id);
                                    $stmt->execute();
                                }
                            }
                        }
                        
                        $success = 'Product updated successfully.';
                        
                        // Refresh product data
                        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
                        $stmt->bind_param("i", $product_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $product = $result->fetch_assoc();
                        
                        // Refresh product images
                        $product_images = [];
                        $stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ?");
                        $stmt->bind_param("i", $product_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($row = $result->fetch_assoc()) {
                            $product_images[] = $row;
                        }
                    } else {
                        $error = 'Failed to update product. Please try again.';
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
    <title>Edit Product - ElectroShop Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#description, #specifications',
            height: 300,
            plugins: 'lists link image table code',
            toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link image | table | code',
            menubar: false
        });
    </script>
    <style>
        .image-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 15px;
        }
        
        .image-preview-item {
            position: relative;
            width: 100px;
            height: 100px;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .image-preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .image-delete {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 24px;
            height: 24px;
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1>Edit Product</h1>
                <a href="products.php" class="btn">Back to Products</a>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form action="edit-product.php?id=<?php echo $product_id; ?>" method="POST" enctype="multipart/form-data" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Product Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="slug">Slug <span class="required">*</span></label>
                        <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($product['slug']); ?>" required>
                        <small>URL-friendly version of the name. Use hyphens instead of spaces.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $product['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price <span class="required">*</span></label>
                        <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo $product['price']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="original_price">Original Price</label>
                        <input type="number" id="original_price" name="original_price" step="0.01" min="0" value="<?php echo $product['original_price']; ?>">
                        <small>Leave empty if not on sale.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="stock">Stock <span class="required">*</span></label>
                        <input type="number" id="stock" name="stock" min="0" value="<?php echo $product['stock']; ?>" required>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label>Product Status</label>
                        <div class="checkbox-options">
                            <label>
                                <input type="checkbox" name="featured" value="1" <?php echo $product['featured'] ? 'checked' : ''; ?>>
                                Featured Product
                            </label>
                            <label>
                                <input type="checkbox" name="is_new" value="1" <?php echo $product['is_new'] ? 'checked' : ''; ?>>
                                New Arrival
                            </label>
                            <label>
                                <input type="checkbox" name="on_sale" value="1" <?php echo $product['on_sale'] ? 'checked' : ''; ?>>
                                On Sale
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Main Product Image</label>
                        <?php if (!empty($product['image'])): ?>
                            <div class="current-image">
                                <img src="../<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="max-width: 200px; margin-bottom: 10px;">
                                <p>Current image. Upload a new one to replace it.</p>
                            </div>
                        <?php endif; ?>
                        <input type="file" id="image" name="image" accept="image/*">
                        <small>Recommended size: 800x800 pixels. Max file size: 2MB.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="additional_images">Additional Images</label>
                        <input type="file" id="additional_images" name="additional_images[]" accept="image/*" multiple>
                        <small>You can select multiple images. Max file size: 2MB per image.</small>
                        
                        <?php if (!empty($product_images)): ?>
                            <div class="image-preview-container">
                                <?php foreach ($product_images as $image): ?>
                                    <div class="image-preview-item">
                                        <img src="../<?php echo $image['image']; ?>" alt="Product Image">
                                        <label class="image-delete">
                                            <input type="checkbox" name="delete_images[]" value="<?php echo $image['id']; ?>" style="display: none;">
                                            <i class="fas fa-times"></i>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <small>Check the X to delete an image.</small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Product Description</label>
                    <textarea id="description" name="description"><?php echo $product['description']; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="specifications">Product Specifications</label>
                    <textarea id="specifications" name="specifications"><?php echo $product['specifications']; ?></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Product</button>
                    <a href="products.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Image delete toggle
        const imageDeleteCheckboxes = document.querySelectorAll('.image-delete input');
        const imageDeleteIcons = document.querySelectorAll('.image-delete');
        
        imageDeleteIcons.forEach(icon => {
            icon.addEventListener('click', function() {
                const checkbox = this.querySelector('input');
                checkbox.checked = !checkbox.checked;
                
                if (checkbox.checked) {
                    this.parentElement.style.opacity = '0.5';
                } else {
                    this.parentElement.style.opacity = '1';
                }
            });
        });
    </script>
    
    <script src="../js/admin.js"></script>
</body>
</html>
