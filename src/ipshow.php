<?php
namespace IpShow;

class IpShow {
    private $fp;
    private $totalIps;
    private $indexCache = [];
    private $debug = false;
    
    public function __construct($binFile) {
        $this->fp = fopen($binFile, 'rb');
        if (!$this->fp) {
            throw new \Exception("Cannot open file: " . $binFile);
        }
        $fileSize = filesize($binFile);
        $this->totalIps = floor($fileSize / 14);
        
        // 建立内存索引
        // ... 其他代码
    }
    
    /**
     * 设置是否启用调试模式
     * @param bool $enabled 是否启用
     * @return self
     */
    public function setDebug($enabled = true) {
        $this->debug = $enabled;
        return $this;
    }
    
    /**
     * 获取当前调试模式状态
     * @return bool
     */
    public function isDebug() {
        return $this->debug;
    }
    
    private function log($message) {
        if ($this->debug) {
            echo "Debug: {$message}\n";
        }
    }
    
    private function getCityName($code) {
        return CityData::getName($code);
    }
    
    public function find($ip) {
        $ipLong = ip2long($ip);
        if ($ipLong === false) {
            $this->log("Invalid IP address");
            return null;
        }
        $this->log("Searching for IP: {$ip} (Long: {$ipLong})");
        
        // 使用索引快速定位大致位置
        $start = 0;
        $end = $this->totalIps;
        $this->log("Total IPs in database: {$this->totalIps}");
        
        $lastStart = 0;
        foreach ($this->indexCache as $offset => $indexIp) {
            if ($ipLong >= $indexIp) {
                $start = $offset;
                $lastStart = $indexIp;
                $this->log("Found start offset: {$start} (IP: " . long2ip($indexIp) . ")");
            } else {
                $end = $offset;
                $this->log("Found end offset: {$end} (IP: " . long2ip($indexIp) . ")");
                break;
            }
        }
        
        // 二分查找具体位置
        return $this->binarySearch($ipLong, $start, $end);
    }
    
    private function binarySearch($ip, $start, $end) {
        while ($start <= $end) {
            $mid = floor(($start + $end) / 2);
            fseek($this->fp, $mid * 14);
            $data = fread($this->fp, 14);
            
            if (strlen($data) < 14) {
                $this->log("Invalid data length at offset " . ($mid * 14));
                return null;
            }
            
            $unpacked = unpack('Nstart_ip/Nend_ip/ncountry_code/nprovince_code/ncity_code', $data);
            if (!$unpacked) {
                $this->log("Failed to unpack data");
                return null;
            }
            
            if ($unpacked['start_ip'] == 0 && $unpacked['end_ip'] == 0) {
                $this->log("Skip invalid range at offset " . ($mid * 14));
                $start = $mid + 1;
                continue;
            }
            
            $this->log("Comparing IP {$ip} with range " . long2ip($unpacked['start_ip']) . " - " . long2ip($unpacked['end_ip']));
            
            if ($ip < $unpacked['start_ip']) {
                $end = $mid - 1;
            } elseif ($ip > $unpacked['end_ip']) {
                $start = $mid + 1;
            } else {
                $this->log("Found match!");
                $result = [
                    'country_code' => $unpacked['country_code'],
                    'province_code' => $unpacked['province_code'],
                    'city_code' => $unpacked['city_code'],
                    'country' => $this->getCityName($unpacked['country_code']),
                    'province' => $this->getCityName($unpacked['province_code']),
                    'city' => $this->getCityName($unpacked['city_code'])
                ];
                return $result;
            }
        }
        $this->log("No match found in range");
        return null;
    }
    
    public function __destruct() {
        if ($this->fp) {
            fclose($this->fp);
        }
    }
    
    public function getIndexCacheSize() {
        return count($this->indexCache);
    }
}