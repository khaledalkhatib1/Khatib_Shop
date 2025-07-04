<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is admin
require_admin();

$error = '';
$success = '';

// Get categories for dropdown
$categories = [];
$cat_result = $conn->query("SELECT * FROM categories ORDER BY name");
if ($cat_result) {
    while ($row = $cat_result->fetch_assoc()) {
        $categories[] = $row;
    }
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
            // Check if slug already exists
            $stmt = $conn->prepare("SELECT id FROM products WHERE slug = ?");
            $stmt->bind_param("s", $slug);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'A product with this slug already exists. Please choose a different slug.';
            } else {
                // Upload product image
                $image_path = '';
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
                            $image_path = 'images/products/' . $new_file_name;
                        } else {
                            $error = 'Failed to upload image.';
                        }
                    }
                }
                
                if (empty($error)) {
                    // Insert product into database
                    $stmt = $conn->prepare("INSERT INTO products (name, slug, description, price, original_price, category_id, image, stock, featured, is_new, on_sale, specifications) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssddssiiiis", $name, $slug, $description, $price, $original_price, $category_id, $image_path, $stock, $featured, $is_new, $on_sale, $specifications);
                    
                    if ($stmt->execute()) {
                        $product_id = $conn->insert_id;
                        
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
                        
                        $success = 'Product added successfully.';
                        
                        // Clear form data
                        $name = $slug = $description = $specifications = '';
                        $price = $original_price = 0;
                        $category_id = null;
                        $stock = 0;
                        $featured = $is_new = $on_sale = 0;
                    } else {
                        $error = 'Failed to add product. Please try again.';
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
    <title>Add Product - ElectroShop Admin</title>
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
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1>Add New Product</h1>
                <a href="products.php" class="btn">Back to Products</a>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form action="add-product.php" method="POST" enctype="multipart/form-data" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Product Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="slug">Slug <span class="required">*</span></label>
                        <input type="text" id="slug" name="slug" value="<?php echo isset($slug) ? htmlspecialchars($slug) : ''; ?>" required>
                        <small>URL-friendly version of the name. Use hyphens instead of spaces.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo isset($category_id) && $category_id == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price <span class="required">*</span></label>
                        <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo isset($price) ? $price : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="original_price">Original Price</label>
                        <input type="number" id="original_price" name="original_price" step="0.01" min="0" value="<?php echo isset($original_price) ? $original_price : ''; ?>">
                        <small>Leave empty if not on sale.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="stock">Stock <span class="required">*</span></label>
                        <input type="number" id="stock" name="stock" min="0" value="<?php echo isset($stock) ? $stock : ''; ?>" required>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label>Product Status</label>
                        <div class="checkbox-options">
                            <label>
                                <input type="checkbox" name="featured" value="1" <?php echo isset($featured) && $featured ? 'checked' : ''; ?>>
                                Featured Product
                            </label>
                            <label>
                                <input type="checkbox" name="is_new" value="1" <?php echo isset($is_new) && $is_new ? 'checked' : ''; ?>>
                                New Arrival
                            </label>
                            <label>
                                <input type="checkbox" name="on_sale" value="1" <?php echo isset($on_sale) && $on_sale ? 'checked' : ''; ?>>
                                On Sale
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Main Product Image</label>
                        <input type="file" id="image" name="image" accept="image/*">
                        <small>Recommended size: 800x800 pixels. Max file size: 2MB.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="additional_images">Additional Images</label>
                        <input type="file" id="additional_images" name="additional_images[]" accept="image/*" multiple>
                        <small>You can select multiple images. Max file size: 2MB per image.</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Product Description</label>
                    <textarea id="description" name="description"><?php echo isset($description) ? $description : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="specifications">Product Specifications</label>
                    <textarea id="specifications" name="specifications"><?php echo isset($specifications) ? $specifications : ''; ?></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Add Product</button>
                    <a href="products.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Auto-generate slug from name
        document.getElementById('name').addEventListener('input', function() {
            const name = this.value;
            const slug = name.toLowerCase()
                .replace(/[^\w\s-]/g, '') // Remove special characters
                .replace(/\s+/g, '-')     // Replace spaces with hyphens
                .replace(/-+/g, '-');     // Replace multiple hyphens with single hyphen
            
            document.getElementById('slug').value = slug;
        });
    </script>
    
    <script src="../js/admin.js"></script>
</body>
</html>
