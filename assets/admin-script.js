//Global Object cacheJsObject
var CacheInProcess = false;//Process Flag for Global 
var isComplete = false;

jQuery(function ($) {
    $(document).ready(function () {
        loadCacheInfo($);
    })
});

function loadCacheInfo($) {
    CacheInProcess = true;
    var data = {action: "cacheListView"};
    jQuery.post(cacheJsObject.ajax_url, data, function (response) {
        $(".ListWrap").html(response);
        CacheInProcess = false;
    });
}

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
function cleanAllCache(_this, bar) {
    let c = confirm('Are You Sure to Clean All Cache ?');
    if (!c) {
        return;
    }
    console.log('Cleaning..');
    let btn = jQuery(_this);
    if (!bar) {
        btn.find(".dashicons").remove();
        btn.prepend('<span class="dashicons dashicons-update loading"></span>');
    } else {
        btn.html('Cleaning...');
    }
    var data = {action: "cleanAllCache"};
    jQuery.post(cacheJsObject.ajax_url, data, function (response) {
        console.log(response);
        if (!bar) {
            btn.find('.loading').removeClass('loading dashicons-update').addClass('dashicons-saved');
        } else {
            btn.html('Cleane Succeed !');
            btn.closest('.ab-sub-wrapper').prev().find('.cacheCount').html("0");
            setTimeout(function () {
                btn.html('Clean All Cache');
            }, 2000);
        }
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
//Generate Part



function cacheCreateRefresh(_this) {
    CacheInProcess = true;
    console.log('Refreshing..');
    let btn = jQuery(_this);
    btn.find(".dashicons").remove();
    btn.prepend('<span class="dashicons dashicons-update loading"></span>');
    var data = {action: "cacheCreateRefresh"};
    jQuery.post(cacheJsObject.ajax_url, data, function (response) {
        btn.find('.loading').removeClass('loading dashicons-update').addClass('dashicons-saved');
        loadCacheInfo(jQuery);
        CacheInProcess = false;
    });
}


function trigCache(type, _this) {
    isComplete = false;
    console.log('Trig....');
    CacheInProcess = true;
    let btn = jQuery(_this);
    btn.find(".dashicons").remove();
    btn.html('<span class="dashicons dashicons-update loading"></span>');
    var data = {action: "trigCache", type: type};
    jQuery.post(cacheJsObject.ajax_url, data, function (response) {
        loadCacheInfo(jQuery);
        CacheInProcess = false;
    });
}

function startAllCache(_this) {
    isComplete = false;
    let btn = jQuery(_this);
    btn.find(".dashicons").remove();
    btn.html('<span class="dashicons dashicons-update loading"></span>');
    jQuery.post(cacheJsObject.ajax_url, {action: "startAllCache"}, function (response) {
        jQuery(_this).html(response);
        loadCacheInfo(jQuery);
    });
}

function replaceExistingTrig(_this) {
    //$(_this).after(loader);
    CacheInProcess = true;
    jQuery.post(cacheJsObject.ajax_url, {action: 'replaceExistingTrig', status: jQuery(_this).prop('checked')}, function (data) {
        CacheInProcess = false;
    });
}

function cleanCache(type, _this) {
    jQuery(_this).html('...');
    CacheInProcess = true;
    jQuery.post(cacheJsObject.ajax_url, {action: 'cleanCache', 'type': type}, function (data) {
        CacheInProcess = false;
        //console.log(data);
        jQuery(".ListWrap").html(data);
    });
}

function rq2Server() {
    if (!CacheInProcess && !isComplete) {
        CacheInProcess = true;
        jQuery.post(cacheJsObject.ajax_url, {action: 'rq2Server'}, function (data) {
            CacheInProcess = false;
            if (data == 'Complete') {
                isComplete = true;
            } else {
                jQuery(".ListWrap").html(data);
            }
            //loadCacheInfo(jQuery);
        });
    }
    //console.log('called');
}

setInterval(rq2Server, 1000);
