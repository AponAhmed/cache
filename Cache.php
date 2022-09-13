<?php

/**
 * Plugin Name: Cache
 * Plugin URI: https://siatexltd.com/wp/plugins/cache
 * Description: Speed Up by loading Static Page from Cache
 * Author: SiATEX
 * Author URI: https://www.siatex.com
 * Version: 3.0.2
 * Text Domain: cachfy-content;
 * Required PHP version: 7.4 or later
 */

namespace CacheFy;

use CacheFy\src\FrontEnd;
use CacheFy\src\CacheAdmin;

/* Plugin Defination */

define('CFY', dirname(__FILE__));
if (!defined('CFY_DIR')) {
    define('CFY_DIR', WP_CONTENT_DIR . "/cache/");
}
define('__CFY_ASSETS', plugin_dir_url(__FILE__) . "assets/");
define('CFY_DEBUG', true);

require 'vendor/autoload.php';

/**
 * WP Plugin To Speed Up by loading Static Page from Cache
 *
 * @author Apon
 */
//WPMU_PLUGIN_DIR;
//muplugins_loaded
//plugins_loaded

class Cache {

    use src\Methods;

    public object $frontEnd;
    public object $cacheAdmin;

    //put your code here
    public function __construct() {

        register_activation_hook(__FILE__, [self::class, 'cache_pre_active_task']);
        register_deactivation_hook(__FILE__, [self::class, 'cache_uninstall']);
        //Frontend Ajax Action
        add_action('wp_ajax_refresh_cache', [$this, 'refresh_cache']);
        //add_action('wp_ajax_nopriv_refresh_cache', [$this, 'refresh_cache']);

        add_action('wp_ajax_remove_cache', [$this, 'remove_cache']);
        add_action('wp_ajax_nopriv_remove_cache', [$this, 'remove_cache']);

        if (is_admin()) {
            $this->cacheAdmin = new CacheAdmin;
        } else {
            if (!self::is_wplogin()) {
                $this->frontEnd = new FrontEnd();
            }
        }
    }

    function refresh_cache() {
        if (isset($_POST['curl'])) {
            $lnk = trim($_POST['curl']);
            $cache = new src\createCache();
            $cache->deleteCacheFile($lnk);
            $cache->file_get_contents_curl($lnk);
        }
        wp_die();
    }

    function remove_cache() {
        if (isset($_POST['curl'])) {
            $lnk = trim($_POST['curl']);
            $cache = new src\createCache();
            $cache->deleteCacheFile($lnk);
        }
        wp_die();
    }

    /**
     * Plugin Initialize
     * @return \CacheFy\Cache
     */
    static function init() {
        return new Cache();
    }

    /**
     * Plugin Active Hook
     */
    public static function cache_pre_active_task() {
        self::dirInit();
    }

    /**
     * Plugin Deactivation Hook
     */
    public static function cache_uninstall() {
        self::rrmdir(CFY_DIR);
        self::RemMuLoader();
        self::RemRootLoader();
    }

}

Cache::init();
