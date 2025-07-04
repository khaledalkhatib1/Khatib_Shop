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
    'lg-oled-tv' => 'LG OLED TV',
    'samsung-qled-tv' => 'Samsung QLED TV',
    'lg-instaview-fridge' => 'LG InstaView Refrigerator',
    'samsung-family-hub-fridge' => 'Samsung Family Hub Refrigerator',
    'hitachi-fridge' => 'Hitachi French Door Refrigerator',
    'electroshop-microwave' => 'ElectroShop Microwave',
    'electroshop-convection-microwave' => 'ElectroShop Convection Microwave',
    'airflow-tower-fan' => 'AirFlow Tower Fan',
    'pureflow-bladeless-fan' => 'PureFlow Bladeless Fan',
    'powersuction-vacuum' => 'PowerSuction Vacuum Cleaner'
];

// Different background colors for different brands
$colors = [
    'lg-oled-tv' => ['#a50034', '#ffffff'], // LG red
    'samsung-qled-tv' => ['#1428a0', '#ffffff'], // Samsung blue
    'lg-instaview-fridge' => ['#a50034', '#ffffff'], // LG red
    'samsung-family-hub-fridge' => ['#1428a0', '#ffffff'], // Samsung blue
    'hitachi-fridge' => ['#e60027', '#ffffff'], // Hitachi red
    'electroshop-microwave' => ['#6c5ce7', '#ffffff'], // ElectroShop purple
    'electroshop-convection-microwave' => ['#6c5ce7', '#ffffff'], // ElectroShop purple
    'airflow-tower-fan' => ['#00cec9', '#333333'], // Teal
    'pureflow-bladeless-fan' => ['#fdcb6e', '#333333'], // Yellow
    'powersuction-vacuum' => ['#ff7675', '#ffffff'] // Coral
];

foreach ($products as $slug => $name) {
    $filename = "../images/products/{$slug}.jpg";
    $bg_color = $colors[$slug][0];
    $text_color = $colors[$slug][1];
    create_placeholder_image($filename, 800, 800, $name, $bg_color, $text_color);
}

echo "<p>All placeholder images for specific products have been created successfully!</p>";
echo "<p>Note: For a production site, replace these placeholder images with actual product photos.</p>";
?>
