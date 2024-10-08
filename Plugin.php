<?php
/**
 * <strong style="color:red;">IP地址属地信息插件</strong>
 * 
 * 一个简单的插件，用于显示评论者的 IP 地址及其属地信息，支持不同的接口获取 IP 属地信息。
 *
 * @package IPLocation
 * @author 王浩宇
 * @version 1.2
 * @update: 2024/10/03
 * @link https://www.wanghaoyu.com.cn
 */

class IPLocation_Plugin implements Typecho_Plugin_Interface
{
    const CACHE_FILE = __DIR__ . '/cache.json';  
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
        $apiOptions = [
            'ip-info.cn' => 'ip-info.cn (国外接口)',
            'wanghaoyu.com.cn' => 'wanghaoyu.com.cn (国内接口)'
        ];
        $selectedApi = Typecho_Widget::widget('Widget_Options')->IPLocationApi ?? 'ip-info.cn';
        
        $form->addInput(new Typecho_Widget_Helper_Form_Element_Select('IPLocationApi', $apiOptions, $selectedApi, _t('选择IP查询接口')));
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form) {}

    public static function getIPLocation($ip)
    {
        $cacheData = json_decode(file_get_contents(self::CACHE_FILE), true); 
        $api = Typecho_Widget::widget('Widget_Options')->IPLocationApi ?? 'ip-info.cn';
        
        if (isset($cacheData[$ip]) && (time() - $cacheData[$ip]['timestamp']) < self::CACHE_EXPIRATION) {
            return $cacheData[$ip]['data']; 
        } else {
            $apiUrl = ($api === 'wanghaoyu.com.cn') ? 'https://ip.wanghaoyu.com.cn/api/query?ip=' . $ip : 'https://api.ip-info.cn/api/query?ip=' . $ip;
            $response = @file_get_contents($apiUrl); 
            if ($response) {
                $locationData = json_decode($response, true);
                $cacheData[$ip] = [
                    'timestamp' => time(), 
                    'data' => $locationData 
                ];
                file_put_contents(self::CACHE_FILE, json_encode($cacheData, JSON_PRETTY_PRINT)); 
                return $locationData; 
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
