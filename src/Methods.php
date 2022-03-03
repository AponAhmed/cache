<?php

namespace CacheFy\src;

/**
 *
 * @author Apon
 */
trait Methods {

//put your code here
    static string $optionKey = 'cache_options';
    static object $option;
    static string $current_url;
    static string $fullPath;
    static string $fileName;
    static string $loaderRoot = ABSPATH . "cache-loader.php";
    static string $loaderMu = WPMU_PLUGIN_DIR . "/mu-CacheLoader.php";
    static array $headers;

    public static function loaderConfig() {
        self::SetHeader();
        self::dirInit();
        //self::$loaderRoot = ABSPATH . "cache-loader.php";
        //self::$loaderMu = WPMU_PLUGIN_DIR . "/mu-CacheLoader.php";

        if (!isset(self::$option->enable) || isset(self::$option->disable_loggedin)) {
            self::$option->cache_loader = "init";
        }

        if (self::$option->cache_loader == 'root') {
            //Root loader file Copy
            //Loader Include in index
            self::putRootLoader();
            self::RemMuLoader();
        } elseif (self::$option->cache_loader == 'mu-plugins') {
            //remove root config if exists 
            self::RemRootLoader();
            //Copy Mu-Plugin in MU-Plugin folder and Initiate
            self::putMuLoader();
        } else {
            //remove root and mu-plugin 
            self::RemRootLoader();
            self::RemMuLoader();
        }
    }

    /**
     * Parse header Information from header and set into self::$header Property as Array
     */
    public static function SetHeader() {
        self::getOption();
        //parse Header string, turn into array and put self::$headers
        $headers = preg_split("/\r\n|\n|\r/", trim(self::$option->response_header));
        self::$headers = array_filter(array_map('trim', $headers));
    }

    /**
     * Remove Root Cache Loader
     */
    public static function RemRootLoader() {
        if (file_exists(self::$loaderRoot)) {
            unlink(self::$loaderRoot);
            self::modifyLoader('remove');
        }
    }

    /**
     * Remove MU plugin With Condition
     */
    public static function RemMuLoader() {
        if (file_exists(self::$loaderMu)) {
            unlink(self::$loaderMu);
        }
    }

    /**
     * Mu Plugin Creation 
     */
    public static function putMuLoader() {
        $file = CFY . "/loader/mu-CacheLoader.php";
        $scripts = file_get_contents($file);
        //CFY_HEADERS_JSON //Key
        //put header information in loader file before create
        $scripts = self::str_replace_first('CFY_HEADERS_JSON', json_encode(self::$headers), $scripts);
        $n = file_put_contents(self::$loaderMu, $scripts);
        // var_dump($n);
        //copy($file, self::$loaderMu);
    }

    /**
     * Put a loader file in root as cache-loader.php 
     */
    public static function putRootLoader() {
        $file = CFY . "/loader/cache-loader.php";
        $scripts = file_get_contents($file);
        //put header information in loader file before create
        $scripts = self::str_replace_first('CFY_HEADERS_JSON', json_encode(self::$headers), $scripts);
        file_put_contents(self::$loaderRoot, $scripts);
        //copy($file, self::$loaderRoot);
        self::modifyLoader();
    }

    /**
     * Replace String First Match
     * @param string $search
     * @param mix $replace
     * @param mix $subject
     * @return string
     */
    static function str_replace_first($search, $replace, $subject) {
        $search = '/' . preg_quote($search, '/') . '/';
        return preg_replace($search, $replace, $subject, 1);
    }

    /**
     * Modify Main Index file to pach-up Cache Loader
     * @param string $state
     */
    public static function modifyLoader(string $state = 'add') {
        $str = "#START CACHE LOADER
require 'cache-loader.php';
#END CACHE LOADER\n
/**";
        $indexContent = file_get_contents(ABSPATH . "index.php");
        if ($state == 'add') {
            $indexContent = str_replace($str, "/**", $indexContent); //Remove Existing

            $indexContent = self::str_replace_first("/**", "$str", $indexContent);
        } else {
            $indexContent = str_replace($str, "/**", $indexContent);
        }

        file_put_contents(ABSPATH . "index.php", $indexContent);
    }

    static function is_ssl() {
        if (isset($_SERVER['HTTPS'])) {
            if ('on' == strtolower($_SERVER['HTTPS']))
                return true;
            if ('1' == $_SERVER['HTTPS'])
                return true;
        } elseif (isset($_SERVER['SERVER_PORT']) && ( '443' == $_SERVER['SERVER_PORT'] )) {
            return true;
        }
        return false;
    }

    /**
     * Request Path Information
     */
    public static function setFilename() {
        $rootScript = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : $_SERVER['PHP_SELF'];
        $pathInfo = pathinfo($rootScript);
        if (isset($pathInfo['dirname'])) {
            if ($pathInfo['dirname'] != "/") {
                $rqUri = str_replace($pathInfo['dirname'], "", $_SERVER['REQUEST_URI']);
            } else {
                $rqUri = $_SERVER['REQUEST_URI'];
            }
        }
        self::$current_url = $rqUri;

        $siteUrl = site_url();
        $fullPath = $siteUrl . "/" . $rqUri;
        $fullPath = preg_replace('/([^:])(\/{2,})/', '$1/', $fullPath);
        self::$fullPath = $fullPath;
        //var_dump(self::$fullPath);
        //exit;
        $md5 = true;
        if ($md5) {//md5
            self::$fileName = CFY_DIR . md5(self::$current_url);
        } else {
            $modFileName = str_replace(["/"], ["~"], self::$current_url);
            self::$fileName = CFY_DIR . $modFileName;
        }
    }

    /**
     * Remove Files and Folder Recursively 
     * @param type $dir
     */
    public static function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object))
                        self::rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                    else
                        unlink($dir . DIRECTORY_SEPARATOR . $object);
                }
            }
            rmdir($dir);
        }
    }

    /**
     * Cache Directory create and set permission
     */
    public static function dirInit() {
        if (!is_dir(WPMU_PLUGIN_DIR)) {
            if (mkdir(WPMU_PLUGIN_DIR, 0777, true)) {
                chmod(WPMU_PLUGIN_DIR, 0777);
                file_put_contents(WPMU_PLUGIN_DIR . "/index.php", "<?php //Silence is golden");
            }
        }
        if (!is_dir(CFY_DIR)) {
            if (mkdir(CFY_DIR, 0777, true)) {
                chmod(CFY_DIR, 0777);
                file_put_contents(CFY_DIR . "index.php", "<?php //Silence is golden");
            }
        }
    }

    /**
     * Update option by Default Serializing
     * @param array $option
     * @return boolean 
     */
    public static function updateOption(Array $option) {
        if (update_option(self:: $optionKey, $option)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get option 
     * @return Object $option
     */
    public static function getOption() {
        $opt = get_option(self::$optionKey);
        if (is_array($opt)) {
            self::$option = (object) $opt;
        } else {
            self::$option = (object) [];
        }
        if (!isset(self::$option->cache_loader)) {
            self::$option->cache_loader = 'mu-plugins';
        }
        return self::$option;
    }

    static function is_wplogin() {
        $ABSPATH_MY = str_replace(array('\\', '/'), DIRECTORY_SEPARATOR, ABSPATH);
        return ((in_array($ABSPATH_MY . 'wp-login.php', get_included_files()) || in_array($ABSPATH_MY . 'wp-register.php', get_included_files()) ) || (isset($_GLOBALS['pagenow']) && $GLOBALS['pagenow'] === 'wp-login.php') || $_SERVER['PHP_SELF'] == '/wp-login.php');
    }

}
