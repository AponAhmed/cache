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
    static string $fileName;
    static string $loaderRoot;
    static string $loaderMu;
    static array $headers;

    public static function loaderConfig() {
        self::SetHeader();
        self::dirInit();
        self::$loaderRoot = ABSPATH . "cache-loader.php";
        self::$loaderMu = WPMU_PLUGIN_DIR . "/mu-CacheLoader.php";

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

    public static function SetHeader() {
        //parse Header string, turn into array and put self::$headers
    }

    public static function RemRootLoader() {
        if (file_exists(self::$loaderRoot)) {
            unlink(self::$loaderRoot);
            self::modifyLoader('remove');
        }
    }

    public static function RemMuLoader() {
        if (file_exists(self::$loaderMu)) {
            unlink(self::$loaderMu);
        }
    }

    public static function putMuLoader() {
        $file = CFY . "/loader/mu-CacheLoader.php";
        //put header information in loader file before create
        copy($file, self::$loaderMu);
    }

    public static function putRootLoader() {
        $file = CFY . "/loader/cache-loader.php";
        //put header information in loader file before create
        copy($file, self::$loaderRoot);
        self::modifyLoader();
    }

    static function str_replace_first($search, $replace, $subject) {
        $search = '/' . preg_quote($search, '/') . '/';
        return preg_replace($search, $replace, $subject, 1);
    }

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
     * Remove Files and Folder Recursively 
     * @param type $dir
     */
    function rrmdir($dir) {
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
            }
        }
        if (!is_dir(CFY_DIR)) {
            if (mkdir(CFY_DIR, 0777, true)) {
                chmod(CFY_DIR, 0777);
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

}
