//Global Object cacheJsObject

/**
 * Ajax Request To Update Option
 * @param {DOMObject} _this
 * @returns {void}
 */
function updateCacheOption(_this) {
    console.log('Updating..');
    let btn = jQuery(_this);
    btn.find(".dashicons").remove();
    btn.prepend('<span class="dashicons dashicons-update loading"></span>');
    var data = {action: "updateCacheOption", data: jQuery(_this).closest('form').serialize()};
    jQuery.post(cacheJsObject.ajax_url, data, function (response) {
        btn.find('.loading').removeClass('loading dashicons-update').addClass('dashicons-saved');

    });
}
/**
 * Clean Cache Ajax Request Builder
 * @param {DOMObject} _this
 * @returns {void}
 */
function cleanAllCache(_this) {
    console.log('Cleaning..');
    let btn = jQuery(_this);
    btn.find(".dashicons").remove();
    btn.prepend('<span class="dashicons dashicons-update loading"></span>');
    var data = {action: "cleanAllCache"};
    jQuery.post(cacheJsObject.ajax_url, data, function (response) {
        console.log(response);
        btn.find('.loading').removeClass('loading dashicons-update').addClass('dashicons-saved');

    });
}

/**
 * 
 */
function cacheLoaderRefresh(_this) {
    console.log('Refreshing..');
    let btn = jQuery(_this);
    btn.html('...');
    //btn.find(".dashicons").remove();
    // btn.prepend('<span class="dashicons dashicons-update loading"></span>');
    var data = {action: "cacheLoaderRefresh", data: jQuery(_this).closest('form').serialize()};
    jQuery.post(cacheJsObject.ajax_url, data, function (response) {
        //btn.find('.loading').removeClass('loading dashicons-update').addClass('dashicons-saved');
        btn.html('Refreshed');

    });
}
