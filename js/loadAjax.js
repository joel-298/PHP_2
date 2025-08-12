function loadContent(_href, callback) {
    jQuery.ajax({
        type: 'post',
        url: _href,
        success: function (data) {
            var data1 = jQuery(data).filter(".mpage_container").html();
            if (typeof (data1) == "undefined") { data1 = jQuery(".mpage_container > *", data); }
            jQuery(".mpage_container").html(data1);
            unsaved = false;
            if (callback && typeof callback == "function") {
                callback(data);
            }
        }
    });
}

jQuery(document).ready(function($){
    if (Modernizr.history) {
        history.replaceState({ myTag: true }, null, window.location.href);
    }
    jQuery(document).on("click", "a.load_ajax", function (evt) {
        if (evt.which == 1) {
            if (!evt.ctrlKey && Modernizr.history) {
                var _href = jQuery(this).attr("href");
                loadContent(_href, function (data) {
                    history.pushState({ myTag: true }, null, _href);
                });
                return false;
            }
            else {
                return true;
            }
        }
        else {
            return false;
        }
    });
    jQuery(document).on("change", "select.load_ajax", function (evt) {
        var _href = jQuery(this).val();
        if (Modernizr.history) {
            loadContent(_href, function (data) {
                history.pushState({ myTag: true }, null, _href);
            });
            return false;
        }
        else {
            window.location.href = _href;
        }
    });
    
    jQuery(window).bind("popstate", function (e) {
        if (e.originalEvent.state && e.originalEvent.state.myTag) { // to avoid Safari popstate on page load
            var _href = location.href;
            loadContent(_href);
        }
    });
});
