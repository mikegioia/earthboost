/**
 * AJAX Request Library
 *
 * This library sets up a wrapper around the reqwest library.
 * It handles GET and POST API calls to the backend.
 */
var Request = (function ( Config, Const ) {
    'use strict';

    var HTTP_GET = 'get';
    var HTTP_POST = 'post';

    function dashboard ( cb ) {
        send( Const.url.dashboard, HTTP_GET, cb );
    }

    function group ( name, year, cb ) {
        var o = {
            name: name,
            year: year
        };
        var url = ( year )
            ? Const.url.group_year.supplant( o )
            : Const.url.group.supplant( o );

        send( url, HTTP_GET, cb );
    }

    /**
     * Master wrapper for making API calls.
     * @param string url
     * @param string method
     * @param function cb
     * @param object data Optional
     */
    function send ( url, method, cb, data ) {
        reqwest({
            type: 'json',
            method: method,
            data: data || {},
            crossOrigin: true,
            url: Config.api_path + url,
            success: function ( r ) {
                cb( r.data );
            }
        });
    }

    return {
        group: group,
        dashboard: dashboard
    };
}( Config, Const ));