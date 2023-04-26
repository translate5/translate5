/**
 * SHARED JS: shared between translate5 and its applets - therefore written in vanilla JS since different JS frameworks used in different applets.
 */

/**
 * Make sure all stack trace frames will be shown in RootCause
 *
 * @type {number}
 */
Error.stackTraceLimit = 1000;

/**
 * Setup a counter for browser tabs having opened t5 app, and return current value of that counter
 * If diff arg is expected to be -1, +1, and if so counter will be incremented/decremented and updated value is returned
 *
 * @param diff
 * @returns {number}
 */
function tabQty(diff) {
    var key = 'translate5-tabQty',
        ls = localStorage,
        qty = parseInt(ls.getItem(key) || 0);

    // Update qty if need
    if (diff) {

        // Update value in localStorage
        ls.setItem(key, qty + diff);
    }

    // Return original or updated qty
    return parseInt(ls.getItem(key));
}

/**
 * If configured the user is logged out on window close
 */
function logoutOnWindowClose() {
    var me = this;

    // If logoutOnWindowClose-config is turned Off - do nothing
    if (!Editor.data.logoutOnWindowClose) {
        return;
    }

    // Increment t5 app tabs qty
    me.tabQty(+1);

    // Bind handler on window beforeunload-event
    onbeforeunload = () => {

        // Decrement t5 app tabs qty, and if this was the last tab - do logout
        if (me.tabQty(-1) > 0) {
            return;
        }

        // If logoutOnWindowClose-config is temporarily turned Off - do nothing
        if (!Editor.data.logoutOnWindowClose) {
            return;
        }

        // Get regexp to pick zfExtended-cookie
        var rex = /(?:^|; )zfExtended=([^;]*)(?:; |$)/,
            m = document.cookie.match(rex),
            zfExtended = m ? m[1] : false;

        // Prepare FormData object to be submitted via sendBeacon()
        var fd = new FormData();
        if (zfExtended) {
            fd.append('zfExtended', zfExtended);
        }
        fd.append('noredirect', 1);

        // Destroy the user session and prevent redirect
        navigator.sendBeacon(Editor.data.pathToRunDir + '/login/logout?beacon=true', fd);

        // Remove now invalid session cookie
        document.cookie = "zfExtended=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        //document.cookie = document.cookie.replace(rex, '; ').replace(/^; |; $/, '');
    }
}

/**
 * Return `then` if $if == true, or return $else arg otherwise
 *
 * Usages:
 * rif(123, 'Price is $1')                         will return 'Price is 123'
 * rif(0, 'Price is $1')                           will return '' (empty string)
 * rif(0, 'Price is $1', 'This is free item!')     will return 'This is free item!'
 *
 * @param $if
 * @param then You can use '$1' expr as a reference to $if arg, so if,
 *        for example $if arg is a string, it can be used as replacement for '$1' if in `then` arg
 * @param $else
 * @return {*}
 */
function rif($if, then, $else) {
    return $if ? then.replace('$1', $if) : (arguments.length > 2 ? $else : '');
}
