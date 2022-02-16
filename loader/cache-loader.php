<?php

namespace CacheFyRoot;

if (!defined('CFY_DIR')) {
    define('CFY_DIR', dirname(__FILE__) . "/wp-content/cache/");
}

/**
 * Description of cache-loader
 *
 * @author Apon
 */
class RootCacheLoader {

    //put your code here
    public static $current_url;
    public static $fileName;

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
        if (file_exists(self::$fileName)) {
            //echo "Root Event";
            header('cache-type:Root-Event');
            echo file_get_contents(self::$fileName);
            exit;
        }
    }

}

RootCacheLoader::run();
