<?php

namespace CacheFy\src;

use CacheFy\src\CacheLoader;

/**
 * Description of FrontEnd
 *
 * @author Apon
 */
class FrontEnd {

    public static $current_url;
    public static $fileName;

    //put your code here
    public function __construct() {
        //add_action('init', [self::class, 'init'], -9999);
        add_action('plugins_loaded', [self::class, 'init'], -9999);
        add_filter('final_output', [self::class, 'storeCache']);
    }

    /**
     * Cache Directory create and set permission
     */
    public static function dirInit() {
        if (!is_dir(CFY_DIR)) {
            if (mkdir(CFY_DIR, 0777, true)) {
                chmod(CFY_DIR, 0777);
            }
        }
    }

    public static function setFilename() {
        $rootScript = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : $_SERVER['PHP_SELF'];
        $pathInfo = pathinfo($rootScript);
        if (isset($pathInfo['dirname'])) {
            $rqUri = str_replace($pathInfo['dirname'], "", $_SERVER['REQUEST_URI']);
        }
        self::$current_url = $rqUri;
        self::$fileName = CFY_DIR . md5(self::$current_url);
    }

    /**
     * Initialize hook after  plugins_loaded
     */
    public static function init() {
        self::dirInit();
        self::setFilename();

        if (file_exists(self::$fileName)) {
            CacheLoader::run();
        } else {
            self::FrontEndInit();
        }
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
        //$html;
        //file_put_contents(self::$fileName, $html);
        return $html;
    }

}
