<?php

namespace CacheFy\src;

/**
 * Description of FrontEnd
 *
 * @author Apon
 */
class FrontEnd {

    //put your code here
    public function __construct() {
        add_filter('final_output', [self::class, 'storeCache']);
    }

    public static function FrontEndInit() {
        ob_start();

        add_action('shutdown', function () {
            $final = '';
            $levels = ob_get_level();
            for ($i = 0; $i < $levels; $i++) {
                $final .= ob_get_clean();
            }
            apply_filters('final_output', $final);
        }, 0);
    }

    static function storeCache($html) {
        var_dump($html);
    }

}
