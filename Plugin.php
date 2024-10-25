<?php
/**
 * <strong style="color:red;">IP地址属地信息插件</strong>
 *
 * 一个简单的插件，用于显示评论者的 IP 地址及其属地信息，支持不同的接口获取 IP 属地信息。
 *
 * @package IPLocation
 * @version 1.3
 * @update: 2024/10/26
 * @author 王浩宇
 * @link https://www.wanghaoyu.com.cn
 */

class IPLocation_Plugin implements Typecho_Plugin_Interface
{
    const CACHE_FILE = __DIR__ . '/cache.json';  
    const API_CONFIG_FILE = __DIR__ . '/api_config.json';
    const CACHE_EXPIRATION = 86400;  

    public static function activate()
    {
        if (!file_exists(self::CACHE_FILE)) {
            file_put_contents(self::CACHE_FILE, json_encode([]));
        }
        return _t('IPLocation 插件已激活');
    }

    public static function deactivate()
    {
        return _t('IPLocation 插件已禁用');
    }

    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $apiOptions = self::loadApiOptions();
        $selectedApi = Typecho_Widget::widget('Widget_Options')->IPLocationApi ?? 'ip-info';

        $form->addInput(new Typecho_Widget_Helper_Form_Element_Select(
            'IPLocationApi', 
            $apiOptions, 
            $selectedApi, 
            _t('选择IP查询接口')
        ));
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form) {}

    private static function loadApiOptions()
    {
        $config = json_decode(file_get_contents(self::API_CONFIG_FILE), true);
        $options = [];
        foreach ($config as $key => $api) {
            $options[$key] = $api['name'];
        }
        return $options;
    }

    private static function loadApiFile($api)
    {
        $config = json_decode(file_get_contents(self::API_CONFIG_FILE), true);
        if (isset($config[$api])) {
            return __DIR__ . '/' . $config[$api]['file'];
        }
        return null;
    }

    public static function getIPLocation($ip)
    {
        $cacheData = json_decode(file_get_contents(self::CACHE_FILE), true);
        $api = Typecho_Widget::widget('Widget_Options')->IPLocationApi ?? 'ip-info';

        // 检查缓存是否存在且未过期
        if (isset($cacheData[$ip]) && (time() - $cacheData[$ip]['timestamp']) < self::CACHE_EXPIRATION) {
            return $cacheData[$ip]['data'];
        } else {
            $apiFile = self::loadApiFile($api);
            if ($apiFile && file_exists($apiFile)) {
                $locationData = include $apiFile;
                $result = $locationData($ip); // 调用 API 逻辑

                if ($result) {
                    $cacheData[$ip] = [
                        'timestamp' => time(),
                        'data' => $result,
                    ];
                    file_put_contents(self::CACHE_FILE, json_encode($cacheData, JSON_PRETTY_PRINT));
                    return $result;
                }
            }
        }

        return null;
    }

    public static function formatIPLocation($ip)
    {
        $locationData = self::getIPLocation($ip);

        if ($locationData) {
            if (!empty($locationData['regions'])) {
                $regions = array_slice($locationData['regions'], 0, 2);
                $asnInfo = $locationData['asn']['info'] ?? $locationData['asn']['name'];
                return 'IP属地：' . implode(' ', $regions) . ' ' . $asnInfo;
            } elseif (!empty($locationData['country'])) {
                $country = $locationData['country']['name'];
                $asnInfo = $locationData['asn']['name'] ?? '';
                return 'IP属地：' . $country . ($asnInfo ? ' ' . $asnInfo : '');
            }
        }

        return 'IP属地：未知';
    }

    public static function displayIPLocation($ip)
    {
        return self::formatIPLocation($ip);
    }
}
