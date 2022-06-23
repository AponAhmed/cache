<?php

namespace CacheFy\src;

use CacheFy\src\createCache;

/**
 * Description of FrontEnd
 *
 * @author Apon
 */
class FrontEnd {

    use Methods;

    //put your code here
    public function __construct() {
        //add_action('init', [self::class, 'init'], -9999);
        add_action('plugins_loaded', [self::class, 'init'], -9999);
        add_filter('final_output', [self::class, 'storeCache'], 9999);
    }

    /**
     * Initialize hook after  plugins_loaded
     */
    public static function init() {

        self::getOption();
        self::setFilename();

        if (isset(self::$option->enable) && !isset(self::$option->disable_loggedin)) {
            //var_dump(self::$fileName);
            if (file_exists(self::$fileName)) {
                //CacheLoader::run();//plugins_loaded Event
                //echo "Init Event";
                header('cache-type:init-event');
                header("cache-control:public, max-age=31536000");
                header("expires:" . date("D, j M Y G:i:s", time() + 31536000) . " GMT");
                header("content-type:text/html");
                header("pragma:public");
                echo file_get_contents(self::$fileName);
                if (is_user_logged_in()) {
                    self::cacheTools();
                }
                self::die();
            } else {
                self::FrontEndInit();
            }
        } else {
            if (is_user_logged_in()) {
                add_action('wp_footer', [self::class, 'cacheTools']);
                //echo self::cacheTools();
            }
            self::FrontEndInit();
        }
    }

    /**
     * Frontend Cache Toolbar
     */
    static function cacheTools() {
        ob_start();
        ?>
        <style>
            .cache-toolbar {
                position: fixed;
                left: 5px;
                bottom: 5px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: #333;
                border: 1px solid #000;
                border-radius: 25px;
                padding: 5px 0px;
                transform: translateX(0px);
                transition: all .2s;
                flex-direction: column;
            }
            .cache-toolbar.cache-toolbar-hide {
                transform: translateX(-50px);
            }
            .cache-toolbar .tolbar-head {
                display: flex;
                padding-bottom: 5px;
                border-bottom: 1px solid #151515;
            }
            .cache-toolbar .tolbar-head svg path {
                color: #979797;
            }

            .cache-toolbar > a {
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 4px;
                color: #fff;
            }

            .cache-toolbar > a {
                color: #fff;
                fill: #fff;
            }
            .loading-cahce-process {
                animation-name: rotateAnimation;
                animation-duration: 1s;
                animation-iteration-count: infinite;
                animation-timing-function: linear;
            }
            @keyframes rotateAnimation {
                from {
                    transform: rotate(0deg);
                }
                to {
                    transform: rotate(360deg);
                }
            }
        </style>
        <div class="cache-toolbar" title="Cache Toolbar">
            <div class='tolbar-head'><svg xmlns="http://www.w3.org/2000/svg" style="max-width:22px" class="svg-icon" viewBox="0 0 512 512"><title>Cache Tools</title><path d="M315.27 33L96 304h128l-31.51 173.23a2.36 2.36 0 002.33 2.77h0a2.36 2.36 0 001.89-.95L416 208H288l31.66-173.25a2.45 2.45 0 00-2.44-2.75h0a2.42 2.42 0 00-1.95 1z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="28"/></svg></div>

            <a href="javascript:void(0)" onclick="refreshCache(this)"><svg style="max-width:22px" xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><title>Refresh Cache</title><path d="M256 48C141.31 48 48 141.32 48 256c0 114.86 93.14 208 208 208 114.69 0 208-93.31 208-208 0-114.87-93.13-208-208-208zm0 313a94 94 0 010-188h4.21l-14.11-14.1a14 14 0 0119.8-19.8l40 40a14 14 0 010 19.8l-40 40a14 14 0 01-19.8-19.8l18-18c-2.38-.1-5.1-.1-8.1-.1a66 66 0 1066 66 14 14 0 0128 0 94.11 94.11 0 01-94 94z"/></svg></a>
            <a href="javascript:void(0)" id="cahceRemove" onclick="removeCache(this)"><svg style="max-width:22px" xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><title>Remove Cache</title><path d="M432 448a15.92 15.92 0 01-11.31-4.69l-352-352a16 16 0 0122.62-22.62l352 352A16 16 0 01432 448zM431.5 204a16 16 0 00-15.5-12H307.19L335.4 37.63c.05-.3.1-.59.13-.89A18.45 18.45 0 00302.73 23l-92.58 114.46a4 4 0 00.29 5.35l151 151a4 4 0 005.94-.31l60.8-75.16A16.37 16.37 0 00431.5 204zM301.57 369.19l-151-151a4 4 0 00-5.93.31L83.8 293.64A16.37 16.37 0 0080.5 308 16 16 0 0096 320h108.83l-28.09 154.36v.11a18.37 18.37 0 0032.5 14.53l92.61-114.46a4 4 0 00-.28-5.35z"/></svg></a>
        </div>
        <script>
            var doneSvg = '<svg xmlns="http://www.w3.org/2000/svg" style="max-width:22px" class="ionicon" viewBox="0 0 512 512"><title>Process Done</title><path style="color:#3af93a" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M416 128L192 384l-96-96"/></svg>';
            var loadingSvg = '<svg xmlns="http://www.w3.org/2000/svg" style="max-width:22px" class="loading-cahce-process" viewBox="0 0 512 512"><title>Processing</title><path d="M434.67 285.59v-29.8c0-98.73-80.24-178.79-179.2-178.79a179 179 0 00-140.14 67.36m-38.53 82v29.8C76.8 355 157 435 256 435a180.45 180.45 0 00140-66.92" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M32 256l44-44 46 44M480 256l-44 44-46-44"/></svg>';
            var hideTime;
            var inProcess = false;
            var ajaxurl = '<?php echo admin_url('admin-ajax.php') ?>';
            var CurrentUrl = window.location.href;

            jQuery(window).mousemove(function () {
                jQuery('.cache-toolbar').removeClass('cache-toolbar-hide');
                clearTimeout(hideTime);
                hideTime = setTimeout(function () {
                    jQuery('.cache-toolbar').addClass('cache-toolbar-hide');
                }, 2000);
            });
            function refreshCache(_this) {
                if (!inProcess) {
                    inProcess = true;
                    let extIcon = jQuery(_this).html();
                    jQuery(_this).html(loadingSvg);
                    jQuery.post(ajaxurl, {action: 'refresh_cache', curl: CurrentUrl}, function (response) {
                        jQuery(_this).html(doneSvg);
                        jQuery("#cahceRemove").find('path').css('fill', "#fff");
                        setTimeout(function () {
                            jQuery(_this).html(extIcon);
                            inProcess = false;
                        }, 2000);
                    })
                } else {
                    console.log('A Process is Running...')
                }
            }
            function removeCache(_this) {
                if (!inProcess) {
                    inProcess = true;
                    let extIcon = jQuery(_this).html();
                    jQuery(_this).html(loadingSvg);
                    jQuery.post(ajaxurl, {action: 'remove_cache', curl: CurrentUrl}, function (response) {
                        jQuery(_this).html(doneSvg);
                        setTimeout(function () {
                            jQuery(_this).html(extIcon);
                            jQuery(_this).find('path').css('fill', "#8c8c8c");
                            inProcess = false;
                        }, 2000);
                    })
                } else {
                    console.log('A Process is Running...')
                }
            }
        </script>
        <?php
        echo ob_get_clean();
    }

    /**
     * final_output Hook Init Before shutdown 
     */
    public static function FrontEndInit() {
        ob_start();
        add_action('shutdown', function () {
            $final = '';
            $levels = ob_get_level();
            for ($i = 0; $i < $levels; $i++) {
                $final .= ob_get_clean();
            }
            echo apply_filters('final_output', $final);
        }, 0);
    }

    /**
     * Store page content as Static file 
     * @param type $html
     * @return type
     */
    static function storeCache($html) {

        if (self::is_ajax()) {
            return $html;
        }
        if (self::isJson($html)) {
            return $html;
        }

        self::setFilename();
        if (file_exists(self::$fileName)) {//If Exist then skip 
            return $html;
        }
        //createCache::getSuccCachedInfo();
        if (is_user_logged_in()) {
            //echo "<pre>";
            //var_dump($_SERVER);
            //echo "</pre>";
            $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $WPHttp = new \WP_Http_Curl();
            $arg = [
                'headers' => [
                    'User-Agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ""
                ],
                'stream' => false,
                'filename' => "",
                'decompress' => false
            ];
            $WPHttp->request($actual_link, $arg);
        } else {
            $headers = headers_list();
            if (self::$option->response_header == "") {
                self::$option->response_header = implode("\n", $headers);
                self::updateOption((array) self::$option);
            }
            //$html;
            self::dirInit();

            if (trim($html) != "") {
                self::storeInfo();
                if (strpos($html, "wp-login.php") === false && strpos($html, "bulk-tag.zip") === false) {
                    file_put_contents(self::$fileName, $html);
                }
            }
        }

        //createCache::putSuccCachedInfo();
        return $html;
    }

    public static function is_ajax() {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            return true;
        }
        return false;
    }

    public static function storeInfo() {
        createCache::storeOuterData(self::$fullPath);
    }

    public static function isJson($string) {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Close Connection and others after loaded from cache
     * @global type $wpdb
     */
    public static function die() {
        global $wpdb;
        $wpdb->close();
        exit;
    }

}
