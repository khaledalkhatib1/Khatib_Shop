<?php

$directories = [
    '../images/products',
    '../images/categories'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
        echo "Created directory: $dir<br>";
    }
}

// Function to create a placeholder image
function create_placeholder_image($filename, $width, $height, $text, $bg_color, $text_color) {
    $image = imagecreatetruecolor($width, $height);
    
    // Convert hex colors to RGB
    $bg_r = hexdec(substr($bg_color, 1, 2));
    $bg_g = hexdec(substr($bg_color, 3, 2));
    $bg_b = hexdec(substr($bg_color, 5, 2));
    
    $text_r = hexdec(substr($text_color, 1, 2));
    $text_g = hexdec(substr($text_color, 3, 2));
    $text_b = hexdec(substr($text_color, 5, 2));
    
    // Fill background
    $bg_color_resource = imagecolorallocate($image, $bg_r, $bg_g, $bg_b);
    imagefill($image, 0, 0, $bg_color_resource);
    
    // Add text
    $text_color_resource = imagecolorallocate($image, $text_r, $text_g, $text_b);
    $font_size = 5;
    
    // Center text (approximate since we're not using TTF)
    $text_width = imagefontheight($font_size) * strlen($text) * 0.6;
    $text_height = imagefontheight($font_size);
    
    $x = ($width - $text_width) / 2;
    $y = ($height + $text_height) / 2;
    
    // Add brand name at the top
    if (strpos($text, 'LG') !== false) {
        imagestring($image, $font_size, $x, $y - 60, "LG", $text_color_resource);
    } else if (strpos($text, 'Samsung') !== false) {
        imagestring($image, $font_size, $x, $y - 60, "SAMSUNG", $text_color_resource);
    } else if (strpos($text, 'Hitachi') !== false) {
        imagestring($image, $font_size, $x, $y - 60, "HITACHI", $text_color_resource);
    } else if (strpos($text, 'ElectroShop') !== false) {
        imagestring($image, $font_size, $x, $y - 60, "ELECTROSHOP ORIGINAL", $text_color_resource);
    } else if (strpos($text, 'PowerCore') !== false) {
        imagestring($image, $font_size, $x, $y - 60, "POWERCORE", $text_color_resource);
    } else if (strpos($text, 'UltraCharge') !== false) {
        imagestring($image, $font_size, $x, $y - 60, "ULTRACHARGE", $text_color_resource);
    }
    
    // Add product name
    imagestring($image, $font_size, $x, $y - 20, $text, $text_color_resource);
    
    // Save image
    imagejpeg($image, $filename, 90);
    imagedestroy($image);
    
    echo "Created placeholder image: $filename<br>";
}

// Create placeholder images for new specific products
$products = [
    'lg-uhd-tv' => 'LG UHD Smart TV',
    'samsung-crystal-uhd-tv' => 'Samsung Crystal UHD TV',
    'samsung-side-by-side-fridge' => 'Samsung Side-by-Side Refrigerator',
    'lg-front-load-washer' => 'LG Front Load Washer',
    'samsung-top-load-washer' => 'Samsung Top Load Washer',
    'hitachi-vacuum-fridge' => 'Hitachi Vacuum Refrigerator',
    'powercore-20000mah' => 'PowerCore 20000mAh Battery',
    'ultracharge-30000mah' => 'UltraCharge 30000mAh Battery',
    'electroshop-electric-cooktop' => 'ElectroShop Electric Cooktop',
    'electroshop-induction-cooktop' => 'ElectroShop Induction Cooktop'
];

// Different background colors for different brands/categories
$colors = [
    'lg-uhd-tv' => ['#a50034', '#ffffff'], // LG red
    'samsung-crystal-uhd-tv' => ['#1428a0', '#ffffff'], // Samsung blue
    'samsung-side-by-side-fridge' => ['#1428a0', '#ffffff'], // Samsung blue
    'lg-front-load-washer' => ['#a50034', '#ffffff'], // LG red
    'samsung-top-load-washer' => ['#1428a0', '#ffffff'], // Samsung blue
    'hitachi-vacuum-fridge' => ['#e60027', '#ffffff'], // Hitachi red
    'powercore-20000mah' => ['#00cec9', '#333333'], // Teal
    'ultracharge-30000mah' => ['#fdcb6e', '#333333'], // Yellow
    'electroshop-electric-cooktop' => ['#6c5ce7', '#ffffff'], // ElectroShop purple
    'electroshop-induction-cooktop' => ['#6c5ce7', '#ffffff'] // ElectroShop purple
];

foreach ($products as $slug => $name) {
    $filename = "../images/products/{$slug}.jpg";
    $bg_color = $colors[$slug][0];
    $text_color = $colors[$slug][1];
    create_placeholder_image($filename, 800, 800, $name, $bg_color, $text_color);
}

// Create category images if they don't exist
$categories = [
    'washing-machines' => 'Washing Machines',
    'batteries-power' => 'Batteries & Power',
    'cooking-appliances' => 'Cooking Appliances'
];

$category_colors = [
    'washing-machines' => ['#3498db', '#ffffff'], // Blue
    'batteries-power' => ['#2ecc71', '#ffffff'], // Green
    'cooking-appliances' => ['#e74c3c', '#ffffff'] // Red
];

foreach ($categories as $slug => $name) {
    $filename = "../images/categories/{$slug}.jpg";
    $bg_color = $category_colors[$slug][0];
    $text_color = $category_colors[$slug][1];
    create_placeholder_image($filename, 600, 400, $name, $bg_color, $text_color);
}

echo "<p>All placeholder images for additional products have been created successfully!</p>";
echo "<p>Note: For a production site, replace these placeholder images with actual product photos.</p>";
?>
