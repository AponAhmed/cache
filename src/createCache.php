<?php

namespace CacheFy\src;

/**
 * Description of createCache
 *
 * @author Apon
 */
class createCache {

    public $max_process = 1;
    public $replaceExisting;
    public $domainAdd;

    //put your code here
    use CacheInfo;

    public function __construct($max = 1) {
        $this->replaceExisting = get_option('replaceExistingCache');
        $this->domainAdd = site_url();
        $this->max_process = $max;
        self::init();
    }

    public function cacheListView() {
        //self::$currentType = 'page';
//        $data = self::xmlFileInfo();
//        echo "<pre>";
//        $urls = [];
//        foreach ($data as $uu) {
//            $urls[] = trim($uu->loc);
//        }
//        $availableUrls = array_diff($urls, self::$cachedLinks);
//        var_dump($availableUrls);
//        //exit;

        self::Bug();
        //echo "<pre>";
        foreach (self::$info as $type => $inf) {
            //var_dump($inf);
            //Percentage Calculation
            $prs = 0;
            //if ($inf->last !== false) {
            if ($inf->done !== 0) {
                if ($inf->total > 0) {
                    //$prs = (100 / $inf->total) * ($inf->last + 1);
                    $prs = (100 / $inf->total) * ($inf->done);
                }
            }
            //so far Complete Status
            $dn = 0;
            if ($inf->last !== false || $inf->done !== 0) {
                if ($inf->last < $inf->total || $inf->done !== 0) {
                    // $dn = $inf->last + 1;
                    $dn = $inf->done;
                } else {
                    $dn = $inf->done;
                }
            }
//            if ($dn >= $inf->total) {
//                $inf->status = "complete";
//            }
            //Triger Check
            $jsFunc = "trigCache";
            if ($inf->status === "complete") {
                $trg = "Re-Cache";
                $jsFunc = "reCacheSingle";
            } else {
                $trg = $inf->status === "init" || $inf->status === false ? "Start" : "Stop";
            }
            $id = str_replace(array(" ", "-"), "_", $inf->type);

            if ($inf->total > 0) {
                echo "<div class='cache-info-type-list-item' id='" . $id . "'>"
                . "<span class='prog' style='width:$prs%;'></span>"
                . "<span class='postType' title='$inf->type'>" . $inf->label . "</span>"
                . "<span class='postType'>" . $dn . " of " . $inf->total . "</span>";

                echo "<div class='itemControl'>"
                . "<button type='button' class='cacheCbtn cln' onclick='cleanCache(\"$inf->type\",this)'>Clean</button>"
                . "<button type='button' class='cacheCbtn $trg' onclick='$jsFunc(\"$inf->type\",this)'>$trg</button></div>"
                . "</div>";

                echo "</div>";
            }
        }
        wp_die();
    }

    public function generate() {
        //echo "<pre>";
        self::Bug();
        $isChanged = false;
        $lastInfo = get_option('lastInfo');
        if ($lastInfo != "") {
            self::$currentType = $lastInfo;
        }
        if (!self::$currentType) {
            $firstType = current((Array) self::$info);
            if ($firstType) {
                self::$currentType = $firstType;
                self::$currentType = $firstType->type;
            }
        }
        $n = 0;
        if (self::$info) {
            $brk = false;
            foreach (self::$info as $type => $info) {
                if ($info->status === true) {
                    $isChanged = true;
                    //Current Type set
                    self::$currentType = $type;

                    $data = self::xmlFileInfo();
                    $urls = [];
                    foreach ($data as $uu) {
                        $urls[] = trim($uu->loc);
                    }
                    self::getSuccCachedInfo();
                    $availableUrls = array_diff($urls, self::$cachedLinks);
                    $availableUrls = array_values($availableUrls);
                    //var_dump($urls, self::$cachedLinks, $availableUrls);
                    //var_dump($obj->loc);
                    //$obj = $data[$currentExe];
                    if (count($availableUrls) > 0) {
                        foreach ($availableUrls as $ll) {
                            $this->cacheG($ll);
                            self::$info->$type->done += 1;
                            if (self::$info->$type->done >= count($urls)) {
                                self::$info->$type->done = count($urls);
                            }
                            self::$cachedLinks[] = (string) $ll;
                            $n++;
                            if ($n == $this->max_process) {
                                $brk = true;
                                break;
                            }
                        }
                        //$ll = $availableUrls[0];
                    } else {
                        $info->done = $info->total;
                    }
                    //Update Last index
                    //self::$info->$type->last = $currentExe;
                    //Set Complete 
                    if ($info->done >= $info->total) {
                        self::$info->$type->status = 'complete';
                        self::$info->$type->last = $info->total;
                    }
                    //$this->info->$type->status = true;
                    //var_dump($this->info);
                }
                if ($n == $this->max_process || $brk) {
                    break;
                }
            }
        }
        //echo 1;
        if ($isChanged) {
            $this->updateInfo();
            $this->cacheListView();
        } else {
            echo "Complete";
        }
        wp_die();
    }

    function cacheG($url) {
        // var_dump(self::$cachedLinks);

        if ($this->replaceExisting == "true") {
            $this->deleteCacheFile($url);
            $this->file_get_contents_curl($url);
        } else {
            if (!$this->cacheExist($url)) {
                $this->file_get_contents_curl($url);
            }
        }
    }

    public function cacheExist($lnk) {
        $dom = $this->domainAdd;
        $dom = str_replace(array('http://', 'https://', 'www.'), array("", "", ""), $dom);
        $lnk = str_replace(array('http://', 'https://', 'www.'), array("", "", ""), $lnk);

        $re = "#$dom(.*)$#";
        preg_match($re, $lnk, $matches, PREG_OFFSET_CAPTURE, 0);
        $RQURL = "";
        if ($matches) {
            $RQURL = $matches[1][0];
        }
        $RQURL = "$RQURL";
        //Delete Previous Cache
        if ($RQURL == "") {
            $RQURL = "/";
        }
        //var_dump($RQURL);

        $file = md5($RQURL);
        $filename = CFY_DIR . "$file";

        if (file_exists($filename)) {
            return $filename;
        }
        return false;
    }

    /*
     * Single Delete by Link
     */

    function deleteCacheFile($lnk) {
        $file = $this->cacheExist($lnk);
        self::removeLinkFromCached($lnk);
        if ($file) {
            if (!unlink($file)) {
                return false;
            } else {
                return true;
            }
        }
        return true;
    }

    function file_get_contents_curl($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }

    function updateInfo() {
        self::putInfo();
        update_option('lastInfo', self::$currentType); //Update Last Info
    }

    function replaceExistingTrig() {
        $status = $_POST['status'];
        update_option('replaceExistingCache', $status);
        wp_die();
    }

    function cleanCache() {
        self::Bug();
        $type = $_POST['type'];
        $this->cleanCacheByType($type);
        $this->cacheListView();
    }

    function reCache() {
        self::Bug();
        $type = $_POST['type'];
        $singleInfo = self::$info->$type;
        $lnks = self::xmlFileInfo($singleInfo->type);
        foreach ($lnks as $url) {
            $link = $url->loc;
            self::removeLinkFromCached($link);
        }

        self::$info->$type->last = 0;
        self::$info->$type->status = true;
        self::$info->$type->done = 0;
        //var_dump(self::$info->$type);
        $this->updateInfo();

        $this->cacheListView();
    }

    public function cleanCacheByType($type) {
        $singleInfo = self::$info->$type;
        $lnks = self::xmlFileInfo($singleInfo->type);
        foreach ($lnks as $url) {
            $link = $url->loc;
            $this->deleteCacheFile($link);
        }
        self::$info->$type->last = false;
        self::$info->$type->status = "init";
        self::$info->$type->done = 0;
        $this->updateInfo();
    }

    public static function storeOuterData($url) {
        global $post, $wp_query;
        if (!$wp_query) {
            return;
        }
        $current_page_id = $wp_query->get_queried_object_id();
        $post = get_post($current_page_id);

        $homePageID = get_option('page_on_front');
        if ($homePageID == @$post->ID) {
            $url = trim($url, "/");
        }
        $type = @$post->post_type;
        $projectId = false;
        if (class_exists('\MPG_Constant')) {
            $path = \MPG_Helper::mpg_get_request_uri(); // это та часть что идет после папки установки WP. тпиа wp.com/xxx
            $redirect_rules = \MPG_CoreModel::mpg_get_redirect_rules($path);
            $projectId = @$redirect_rules['template_id'];
        }
        if ($projectId) {
            $type = 'repeatable';
        }
        self::init();
        if ($type != "" && isset(self::$info->$type)) {
            if (!in_array($url, self::$cachedLinks)) {
                self::$cachedLinks[] = $url;

                self::$info->$type->done += 1;
                //var_dump(self::$cachedLinks, self::$info->$type);
                //exit;
                self::putInfo();
            }
        }
    }

}
