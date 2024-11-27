<?php
require 'vendor/autoload.php';

use IpShow\IpShow;

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
}

function formatTime($microseconds) {
    if ($microseconds < 1000) {
        return number_format($microseconds, 2) . ' μs（微秒）';
    } elseif ($microseconds < 1000000) {
        return number_format($microseconds / 1000, 2) . ' ms（毫秒）';
    } else {
        return number_format($microseconds / 1000000, 2) . ' s（秒）';
    }
}

function benchmark($testSize = 10000, $warmup = true) {
    echo "性能测试开始...\n";
    echo "==============================\n";

    // 初始化性能计数器
    $startMemory = memory_get_usage();
    
    // 加载IP数据库
    echo "加载IP数据库...\n";
    $beforeLoad = memory_get_usage();
    $loadStart = microtime(true);
    $ipFinder = new IpShow('ip.bin');
    $loadTime = (microtime(true) - $loadStart) * 1000000;
    $afterLoad = memory_get_usage();
    echo "数据库加载时间: " . formatTime($loadTime) . "\n";
    echo "数据库加载占用内存: " . formatBytes($afterLoad - $beforeLoad) . "\n";
    echo "索引缓存大小: " . formatBytes(strlen(serialize($ipFinder->getIndexCacheSize()))) . "\n";
    
    // 准备测试IP列表
    $testIps = [];
    if (file_exists('testip.txt')) {
        $testIps = array_map('trim', file('testip.txt'));
        echo "从testip.txt加载了 " . count($testIps) . " 个IP地址\n";
    } else {
        echo "生成 {$testSize} 个随机IP地址...\n";
        for ($i = 0; $i < $testSize; $i++) {
            $testIps[] = mt_rand(0, 255) . "." . mt_rand(0, 255) . "." . mt_rand(0, 255) . "." . mt_rand(0, 255);
        }
    }

    // 预热阶段
    if ($warmup) {
        echo "\n执行预热...\n";
        $warmupSize = min(1000, count($testIps));
        for ($i = 0; $i < $warmupSize; $i++) {
            $ipFinder->find($testIps[$i]);
        }
    }

    // 性能测试
    echo "\n开始性能测试...\n";
    $times = [];
    $hits = 0;
    $misses = 0;
    $totalTime = 0;
    $minTime = PHP_FLOAT_MAX;
    $maxTime = 0;

    foreach ($testIps as $index => $ip) {
        $start = microtime(true);
        $location = $ipFinder->find($ip);
        $time = (microtime(true) - $start) * 1000000; // 转换为微秒
        
        $times[] = $time;
        $totalTime += $time;
        $minTime = min($minTime, $time);
        $maxTime = max($maxTime, $time);
        
        if ($location) {
            $hits++;
        } else {
            $misses++;
        }

        // 每1000次查询显示进度
        if (($index + 1) % 1000 === 0) {
            echo "已处理 " . number_format($index + 1) . " 个IP...\n";
        }
    }

    // 计算统计信息
    sort($times);
    $totalQueries = count($testIps);
    $avgTime = $totalTime / $totalQueries;
    $medianTime = $times[floor($totalQueries / 2)];
    $p95Time = $times[floor($totalQueries * 0.95)];
    $p99Time = $times[floor($totalQueries * 0.99)];
    $qps = $totalQueries / ($totalTime / 1000000);
    
    // 内存使用统计
    $memoryUsed = memory_get_usage() - $startMemory;
    $peakMemory = memory_get_peak_usage();

    // 输出统计信息
    echo "\n性能测试结果:\n";
    echo "==============================\n";
    echo "总查询数: " . number_format($totalQueries) . "\n";
    echo "总耗时: " . formatTime($totalTime) . "\n";
    echo "每秒查询数(QPS): " . number_format($qps, 2) . "\n";
    echo "平均查询时间: " . formatTime($avgTime) . "\n";
    echo "最小查询时间: " . formatTime($minTime) . "\n";
    echo "最大查询时间: " . formatTime($maxTime) . "\n";
    echo "中位数查询时间: " . formatTime($medianTime) . "\n";
    echo "95%查询时间: " . formatTime($p95Time) . "\n";
    echo "99%查询时间: " . formatTime($p99Time) . "\n";
    echo "命中率: " . number_format(($hits / $totalQueries) * 100, 2) . "%\n";
    echo "内存使用: " . formatBytes($memoryUsed) . "\n";
    echo "峰值内存: " . formatBytes($peakMemory) . "\n";

    // CPU使用率（仅在Linux系统上有效）
    if (function_exists('sys_getloadavg')) {
        $load = sys_getloadavg();
        echo "CPU负载: " . implode(", ", array_map(function($v) { 
            return number_format($v, 2); 
        }, $load)) . "\n";
    }

    // 输出最慢的10个查询
    echo "\n最慢的10个查询:\n";
    echo "==============================\n";
    arsort($times);
    $slowest = array_slice($times, 0, 10, true);
    foreach ($slowest as $index => $time) {
        echo sprintf(
            "IP: %s, 耗时: %s\n",
            $testIps[$index],
            formatTime($time)
        );
    }
}

// 运行基准测试
// 可以调整测试IP数量，第二个参数控制是否进行预热
benchmark(10000, true); 