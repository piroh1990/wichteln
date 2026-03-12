<?php
// captcha.php - Generates captcha image

session_start();

// Generate random 5-digit code
$captcha_code = '';
for ($i = 0; $i < 5; $i++) {
    $captcha_code .= random_int(0, 9);
}

// Store in session
$_SESSION['captcha_code'] = $captcha_code;

// Create image
$width = 150;
$height = 50;
$image = imagecreatetruecolor($width, $height);

// Colors
$bg_color = imagecolorallocate($image, 240, 240, 240);
$text_color = imagecolorallocate($image, 43, 45, 66); // --text-primary
$line_color = imagecolorallocate($image, 42, 157, 143); // --secondary-color
$noise_color = imagecolorallocate($image, 200, 200, 200);

// Fill background
imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);

// Add noise (dots)
for ($i = 0; $i < 100; $i++) {
    imagesetpixel($image, random_int(0, $width), random_int(0, $height), $noise_color);
}

// Add lines
for ($i = 0; $i < 3; $i++) {
    imageline($image, random_int(0, $width), random_int(0, $height), random_int(0, $width), random_int(0, $height), $line_color);
}

// Add captcha text
$font_size = 20;
$angle = 0;
$x = 15;
$y = 35;

// Use built-in font if TTF not available
$code_length = strlen($captcha_code);
for ($i = 0; $i < $code_length; $i++) {
    $char = $captcha_code[$i];
    $char_angle = random_int(-15, 15);
    $char_y = $y + random_int(-5, 5);
    
    // Try to use TTF font, fallback to built-in
    if (function_exists('imagettftext')) {
        // Use system font if available
        $fonts = [
            '/System/Library/Fonts/Helvetica.ttc',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            '/Windows/Fonts/arial.ttf',
            '/Library/Fonts/Arial.ttf'
        ];
        
        $font_file = null;
        foreach ($fonts as $font) {
            if (file_exists($font)) {
                $font_file = $font;
                break;
            }
        }
        
        if ($font_file) {
            imagettftext($image, $font_size, $char_angle, $x, $char_y, $text_color, $font_file, $char);
        } else {
            // Fallback to built-in font
            imagestring($image, 5, $x, $char_y - 20, $char, $text_color);
        }
    } else {
        // Fallback to built-in font
        imagestring($image, 5, $x, $char_y - 20, $char, $text_color);
    }
    
    $x += 25;
}

// Output image
header('Content-Type: image/png');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

imagepng($image);
imagedestroy($image);
?>
