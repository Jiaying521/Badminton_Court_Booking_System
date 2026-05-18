<?php
// 创建默认头像目录
$dir = __DIR__ . '/Admin_Module/Pictures/users/';
if (!file_exists($dir)) {
    mkdir($dir, 0777, true);
    echo "目录已创建: " . $dir . "<br>";
}

// 创建一个简单的 base64 图片作为默认头像
$defaultAvatarPath = $dir . 'default_avatar.png';

if (!file_exists($defaultAvatarPath)) {
    // 创建一个简单的 SVG 转 PNG 的默认头像（使用 GD 库）
    if (function_exists('imagecreate')) {
        $width = 200;
        $height = 200;
        $image = imagecreate($width, $height);
        
        // 背景色 - 绿色
        $bgColor = imagecolorallocate($image, 43, 126, 58);
        // 文字颜色 - 白色
        $textColor = imagecolorallocate($image, 255, 255, 255);
        
        // 填充背景
        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);
        
        // 添加文字
        $text = "User";
        $fontSize = 5;
        $textWidth = imagefontwidth($fontSize) * strlen($text);
        $textHeight = imagefontheight($fontSize);
        $x = ($width - $textWidth) / 2;
        $y = ($height - $textHeight) / 2;
        imagestring($image, $fontSize, $x, $y, $text, $textColor);
        
        // 保存图片
        imagepng($image, $defaultAvatarPath);
        imagedestroy($image);
        
        echo "默认头像已创建: " . $defaultAvatarPath . "<br>";
    } else {
        echo "GD 库未安装，请手动放置 default_avatar.png 文件<br>";
    }
} else {
    echo "默认头像已存在: " . $defaultAvatarPath . "<br>";
}

echo "完成！";
?>