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
                                    <textarea cols="40" rows="4" placeholder="Response Headers" name="cahceOption[response_header]"><?php echo isset(self::$option->response_header) ? self::$option->response_header : "" ?></textarea>
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

    function countPages() {
        
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
    }

    function cacheLoaderRefresh() {
        self::loaderConfig();
    }

}
