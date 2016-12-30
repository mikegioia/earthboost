/**
 * AJAX Request Library
 *
 * This library sets up a wrapper around the reqwest library.
 * It handles GET and POST API calls to the backend.
 */
var Request = (function ( Config, Const, Message ) {
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

    function saveMember ( groupName, year, data, cb ) {
        var url = Const.url.save_member.supplant({
            year: year,
            name: groupName
        });

        send( url, HTTP_POST, cb, data );
    }

    /**
     * Loads a parameter from the context, with a default
     * if it's not found.
     * @param Object ctx
     * @param String key
     * @param Mixed def Default value (null)
     */
    function param ( ctx, key, def ) {
        return ( ctx.params && ctx.params[ key ] )
            ? ctx.params[ key ]
            : def || null;
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
                if ( handle( r ) ) {
                    typeof cb === 'function' && cb( r.data );
                }
            },
            error: function ( e ) {
                console.error( e );
                Message.error( "There was a problem with that request." );
            }
        });
    }

    /**
     * Handles a response based on the status code.
     * @param Object r
     * @return Bool
     */
    function handle( r ) {
        // Render any notifications
        if ( r.messages && r.messages.length ) {
            r.messages.forEach( function ( m ) {
                Message[ m.type ]( m.message );
            });
        }

        switch ( r.code ) {
            case 401:
                alert( 'GOTO LOGIN' );
                return false;
            case 400:
            case 403:
            case 404:
            case 500:
                Message.halt( r.code, r.message );
                return false;
            case 200:
            default:
                return true;
        }
    }

    return {
        param: param,
        group: group,
        dashboard: dashboard,
        saveMember: saveMember
    };
}( Config, Const, Message ));