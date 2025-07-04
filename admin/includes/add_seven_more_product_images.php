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
    if (strpos($text, 'Sony') !== false) {
        imagestring($image, $font_size, $x, $y - 60, "SONY", $text_color_resource);
    } else if (strpos($text, 'TCL') !== false) {
        imagestring($image, $font_size, $x, $y - 60, "TCL", $text_color_resource);
    } else if (strpos($text, 'Hisense') !== false) {
        imagestring($image, $font_size, $x, $y - 60, "HISENSE", $text_color_resource);
    } else if (strpos($text, 'SAKO') !== false) {
        imagestring($image, $font_size, $x, $y - 60, "SAKO SOLAR", $text_color_resource);
    } else if (strpos($text, 'ElectroShop') !== false) {
        imagestring($image, $font_size, $x, $y - 60, "ELECTROSHOP ORIGINAL", $text_color_resource);
    } else if (strpos($text, 'Samsung') !== false) {
        imagestring($image, $font_size, $x, $y - 60, "SAMSUNG", $text_color_resource);
    } else if (strpos($text, 'Philips') !== false) {
        imagestring($image, $font_size, $x, $y - 60, "PHILIPS", $text_color_resource);
    }
    
    // Add product name (split into multiple lines if too long)
    $words = explode(' ', $text);
    $line = '';
    $line_y = $y - 20;
    
    foreach ($words as $word) {
        $test_line = $line . ' ' . $word;
        if (strlen($test_line) > 30) {
            imagestring($image, $font_size, $x, $line_y, $line, $text_color_resource);
            $line = $word;
            $line_y += 20;
        } else {
            $line = $test_line;
        }
    }
    
    // Output the last line
    imagestring($image, $font_size, $x, $line_y, $line, $text_color_resource);
    
    // Save image
    imagejpeg($image, $filename, 90);
    imagedestroy($image);
    
    echo "Created placeholder image: $filename<br>";
}

// Create placeholder images for new specific products
$products = [
    'sony-bravia-xr-oled' => 'Sony BRAVIA XR OLED 4K TV',
    'tcl-6-series-qled' => 'TCL 6-Series QLED Roku TV',
    'hisense-u8h-mini-led' => 'Hisense U8H Mini-LED TV',
    'sako-lithium-battery-200ah' => 'SAKO Solar Deep Cycle Lithium Battery 200AH/24V',
    'electroshop-wall-oven' => 'ElectroShop Electric Wall Oven',
    'samsung-combination-wall-oven' => 'Samsung Combination Microwave Wall Oven',
    'philips-oled807-ambilight' => 'Philips OLED807 with Ambilight'
];

// Different background colors for different brands/categories
$colors = [
    'sony-bravia-xr-oled' => ['#000000', '#ffffff'], // Sony black
    'tcl-6-series-qled' => ['#e50000', '#ffffff'], // TCL red
    'hisense-u8h-mini-led' => ['#ed1c24', '#ffffff'], // Hisense red
    'sako-lithium-battery-200ah' => ['#2ecc71', '#ffffff'], // Green for solar
    'electroshop-wall-oven' => ['#6c5ce7', '#ffffff'], // ElectroShop purple
    'samsung-combination-wall-oven' => ['#1428a0', '#ffffff'], // Samsung blue
    'philips-oled807-ambilight' => ['#0e5aa7', '#ffffff'] // Philips blue
];

foreach ($products as $slug => $name) {
    $filename = "../images/products/{$slug}.jpg";
    $bg_color = $colors[$slug][0];
    $text_color = $colors[$slug][1];
    create_placeholder_image($filename, 800, 800, $name, $bg_color, $text_color);
}

// Create category image if it doesn't exist
$categories = [
    'solar-renewable' => 'Solar & Renewable Energy'
];

$category_colors = [
    'solar-renewable' => ['#27ae60', '#ffffff'] // Green
];

foreach ($categories as $slug => $name) {
    $filename = "../images/categories/{$slug}.jpg";
    $bg_color = $category_colors[$slug][0];
    $text_color = $category_colors[$slug][1];
    create_placeholder_image($filename, 600, 400, $name, $bg_color, $text_color);
}

echo "<p>All placeholder images for the 7 additional products have been created successfully!</p>";
echo "<p>Note: For a production site, replace these placeholder images with actual product photos.</p>";
?>
