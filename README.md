# ipshowIP地址查询库

一个高性能的IP地址地理位置查询库，支持快速查询IP对应的国家、省份和城市信息。

## 特性

- 高性能：使用二进制文件存储，支持快速检索
- 低内存：采用稀疏索引，只在内存中保留必要的索引数据
- 并发安全：支持多进程/多线程并发查询
- 中文支持：直接返回中文地理位置信息
- 调试功能：可选的调试输出功能

## 安装

通过 Composer 安装：

```bash
composer require ipshow/ipshow
```

## 使用方法

```php
- use Ipshow\IpShow;
+ use IpShow\IpShow;

// ... 其他内容 ...
```
