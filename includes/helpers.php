<?php
if (!defined('ABSPATH')) exit;


if (!function_exists('wpgp_get_client_ip')) {
    function wpgp_get_client_ip(){
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            $list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($list[0]);
        }
        if (!empty($_SERVER['REMOTE_ADDR'])) return $_SERVER['REMOTE_ADDR'];
        return '0.0.0.0';
    }
}


if (!function_exists('wpgp_is_ip_whitelisted')) {
    function wpgp_is_ip_whitelisted($ip){
        $opts = get_option('wpgp_options', []);
        $list = explode("\n", ($opts['whitelist_ips'] ?? ''));
        $list = array_map('trim', $list);
        $list = array_filter($list);
        if (in_array($ip, $list)) return true;
        if (in_array($ip, ['127.0.0.1', '::1'])) return true;
        return false;
    }
}