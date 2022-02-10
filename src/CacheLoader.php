<?php

namespace CacheFy\src;

/**
 * Description of CacheLoader
 *
 * @author apon
 */
if (!defined('CFY_DIR')) {
    define('CFY_DIR', WP_CONTENT_DIR . "/cache/");
}

class CacheLoader {

    //put your code here
    public static $current_url;
    public static $fileName;

    /**
     * Set Current Request URL and File name 
     */
    public static function setUrl() {
        $rootScript = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : $_SERVER['PHP_SELF'];
        $pathInfo = pathinfo($rootScript);
        if (isset($pathInfo['dirname'])) {
            $rqUri = str_replace($pathInfo['dirname'], "", $_SERVER['REQUEST_URI']);
        }
        self::$current_url = $rqUri;
        self::$fileName = CFY_DIR . md5(self::$current_url);
    }

    public static function run() {
        echo "------CACHE LOADER------";
        self::die();
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
