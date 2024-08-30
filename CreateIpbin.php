<?php
ini_set("memory_limit", -1);
$city222 = file_get_contents(dirname(__FILE__).'/city.json');
$city222 = json_decode($city222, true);

$cityjson = file_get_contents(dirname(__FILE__).'/cityvalue2.json');
$citydata = json_decode($cityjson, true);

function encodeData($citydata, $country, $province, $city) {
    $tmp =  [
        'country_code' => $citydata[$country] ?? 0,
        'province_code' => $citydata[$province] ?? 0,
        'city_code' => $citydata[$city] ?? 0
    ];
    return $tmp;
}
// 打开文件
$file = fopen(dirname(__FILE__).'/db3.csv', 'r');

$result = [];
// 检查文件是否成功打开

$iswrite = true;
if ($file !== false) {
    // 循环读取文件中的每一行
    $i = $j = $k = 1;
    $d3 = $d4 = $d5 = $d6 = [];
    if($iswrite){
        $fileindexname = dirname(__FILE__)."/ipindex.bin";
        $indexFile = fopen($fileindexname, 'wb');
        $filenamewrite = dirname(__FILE__)."/ip.bin";
        $filewrite = fopen($filenamewrite, 'wb');
    }
    $xx = 1;
    $currentBlockStart = null;
    $currentBlockOffset = 0;
    while (($data = fgetcsv($file, 1000, ",")) !== false) {
        
        $result[] = $data;
        // 国外ip不具体识别
        if($data['2'] != 'CN'){
            $data['4'] = $data['5'] = $data['3'];
        }
        $data['3'] = $city222[strtolower($data['3'])] ?? $data['3'];
        $data['4'] = $city222[strtolower($data['4'])] ?? $data['4'];
        $data['5'] = $city222[strtolower($data['5'])] ?? $data['5'];
        
        $encoded = encodeData($citydata, $data[3], $data[4], $data[5]);
        $tmpwrite = pack('NN', $data[0], $data[1]) // IP 范围
        . pack('n', $encoded['country_code']) // country_code (16-bit)
        . pack('n', $encoded['province_code']) // province_code (16-bit)
        . pack('n', $encoded['city_code']);

        // $unpacked = unpack('Nstart_ip/Nend_ip/ncountry_code/nprovince_code/ncity_code', $tmpwrite);
        if($encoded['province_code'] == '0' || $encoded['province_code'] == 0){
            // var_dump($data,$encoded);
            $errortmp[$data['4']] = 1;
        }
        if($iswrite){
            if ($currentBlockStart === null || $data[0] >= $currentBlockStart + 10000) {
                $currentBlockStart = $data[0];
                fwrite($indexFile, pack('N', $currentBlockStart)); // 写入块起始IP
                fwrite($indexFile, pack('N', $currentBlockOffset));
            }
            fwrite($filewrite,$tmpwrite);
        }
        $currentBlockOffset += 14;
    }
    // var_dump($errortmp);
    fclose($indexFile);
    fclose($filewrite);
    fclose($file);
    exit();
} else {
    echo "无法打开文件。";
}
?>