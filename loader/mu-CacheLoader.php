<?php

namespace CacheFy\src;

/**
 * Description of CacheLoader
 * A Mu-Plugin For Cache 
 * @author apon
 */
if (!defined('CFY_DIR')) {
    define('CFY_DIR', WP_CONTENT_DIR . "/cache/");
}

class CacheLoader {

    //put your code here
    static string $optionKey = 'cache_options';
    public static $current_url;
    public static $fileName;
    public static object $option;

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
        self::setUrl();
        self::getOption();
        if (isset(self::$option->enable) && !is_admin() && file_exists(self::$fileName)) {
            //echo "Mu plugin Event";
            header('cache-type:Mu-Event');
            echo file_get_contents(self::$fileName);
            self::die();
        }
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

    public static function getOption() {
        $opt = get_option(self::$optionKey);
        if (is_array($opt)) {
            self::$option = (object) $opt;
        } else {
            self::$option = (object) [];
        }
        return self::$option;
    }

}

CacheLoader::run();
