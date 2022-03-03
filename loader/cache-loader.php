<?php

namespace CacheFyRoot;

if (!defined('CFY_DIR')) {
    define('CFY_DIR', dirname(__FILE__) . "/wp-content/cache/");
}
if (!defined('CFY_HEADERS')) {
    define('CFY_HEADERS', 'CFY_HEADERS_JSON');
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
    public $defaultHeader;

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
            self::headerBuilder();
            header('cache-type:Root-Event');
            header("cache-control:public, max-age=31536000");
            header("expires:" . date("D, j M Y G:i:s", time() + 31536000) . " GMT");
            header("content-type:text/html");
            header("pragma:public");
            echo file_get_contents(self::$fileName);
            exit;
        }
    }

    public static function headerBuilder() {
        if (defined('CFY_HEADERS')) {
            $headerJson = json_decode(CFY_HEADERS, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($headerJson) && count($headerJson) > 0) {
                foreach ($headerJson as $header) {
                    header($header);
                }
            }
        }
    }

}

RootCacheLoader::run();
