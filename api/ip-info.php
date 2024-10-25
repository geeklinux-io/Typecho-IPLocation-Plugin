<?php
return function($ip) {
    $url = 'https://api.ip-info.cn/api/query?ip=' . $ip;
    $response = @file_get_contents($url);
    return $response ? json_decode($response, true) : null;
};
