<?php
require 'vendor/autoload.php';

use IpShow\IpShow;

// 模拟并发请求
function concurrent_test($processes = 10) {
    $ips = [
        '1.1.1.1',
        '8.8.8.8',
        '114.114.114.114',
        // ... 添加更多测试IP
    ];
    
    for ($i = 0; $i < $processes; $i++) {
        $pid = pcntl_fork();
        if ($pid == -1) {
            die("无法创建进程");
        } else if ($pid) {
            // 父进程继续
            continue;
        } else {
            // 子进程
            $finder = new IpShow();
            foreach ($ips as $ip) {
                $result = $finder->find($ip);
                echo "进程 " . getmypid() . " 查询IP: {$ip} 结果: " . 
                    ($result ? $result['city'] : 'not found') . "\n";
            }
            exit(0);
        }
    }
    
    // 等待所有子进程结束
    $status = 0;
    while (pcntl_waitpid(0, $status) != -1);
}

// 运行测试
echo "开始并发测试...\n";
concurrent_test(10);  // 10个并发进程 