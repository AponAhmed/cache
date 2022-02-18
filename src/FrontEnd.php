<?php

namespace CacheFy\src;

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
        add_filter('final_output', [self::class, 'storeCache']);
    }

    /**
     * Initialize hook after  plugins_loaded
     */
    public static function init() {
        self::getOption();
        self::setFilename();
        if (isset(self::$option->enable)) {
            if (file_exists(self::$fileName)) {
                //CacheLoader::run();//plugins_loaded Event
                //echo "Init Event";
                header('cache-type:init-event');
                echo file_get_contents(self::$fileName);
                self::die();
            } else {
                self::FrontEndInit();
            }
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
        self::dirInit();
        if (trim($html) != "") {
            file_put_contents(self::$fileName, $html);
        }
        return $html;
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
