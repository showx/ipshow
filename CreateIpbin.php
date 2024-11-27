<?php
ini_set("memory_limit", -1);

function encodeData($citydata, $country, $province, $city) {
    $tmp =  [
        'country_code' => $citydata[$country] ?? 0,
        'province_code' => $citydata[$province] ?? 0,
        'city_code' => $citydata[$city] ?? 0
    ];
    return $tmp;
}

// 使用生成器来减少内存使用
function readCsvGenerator($filename) {
    $handle = fopen($filename, 'r');
    while (($data = fgetcsv($handle)) !== false) {
        yield $data;
    }
    fclose($handle);
}

// 批量写入以提高性能
function createBinFile($csvFile, $binFile, $citydata, $city222) {
    $fp = fopen($binFile, 'wb');
    $batch = [];
    $batchSize = 1000;
    $currentOffset = 0;
    $count = 0;
    
    foreach (readCsvGenerator($csvFile) as $row) {
        // 确保IP地址是数字格式
        $ipStart = is_numeric($row[0]) ? $row[0] : ip2long($row[0]);
        $ipEnd = is_numeric($row[1]) ? $row[1] : ip2long($row[1]);
        
        if ($ipStart === false || $ipEnd === false) {
            echo "Warning: Invalid IP range: {$row[0]} - {$row[1]}\n";
            continue;
        }

        // 处理国家/省份/城市编码
        if($row[2] != 'CN'){
            $row[4] = $row[5] = $row[3];
        }
        $row[3] = $city222[strtolower($row[3])] ?? $row[3];
        $row[4] = $city222[strtolower($row[4])] ?? $row[4];
        $row[5] = $city222[strtolower($row[5])] ?? $row[5];
        
        $encoded = encodeData($citydata, $row[3], $row[4], $row[5]);
        
        // 打包数据
        $record = pack('NN', $ipStart, $ipEnd) // IP 范围
            . pack('n', $encoded['country_code']) // country_code (16-bit)
            . pack('n', $encoded['province_code']) // province_code (16-bit)
            . pack('n', $encoded['city_code']); // city_code (16-bit)
        
        $batch[] = $record;
        
        if (count($batch) >= $batchSize) {
            fwrite($fp, implode('', $batch));
            $currentOffset += count($batch) * 14;
            $batch = [];
        }
    }
    
    // 写入剩余数据
    if (!empty($batch)) {
        fwrite($fp, implode('', $batch));
    }
    
    fclose($fp);
    echo "Debug: Data file size: " . filesize($binFile) . " bytes\n";
    echo "Debug: Total records processed: " . $count . "\n";
}

// 加载配置文件
$city222 = file_get_contents(dirname(__FILE__).'/city.json');
$city222 = json_decode($city222, true);

$cityjson = file_get_contents(dirname(__FILE__).'/cityvalue2.json');
$citydata = json_decode($cityjson, true);

// 创建新的bin文件
createBinFile(
    dirname(__FILE__).'/db3.csv', 
    dirname(__FILE__).'/ip.bin',
    $citydata,
    $city222
);

echo "转换完成！\n";
?>