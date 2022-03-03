<?php

namespace CacheFy\src;

/**
 *
 * @author Apon
 */
define('TYPE_LINKS_DIR', ABSPATH . "sitemaps/type/");

trait CacheInfo {

    /**
     * Status Information of Cache Generation
     * @var string
     */
    private static string $cacheInfoKey = 'CacheInfoData';
    static string $cachedFile = WP_CONTENT_DIR . "/cached";
    static array $cachedLinks = [];

    /**
     * Cache Information
     * [
     *  'post'=>[
     *      label=>'Posts'
     *      total=>10,
     *      last=>2,
     *      status=>init//default is 'init' and others are 1/0 and complete
     *      ]
     * ]
     * @var object $info
     */
    public static object $info;

    /**
     * 
     * @var type
     */
    public static $currentType;

    static function init() {
        self::getInfo();
        //echo "<pre>";
        //var_dump(self::$info);
        //exit;
    }

    static function Bug() {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
    }

    //put your code here
    public static function isDir() {
        if (is_dir(TYPE_LINKS_DIR)) {
            return true;
        }
        return false;
    }

    /**
     * To read XML file's Information
     * @param type $fileName
     */
    public static function xmlFileInfo($type = false) {
        $fName = $type;
        if ($type === false) {
            $fName = self::$currentType;
        }
        $file = ABSPATH . "/sitemaps/type/" . $fName . ".xml";
        $xmlData = simplexml_load_file($file);

        if (count($xmlData->url)) {
            return $xmlData->url;
        }
        return [];
    }

    /**
     * Set Post Type Info
     * @param string $type post or taxonomy
     */
    public static function SetInfo($type = 'post') {
        $label = self::$currentType;
        if ($type == 'post') {
            $PosttypeObj = get_post_type_object(self::$currentType);
            if ($PosttypeObj) {
                $label = $PosttypeObj->labels->name;
            }
        } else {
            //Taxonomy Info
            $label = get_taxonomy(self::$currentType)->label;
        }

        $totalLinks = count(self::xmlFileInfo());

        return (object) [
                    'label' => $label,
                    'type' => self::$currentType,
                    'total' => $totalLinks,
                    'last' => false,
                    'done' => 0,
                    'status' => 'init'
        ];
        //self::$info->type = self::$currentType;
        //var_dump(self::$info);
        //self::$info->self::$currentType
    }

    public static function removeSuccCachedInfo() {
        if (file_exists(self::$cachedFile)) {
            self::$cachedLinks = [];
            $inf = unlink(self::$cachedFile);
        }
    }

    public static function getSuccCachedInfo() {
        if (file_exists(self::$cachedFile)) {
            $content = file_get_contents(self::$cachedFile);
            if (!empty($content)) {
                self::$cachedLinks = json_decode($content, true);
            }
            //var_dump(self::$cachedLinks);
        }
    }

    public static function removeLinkFromCached($link) {
        self::getSuccCachedInfo();
        $indx = array_search($link, self::$cachedLinks);
        unset(self::$cachedLinks[$indx]);
        //self::$cachedLinks = self::$cachedLinks;
        self::putSuccCachedInfo();
    }

    public static function putSuccCachedInfo() {

        if (isset(self::$cachedLinks)) {
            return file_put_contents(self::$cachedFile, json_encode(array_unique(self::$cachedLinks)));
        }
        return false;
    }

    /**
     * 
     */
    public static function refresh() {
        self::init();
        self::removeSuccCachedInfo();
        self::Bug(); //Find BUG
        $info = [];
        if (self::isDir()) {
            $postTypes = get_option('sitemap_post_types');
            $types = [];
            if (!empty($postTypes)) {
                $types = json_decode($postTypes);
            }
            $types[] = 'repeatable'; //Additional Type for Repeatable Pages
            foreach ($types as $postType) {
                self::$currentType = $postType;
                $info[self::$currentType] = self::SetInfo('post');
            }
            //var_dump($types);
            $taxonomies = get_option('sitemap_taxonomies');
            $taxTypes = [];
            if (!empty($taxonomies)) {
                $taxTypes = json_decode($taxonomies);
            }
            //var_dump($taxTypes);

            foreach ($taxTypes as $taxo) {
                self::$currentType = $taxo;
                $info[self::$currentType] = self::SetInfo('taxo');
            }
            self::$info = (object) $info;
        } else {
            //sitemap Plugin not installed or not generated
        }
        self::putInfo();
        wp_die();
    }

    static function getInfo() {
        self::getSuccCachedInfo();
        $inf = get_option(self::$cacheInfoKey);

        if (!empty($inf) && is_object($inf)) {
            $inf = $inf;
        } else {
            $inf = (object) [];
        }
        self::$info = $inf;
        return self::$info;
    }

    static function putInfo() {
        self::putSuccCachedInfo();
        update_option(self::$cacheInfoKey, self::$info);
        return;
    }

    static function updateStatus($type, $status = false) {
        if (!$status) {
            if (self::$info->$type->status === false || self::$info->$type->status === "init") {
                $status = true;
            } else {
                $status = false;
            }
        }
        if (self::$info->$type->status === "complete") {
            $status = true;
            self::$info->$type->last = false;
        }

        self::$info->$type->status = $status;
        if (self::putInfo()) {
            return true;
        }
        return false;
    }

    static function trigCache() {
        if (isset($_POST['type'])) {
            $type = trim($_POST['type']);
            if (self::updateStatus($type)) {
                self::cacheListView();
            }
        }
    }

    public static function currStatus() {
        if (self::$info) {
            foreach (self::$info as $inf) {
                if ($inf->status === true) {
                    return true;
                    break;
                }
            }
        }
        return false;
    }

    static function startAllCache() {
        if (self::currStatus()) {
            //have to stop
            self::allTrig(false);
            echo "Start All";
        } else {
            //have to start 
            //update_option('cache_pause', 'false');
            self::allTrig(true);
            echo "Stop All";
        }
        wp_die();
    }

    public static function allTrig($status) {
        if (self::$info) {
            foreach (self::$info as $type => $info) {
                self::$info->$type->status = $status;
            }
            self::putInfo();
        }
    }

}
