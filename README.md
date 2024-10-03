

# Typecho IPLocation 插件

 **Typecho IPLocation 插件** 是一个 Typecho 插件，用于显示评论者的 IP 地址及其属地信息。插件使用 [IP-INFO.CN](https://api.ip-info.cn/) API 来查询 IP 属地数据，并将结果缓存到本地，以减少频繁 API 调用。缓存时间为 86400 秒（24小时）。

---

## 功能简介

- 通过 IP-INFO.CN API 查询评论者 IP 的地理信息，可自行更换为其他接口。
- 缓存查询结果至本地 `cache.json` 文件，有效期为 24 小时，提升性能。
- 国内 IP：显示**省、市**以及 ISP 信息，如 `中国 山东省 潍坊市 中国联通`。
- 国外 IP：仅显示国家和 ASN 信息，如 `澳大利亚 CLOUDFLARENET`。
- 缓存存储于插件目录下的 `cache.json` 文件，简单且高效。

---

## 安装与激活

1. 将插件文件夹 `IPLocation` 上传至 `Typecho` 的 `usr/plugins` 目录中。
2. 在 Typecho 后台启用插件。
3. 插件激活时会自动创建 `cache.json` 文件用于存储缓存。

---

## 使用方法

在评论模板中，使用以下代码嵌入并显示评论者的 IP 属地信息：

```php
<?php echo IPLocation_Plugin::displayIPLocation($comments->ip); ?>
```

此代码会输出 IP 的属地信息，例如：

- **国内**：`IP属地：山东省 潍坊市 中国联通`
- **国外**：`IP属地：澳大利亚 CLOUDFLARENET`

详情请到我的博客查看：https://www.wanghaoyu.com.cn/archives/ip-location-plugin.html

---

## 缓存机制

- **缓存文件**：`cache.json` 文件位于插件目录下，所有 IP 查询结果都存储在该文件中。
- **缓存有效期**：24 小时（86400 秒）。每次查询时，会优先检查缓存，超过有效期则重新发起 API 请求。
  
---

## 更新日志

### v1.1 (2024-10-03)
- 改进了缓存逻辑，将缓存存储至 `cache.json` 文件。
- 支持显示国内省、市及 ISP 信息；国外仅显示国家和 ASN 信息。

---

## 作者信息

- **作者**: 王浩宇
- **版本**: 1.1
- **更新日期**: 2024/10/03
- **网站**: [wanghaoyu.com.cn](https://www.wanghaoyu.com.cn)

---

如有问题或建议，请通过以上链接联系作者。