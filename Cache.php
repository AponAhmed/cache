<?php

/**
 * Plugin Name: Cache
 * Plugin URI: https://siatexltd.com/wp/plugins/cache
 * Description: Speed Up by loading Static Page from Cache
 * Author: SiATEX
 * Author URI: https://www.siatex.com
 * Version: 1.0
 * Text Domain: cachfy-content;
 */

namespace CacheFy;

use CacheFy\src\FrontEnd;

require 'vendor/autoload.php';

/**
 * WP Plugin To Speed Up by loading Static Page from Cache
 *
 * @author Apon
 */
class Cache {

    public object $frontEnd;

    //put your code here
    public function __construct() {
        if (is_admin()) {
            
        } else {
            $this->frontEnd = new FrontEnd();
            $this->frontEnd::FrontEndInit();
        }
    }

}

$cache = new Cache();
