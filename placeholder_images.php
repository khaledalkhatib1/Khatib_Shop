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

function create_placeholder_image($filename, $width, $height, $text, $bg_color, $text_color) {
    $image = imagecreatetruecolor($width, $height);
    
    $bg_r = hexdec(substr($bg_color, 1, 2));
    $bg_g = hexdec(substr($bg_color, 3, 2));
    $bg_b = hexdec(substr($bg_color, 5, 2));
    
    $text_r = hexdec(substr($text_color, 1, 2));
    $text_g = hexdec(substr($text_color, 3, 2));
    $text_b = hexdec(substr($text_color, 5, 2));
    
    $bg_color_resource = imagecolorallocate($image, $bg_r, $bg_g, $bg_b);
    imagefill($image, 0, 0, $bg_color_resource);
    
    $text_color_resource = imagecolorallocate($image, $text_r, $text_g, $text_b);
    $font_size = 5;
    $text_box = imagettfbbox($font_size, 0, 'arial.ttf', $text);
    
    $text_width = imagefontheight($font_size) * strlen($text) * 0.6;
    $text_height = imagefontheight($font_size);
    
    $x = ($width - $text_width) / 2;
    $y = ($height + $text_height) / 2;
    
    imagestring($image, $font_size, $x, $y - 20, $text, $text_color_resource);
    
    imagejpeg($image, $filename, 90);
    imagedestroy($image);
    
    echo "Created placeholder image: $filename<br>";
}

$products = [
    'smart-tv-x1000' => 'Ultra HD Smart TV',
    'smart-refrigerator' => 'Smart Refrigerator',
    'ai-washing-machine' => 'AI Washing Machine',
    'ecobreeze-ac' => 'EcoBreeze AC',
    'sunpower-solar-kit' => 'SunPower Solar Kit',
    'nutriblend-blender' => 'NutriBlend Blender',
    'secureview-camera' => 'SecureView Camera',
    'turbogamer-laptop' => 'TurboGamer Laptop',
    'soundpods-earbuds' => 'SoundPods Earbuds',
    'cleanbot-vacuum' => 'CleanBot Vacuum',
    'soundmax-soundbar' => 'SoundMax Soundbar',
    'urbanglide-scooter' => 'UrbanGlide Scooter',
    'brewmaster-coffee' => 'BrewMaster Coffee',
    'safeguard-doorbell' => 'SafeGuard Doorbell',
    'powerhub-station' => 'PowerHub Station'
];

$colors = [
    'smart-tv-x1000' => ['#3498db', '#ffffff'],
    'smart-refrigerator' => ['#2ecc71', '#ffffff'],
    'ai-washing-machine' => ['#9b59b6', '#ffffff'],
    'ecobreeze-ac' => ['#1abc9c', '#ffffff'],
    'sunpower-solar-kit' => ['#f1c40f', '#333333'],
    'nutriblend-blender' => ['#e74c3c', '#ffffff'],
    'secureview-camera' => ['#34495e', '#ffffff'],
    'turbogamer-laptop' => ['#8e44ad', '#ffffff'],
    'soundpods-earbuds' => ['#d35400', '#ffffff'],
    'cleanbot-vacuum' => ['#2c3e50', '#ffffff'],
    'soundmax-soundbar' => ['#16a085', '#ffffff'],
    'urbanglide-scooter' => ['#c0392b', '#ffffff'],
    'brewmaster-coffee' => ['#7f8c8d', '#ffffff'],
    'safeguard-doorbell' => ['#27ae60', '#ffffff'],
    'powerhub-station' => ['#f39c12', '#ffffff']
];

foreach ($products as $slug => $name) {
    $filename = "../images/products/{$slug}.jpg";
    $bg_color = $colors[$slug][0];
    $text_color = $colors[$slug][1];
    create_placeholder_image($filename, 800, 800, $name, $bg_color, $text_color);
}

echo "<p>All placeholder images have been created successfully!</p>";
echo "<p>Note: For a production site, replace these placeholder images with actual product photos.</p>";
?>
