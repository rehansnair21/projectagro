<?php
// Create directories if they don't exist
$directories = ['assets', 'assets/images', 'uploads', 'uploads/profile_photos'];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Create a simple default avatar if it doesn't exist
if (!file_exists('assets/images/default-avatar.png')) {
    $image = imagecreatetruecolor(200, 200);
    $bg = imagecolorallocate($image, 238, 238, 238);
    $fg = imagecolorallocate($image, 76, 175, 80);
    
    imagefill($image, 0, 0, $bg);
    imagefilledellipse($image, 100, 70, 80, 80, $fg);
    imagefilledrectangle($image, 60, 120, 140, 200, $fg);
    
    imagepng($image, 'assets/images/default-avatar.png');
    imagedestroy($image);
}

echo "Default avatar and directories created successfully!";
?> 