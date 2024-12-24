<?php
/**
 * <strong style="color:red;">IP地址属地信息插件</strong>
 *
 * 一个简单的插件，用于显示评论者的 IP 地址及其属地信息，支持不同的接口获取 IP 属地信息。
 *
 * @package IPLocation
 * @version 1.4
 * @update: 2024/12/25
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
        return _t('IPLocation 插件已激活 by wanghaoyu.com.cn');
    }

    public static function deactivate()
    {
        return _t('IPLocation 插件已禁用');
    }

    public static function config(Typecho_Widget_Helper_Form $form)
    {
        echo '<style>
            .typecho-page-title {
                font-size: 24px;
                font-weight: bold;
                margin-bottom: 20px;
            }
            .typecho-option {
                margin-bottom: 15px;
            }
            .typecho-option label {
                font-weight: bold;
            }
            .typecho-option input[type="checkbox"] {
                margin-right: 10px;
            }
            .typecho-option select {
                padding: 5px;
                border-radius: 4px;
                border: 1px solid #ccc;
            }
        </style>';

        $apiOptions = self::loadApiOptions();
        $selectedApi = Typecho_Widget::widget('Widget_Options')->IPLocationApi ?? 'ip-info';

        $form->addInput(new Typecho_Widget_Helper_Form_Element_Select(
            'IPLocationApi', 
            $apiOptions, 
            $selectedApi, 
            _t('选择IP查询接口')
            .'<br>'
            ._t('接口状态监控显示页面：<a href="https://uptime.wanghaoyu.com.cn/status/all-status-page" target="_blank">状态监控</a>')
        ));

        $form->addInput(new Typecho_Widget_Helper_Form_Element_Checkbox(
            'EnableCache',
            ['1' => _t('启用缓存')],
            ['1'],
            _t('缓存设置'),
            _t('启用此选项可开启IP查询结果缓存功能，可以显著减少API请求，优化网站性能，提升用户体验')
            .'<br>'
            ._t('默认缓存到插件目录下的cache.json文件中，缓存时间为24小时。')
            .'<br>'
            ._t('如果需要清空缓存，请到插件目录下删除cache.json文件内容。')
        ));

        $form->addInput(new Typecho_Widget_Helper_Form_Element_Checkbox(
            'ShowCountyLevel',
            ['1' => _t('显示县级市地理位置')],
            ['1'],
            _t('地理位置设置'),
            _t('启用此选项以显示县级市地理位置,无数据时自动留空。')
            .'<br>'
            ._t('国外IP查询结果不显示县级市地理位置。')
        ));

        $form->addInput(new Typecho_Widget_Helper_Form_Element_Checkbox(
            'ShowASNInfo',
            ['1' => _t('显示运营商信息')],
            ['1'],
            _t('运营商信息设置'),
            _t('启用此选项以显示运营商信息。')

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
        $enableCache = Typecho_Widget::widget('Widget_Options')->plugin('IPLocation')->EnableCache;
        $cacheData = $enableCache ? json_decode(file_get_contents(self::CACHE_FILE), true) : [];
        $api = Typecho_Widget::widget('Widget_Options')->IPLocationApi ?? 'ip-info';

        if ($enableCache && isset($cacheData[$ip]) && (time() - $cacheData[$ip]['timestamp']) < self::CACHE_EXPIRATION) {
            return $cacheData[$ip]['data'];
        } else {
            $apiFile = self::loadApiFile($api);
            if ($apiFile && file_exists($apiFile)) {
                $locationData = include $apiFile;
                $result = $locationData($ip);

                if ($result && $enableCache) {
                    $cacheData[$ip] = [
                        'timestamp' => time(),
                        'data' => $result,
                    ];
                    file_put_contents(self::CACHE_FILE, json_encode($cacheData, JSON_PRETTY_PRINT));
                }
                return $result;
            }
        }

        return null;
    }

    public static function formatIPLocation($ip)
    {
        $locationData = self::getIPLocation($ip);
        $showCountyLevel = Typecho_Widget::widget('Widget_Options')->plugin('IPLocation')->ShowCountyLevel;
        $showASNInfo = Typecho_Widget::widget('Widget_Options')->plugin('IPLocation')->ShowASNInfo;

        if ($locationData) {
            $regions = $showCountyLevel ? $locationData['regions'] : array_slice($locationData['regions'], 0, 2);
            
            $asnInfo = '';
            if ($showASNInfo && isset($locationData['asn']['info'])) {
                if (is_array($locationData['asn']['info'])) {
                    $asnInfo = $locationData['asn']['info']['name'] ?? '';
                } else {
                    $asnInfo = $locationData['asn']['info'];
                }
            }

            $countryCode = $locationData['country']['code'] ?? $locationData['registered_country']['code'] ?? '';
            $countryName = $locationData['country']['name'] ?? $locationData['registered_country']['name'] ?? '未知';

            $locationString = implode(' ', $regions);

            if ($countryCode !== 'CN') {
                $locationString = $countryName . ' ' . $locationString;
            }

            if ($asnInfo) {
                $locationString .= ' ' . $asnInfo;
            }

            return 'IP属地：' . trim($locationString);
        }

        return 'IP属地：未知';
    }

    public static function displayIPLocation($ip)
    {
        return self::formatIPLocation($ip);
    }
}