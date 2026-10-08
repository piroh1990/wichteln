<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/captcha_lib.php';

start_secure_session();
$captcha_code = generate_captcha_code();
$_SESSION['captcha_code'] = $captcha_code;

$width = 220;
$height = 56;
$image = imagecreatetruecolor($width, $height);

$bg_color = imagecolorallocate($image, 240, 240, 240);
$text_color = imagecolorallocate($image, 43, 45, 66);
$line_color = imagecolorallocate($image, 42, 157, 143);
$noise_color = imagecolorallocate($image, 200, 200, 200);

imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);

for ($i = 0; $i < 140; $i++) {
    imagesetpixel($image, random_int(0, $width - 1), random_int(0, $height - 1), $noise_color);
}

for ($i = 0; $i < 4; $i++) {
    imageline(
        $image,
        random_int(0, $width - 1),
        random_int(0, $height - 1),
        random_int(0, $width - 1),
        random_int(0, $height - 1),
        $line_color
    );
}

$font_size = 22;
$x = 16;
$y = 38;
$code_length = strlen($captcha_code);
$fonts = array(
    '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
    '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
    '/System/Library/Fonts/Helvetica.ttc',
    '/Library/Fonts/Arial.ttf',
    '/Windows/Fonts/arial.ttf',
);
$font_file = null;
foreach ($fonts as $font) {
    if (file_exists($font)) {
        $font_file = $font;
        break;
    }
}

for ($i = 0; $i < $code_length; $i++) {
    $char = $captcha_code[$i];
    $char_angle = random_int(-18, 18);
    $char_y = $y + random_int(-4, 4);
    if ($font_file !== null && function_exists('imagettftext')) {
        imagettftext($image, $font_size, $char_angle, $x, $char_y, $text_color, $font_file, $char);
    } else {
        imagestring($image, 5, $x, 18, $char, $text_color);
    }
    $x += 32;
}

header('Content-Type: image/png');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

imagepng($image);
imagedestroy($image);
