<?php

namespace CacheFy\src;

use CacheFy\src\createCache;

/**
 * Description of CacheAdmin
 *
 * @author Apon
 */
class CacheAdmin {

    use Methods;

    //put your code here
    public function __construct() {
        add_action("admin_menu", [$this, "AdminMenu"]);
        add_action('admin_enqueue_scripts', [$this, 'adminScript']);
        add_action('wp_ajax_updateCacheOption', [$this, 'updateCacheOption']);
        add_action('wp_ajax_cleanAllCache', [$this, 'cleanAllCache']);
        add_action('wp_ajax_cacheLoaderRefresh', [$this, 'cacheLoaderRefresh']);

        self::getOption();

        add_action('wp_ajax_cacheCreateRefresh', [createCache::class, 'refresh']);
        add_action('wp_ajax_startAllCache', [createCache::class, 'startAllCache']);
        add_action('wp_ajax_trigCache', [createCache::class, 'trigCache']);

        $this->CreateCache = new createCache();
        add_action('wp_ajax_cacheListView', [$this->CreateCache, 'cacheListView']);
        add_action('wp_ajax_replaceExistingTrig', [$this->CreateCache, 'replaceExistingTrig']);
        add_action('wp_ajax_rq2Server', [$this->CreateCache, 'generate']);
        add_action('wp_ajax_cleanCache', [$this->CreateCache, 'cleanCache']);

        //Admin Bar
        add_action('admin_bar_menu', [self::class, 'cache_admin_bar'], 100);
        add_action('save_post', [$this, 'removeCacheUpdatedPost']);
    }

    public static function countCache() {
        if (is_dir(CFY_DIR)) {
            $scanned_directory = array_diff(scandir(CFY_DIR), array('..', '.', 'index.php'));
            return count($scanned_directory);
        }
        return 0;
    }

    public static function cache_admin_bar($admin_bar) {
        //var_dump($admin_bar);
        $countCache = self::countCache();
        $c = "(<span class='cacheCount'>$countCache</span>)";
        $admin_bar->add_menu(array('id' => 'cfycache', 'title' => '<div style="display:flex"><svg xmlns="http://www.w3.org/2000/svg" style="max-width:18px;margin-right:2px" class="svg-icon" viewBox="0 0 512 512"><title>Cache Tools</title><path d="M315.27 33L96 304h128l-31.51 173.23a2.36 2.36 0 002.33 2.77h0a2.36 2.36 0 001.89-.95L416 208H288l31.66-173.25a2.45 2.45 0 00-2.44-2.75h0a2.42 2.42 0 00-1.95 1z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="28"/></svg>Cache ' . $c . "</div>", 'href' => '#'));
        $admin_bar->add_menu(array('id' => 'settings-cache', 'title' => 'Settings', 'href' => 'tools.php?page=Cache', 'parent' => 'cfycache'));
        $admin_bar->add_menu(array(
            'id' => 'clean-cache',
            'title' => 'Clean All Cache',
            'href' => '#',
            'parent' => 'cfycache',
            'meta' => [
                "onclick" => "cleanAllCache(this,true)"
            ])
        );

        createCache::getInfo();
        $status = createCache::currStatus();
        if ($status) {
            $btnTT = "Stop Generate";
        } else {
            $btnTT = "Start Generate";
        }
        //currStatus

        $admin_bar->add_menu(array(
            'id' => 'generate-cache',
            'title' => $btnTT,
            'href' => '#',
            'parent' => 'cfycache',
            'meta' => [
                "onclick" => "startAllCache(this,true)"
            ])
        );
    }

    /**
     * Menu Register for Admin Page
     */
    public function AdminMenu() {
        add_submenu_page(
                "tools.php", //$parent_slug
                "Cache", //$page_title
                "Cache", //$menu_title
                "manage_options", //$capability
                "Cache", //$menu_slug
                [$this, 'cache_manage_callback'] //Calback
        );
    }

    /**
     * Admin Script Init
     */
    public function adminScript($hook) {
        //if (strpos($hook, 'cache') !== false) {
        wp_enqueue_style('cache-admin-style', __CFY_ASSETS . 'admin-style.css');

        wp_enqueue_script('cache-admin-script', __CFY_ASSETS . 'admin-script.js', array('jquery'), '1.0');
        wp_localize_script('cache-admin-script', 'cacheJsObject', array('ajax_url' => admin_url('admin-ajax.php')));
        //}
    }

    /**
     * Cache Admin View callback
     */
    public function cache_manage_callback() {
        //var_dump(self::$option);
        ?>
        <form>
            <div class="wrap">
                <h2 class='cache-title'>
                    <div class="titleArea">
                        <?php echo get_admin_page_title() ?> &nbsp;&nbsp;&nbsp;
                        <button type="button" class="button button-danger" onclick="cleanAllCache(this)">Clean All Cache</button>
                    </div>
                    <label class="switch">
                        <input <?php echo isset(self::$option->enable) && self::$option->enable == '1' ? "checked" : "" ?> name="cahceOption[enable]" value='1' type="checkbox">
                        <div><span></span></div>
                    </label>
                </h2>
                <hr><br>
                <div class="tab-wrap">
                    <nav class="nav-tab-wrapper">
                        <a href="#cacheSettings" class="nav-tab nav-tab-active ">Settings</a>
                        <a href="#cacheCreate" class="nav-tab">Generate</a>
                        <a href="#dbOptimize" class="nav-tab">DB Optimize</a>
                    </nav>
                    <div class="tab-content">
                        <div class="tab-pane" id="cacheSettings">
                            <div class="cache-option-wrap">
                                <label></label>
                                <label><input <?php echo isset(self::$option->disable_loggedin) && self::$option->disable_loggedin == '1' ? "checked" : "" ?> type="checkbox" value="1" name="cahceOption[disable_loggedin]">&nbsp;Disable When Logged-in</label>
                            </div>
                            <div class="cache-option-wrap">
                                <label>Loader <button title="Refresh Configaration after Change loader" type="button" class='refrashCacheLoader' onclick="cacheLoaderRefresh(this)">Refresh</button></label>
                                <select name="cahceOption[cache_loader]"  class='custom-select'>
                                    <option <?php echo isset(self::$option->cache_loader) && self::$option->cache_loader == 'mu-plugins' ? "selected" : "" ?> value="mu-plugins">Mu-Plugins</option>
                                    <option <?php echo isset(self::$option->cache_loader) && self::$option->cache_loader == 'root' ? "selected" : "" ?> value="root">Root</option>
                                    <option <?php echo isset(self::$option->cache_loader) && self::$option->cache_loader == 'init' ? "selected" : "" ?> value="init">Init</option>
                                </select>
                                <span class="description">&nbsp;&nbsp;&nbsp;Disable option only work with init loader</span>
                            </div>
                            <div class="cache-option-wrap">
                                <label>Headers</label>
                                <div>
                                    <textarea cols="100" rows="8" placeholder="Response Headers" name="cahceOption[response_header]"><?php echo isset(self::$option->response_header) ? self::$option->response_header : "" ?></textarea>
                                    <br><span class="description">Each Header will be new line. Ex: "content-encoding:gzip [line-break] cache-pilicy:none"</span>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane" id="cacheCreate">
                            <br>
                            <!--<button type="button" onclick="rq2Server()">GG</button>-->
                            <div style="display:flex;max-width: 550px;justify-content: space-between;">
                                <div>
                                    <button type="button" onclick="cacheCreateRefresh(this)" title="Load or Refrash with Sitemap" class="btn btn-cms-default"><span class="dashicons dashicons-update"></span></button>
                                    &nbsp; &nbsp;<label><input value="1" onchange="replaceExistingTrig(this)" type="checkbox" <?php echo isset($this->CreateCache->replaceExisting) && $this->CreateCache->replaceExisting == 'true' ? "checked" : "" ?> name='cahceOption[replaceExisting]'>&nbsp;Replace Existing Cache</label>
                                </div>
                                <button type="button" onclick="startAllCache(this)" class="btn btn-cms-default btn-sm">Start All</button>
                            </div>
                            <div class="ListWrap">
                                <!-- Ajax -->
                            </div>
                        </div>
                        <div class="tab-pane" id="dbOptimize">
                            <br>
                            DB Optimize Tab.
                        </div>
                    </div>
                </div>


                <br>
                <hr>
                <button type="button" class="button button-primary" onclick="updateCacheOption(this)">Update</button>
            </div>
        </form>
        <?php
    }

    public function removeCacheUpdatedPost($post_id) {
        global $wpdb;
        $project = false;
        if (class_exists('\MPG_Constant')) {
            $projects = $wpdb->get_results("SELECT urls_array From {$wpdb->prefix}" . \MPG_Constant::MPG_PROJECTS_TABLE . " where  template_id=$post_id and exclude_in_robots !=0");
        }

        //echo "<pre>";
        if ($projects) {
            $rootUrl = get_site_url();
            foreach ($projects as $project) {
                $linksArray = json_decode($project->urls_array);
                foreach ($linksArray as $linkSuff) {
                    $link = $rootUrl . "/" . $linkSuff;
                    $link = preg_replace('/([^:])(\/{2,})/', '$1/', $link);
                    $this->CreateCache->deleteCacheFile($link);
                }
            }
        } else {
            $link = get_permalink($post_id);
            $homePageID = get_option('page_on_front');
            if ($homePageID == $post_id) {
                $link = trim($link, "/");
            }
            $this->CreateCache->deleteCacheFile($link);
        }
    }

    /**
     * 
     */
    function updateCacheOption() {
        $data = [];
        parse_str($_POST['data'], $data);
        self::updateOption($data['cahceOption']);
        self::loaderConfig();
        wp_die();
    }

    function cleanAllCache() {
        self::rrmdir(CFY_DIR);
        createCache::refresh();
    }

    function cacheLoaderRefresh() {
        self::loaderConfig();
    }

}
