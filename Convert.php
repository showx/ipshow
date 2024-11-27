<?php
require 'vendor/autoload.php';

use IpShow\IpShow;

// 使用IP查询
$ipFinder = new IpShow('ip.bin');
// $ipFinder->setDebug(true);  // 需要调试时打开此行注释
$ip = '113.88.209.42';
// 查不到为null
$location = $ipFinder->find($ip);
var_dump($location);
echo "查询IP: {$ip}\n";
if ($location) {
    echo "国家: " . $location['country'] . "\n";
    echo "省份: " . $location['province'] . "\n";
    echo "城市: " . $location['city'] . "\n";
} else {
    echo "未找到此IP的位置信息\n";
}
?>