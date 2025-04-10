<?php
// Create necessary directories
$directories = [
    'assets/images',
    'uploads/profile_photos'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Create a default avatar if it doesn't exist
$default_avatar = 'assets/images/default-avatar.png';
if (!file_exists($default_avatar)) {
    $size = 200;
    $image = imagecreatetruecolor($size, $size);
    
    // Colors
    $bg = imagecolorallocate($image, 238, 238, 238);
    $fg = imagecolorallocate($image, 76, 175, 80);
    
    // Background
    imagefilledrectangle($image, 0, 0, $size, $size, $bg);
    
    // Simple avatar design
    imagefilledellipse($image, $size/2, $size/2-10, 120, 120, $fg);
    imagefilledrectangle($image, $size/2-40, $size/2+30, $size/2+40, $size, $fg);
    
    imagepng($image, $default_avatar);
    imagedestroy($image);
}

echo "Assets created successfully!";
?> 