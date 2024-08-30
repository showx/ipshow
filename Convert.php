<?php

$cityjson = file_get_contents(dirname(__FILE__).'/cityvalue.json');
$citydata = json_decode($cityjson, true);
// $citydata = array_values($citydata);
// var_dump($citydata);exit();
$filename = dirname(__FILE__).'/ip.bin';
$file = fopen($filename, 'rb');

$filename = dirname(__FILE__).'/ipindex.bin';
$fileIndex = fopen($filename, 'rb');

function findIpRange($file, $ip) {
    $recordSize = 14; // 每条记录 14 字节
    // fseek($file, 0, SEEK_END);
    // $fileSize = ftell($file);
    // $totalRecords = $fileSize / $recordSize;
    // echo "total:".$totalRecords.PHP_EOL;
    // var_dump($totalRecords);exit();
    // float(1696648.5714285714)
    $totalRecords = "2969135";

    $low = 0;
    $high = $totalRecords - 1;

    while ($low <= $high) {
        // echo $low." ".$high.PHP_EOL;
        $mid = floor(($low + $high) / 2);
        fseek($file, $mid * $recordSize);
        $record = fread($file, $recordSize);
        $unpacked = unpack('Nstart_ip/Nend_ip/ncountry_code/nprovince_code/ncity_code', $record);
        
        if ($ip < $unpacked['start_ip']) {
            $high = $mid - 1;
        } elseif ($ip > $unpacked['end_ip']) {
            $low = $mid + 1;
        } else {
            // 找到对应的 IP 段
            // fclose($file);
            return $unpacked;
        }
    }
    
    // while ($low <= $high) {
    //     fseek($file, $low * $recordSize);
    //     $record = fread($file, $recordSize);
    //     $unpacked = unpack('Nstart_ip/Nend_ip/ncountry_code/nprovince_code/ncity_code', $record);
    //     $low++;
    //     if($ip >= $unpacked['start_ip'] && $ip <= $unpacked['end_ip']){
    //         fclose($file);
    //         return $unpacked;
    //     }
    // }
    
    fclose($file);
    return null; // 未找到对应的 IP 段
}


// $indexFile = fopen($indexFilename, 'rb');
// $binFile = fopen($binFilename, 'rb');

function findIpRangeWithIndex($binFile, $indexFile, $ip) {
    $blockSize = 10000; // 每个块的IP范围
    $recordSize = 14;   // 每条记录14字节

    // 定位到对应的块
    $low = 0;
    // fseek($indexFile, 0, SEEK_END);
    // $high = ftell($indexFile) / 8 - 1; // 每块索引占8字节
    // var_dump($high);exit();
    $high = 183540;
    
    while ($low <= $high) {
        // var_dump($indexFile);
        $mid = floor(($low + $high) / 2);
        fseek($indexFile, $mid * 8);
        $indexEntry = fread($indexFile, 8);
        list($blockStartIp, $blockOffset) = array_values(unpack('NblockStartIp/NblockOffset', $indexEntry));
        
        if ($ip < $blockStartIp) {
            $high = $mid - 1;
        } elseif ($ip >= $blockStartIp && $ip < $blockStartIp + $blockSize) {
            // 找到对应的块
            fseek($binFile, $blockOffset);
            while (!feof($binFile)) {
                $record = fread($binFile, $recordSize);
                if (strlen($record) < $recordSize) break; // 读到文件末尾
                $unpacked = unpack('Nstart_ip/Nend_ip/ncountry_code/nprovince_code/ncity_code', $record);
                
                if ($ip >= $unpacked['start_ip'] && $ip <= $unpacked['end_ip']) {
                    // fclose($binFile);
                    // fclose($indexFile);
                    return $unpacked;
                }
            }
            break;
        } else {
            $low = $mid + 1;
        }
    }
    
    // fclose($binFile);
    // fclose($indexFile);
    return null;
}
// 示例使用
$ip = "154.64.226.178";
$ip = "183.6.56.145";

$ips = file(dirname(__FILE__).'/testip.txt');
foreach($ips as $ip){
    $ip = trim($ip);
    $ipToSearch = ip2long($ip);
    // var_dump($ipToSearch);
    // $ipToSearch = 16793700;
    $result = findIpRange($file, $ipToSearch);
    // $result = findIpRangeWithIndex($file, $fileIndex, $ipToSearch);
    if ($result) {
        // var_dump($result);
        $long = $result['start_ip'];
        $ip1 = long2ip($long);
        // echo $ip.PHP_EOL;  // 输出：192.168.1.1
        $long = $result['end_ip'];
        $ip2 = long2ip($long);
        // echo $ip.PHP_EOL; 
        
        // $decoded = decodeData($result['country_code'], $result['province_code'], $result['city_code'], 'country_codes.map', 'province_codes.map', 'city_codes.map');
        echo "IP: {$ip}({$ipToSearch}), {$ip1} - {$ip2} , Country: {$citydata[$result['country_code']]}, Province: {$citydata[$result['province_code']]}, City: {$citydata[$result['city_code']]}\n";
        // exit();
    } else {
        // echo "IP: {$ipToSearch} 不在任何已知的 IP 段内\n";
    }
}
?>