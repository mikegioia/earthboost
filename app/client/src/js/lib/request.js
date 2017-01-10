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
    var STATUS_INFO = 'info';
    var STATUS_ERROR = 'error';
    var STATUS_SUCCESS = 'success';

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

    function questions ( name, year, userId, cb ) {
        var o = {
            name: name,
            year: year,
            userid: userId
        };
        var url = ( userId )
            ? Const.url.questions_user.supplant( o )
            : Const.url.questions.supplant( o );

        send( url, HTTP_GET, cb );
    }

    function saveMember ( groupName, year, data, cb ) {
        var url = Const.url.save_member.supplant({
            year: year,
            name: groupName
        });

        send( url, HTTP_POST, cb, data );
    }

    function removeMember ( groupName, year, id, cb ) {
        var url = Const.url.remove_member.supplant({
            year: year,
            name: groupName
        });

        send( url, HTTP_POST, cb, {
            id: id
        });
    }

    function saveAnswer ( groupName, year, userId, data, cb, errCb ) {
        var o = {
            year: year,
            userid: userId,
            name: groupName
        };
        var url = ( userId )
            ? Const.url.save_answer_user.supplant( o )
            : Const.url.save_answer.supplant( o );

        send( url, HTTP_POST, cb, data, errCb );
    }

    function login ( data, cb ) {
        send( Const.url.login, HTTP_POST, cb, data );
    }

    function session ( cb ) {
        send( Const.url.session, HTTP_GET, cb );
    }

    function authorize ( token, cb ) {
        send( Const.url.authorize, HTTP_POST, cb, {
            token: token
        });
    }

    function logout ( cb ) {
        send( Const.url.logout, HTTP_GET, cb );
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
     * Loads a parameter from the context, with a default
     * if it's not found.
     * @param String key
     * @param Mixed def Default value (null)
     */
    function getParam ( key, def ) {
        var tmp = [];
        var result = null;

        location.search.substr( 1 )
            .split( "&" )
            .forEach( function ( item ) {
                tmp = item.split( key + "=", 2 );

                if ( tmp.length >= 2 ){
                    result = decodeURIComponent( tmp[ 1 ] );
                }
            });

        return result;
    }

    /**
     * Master wrapper for making API calls.
     * @param string url
     * @param string method
     * @param function cb
     * @param object data Optional
     * @param function errCb Optional
     */
    function send ( url, method, cb, data, errCb ) {
        reqwest({
            type: 'json',
            method: method,
            data: data || {},
            crossOrigin: true,
            withCredentials: true,
            url: Config.api_path + url,
            success: function ( r ) {
                if ( handle( r ) ) {
                    typeof cb === 'function' && cb( r.data, r.status );
                }
            },
            error: function ( e ) {
                console.error( e );
                typeof errCb === 'function' && errCb();
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
        switch ( r.code ) {
            case 302:
                page( r.data.url );
                displayMessages( r.messages );
                return false;
            case 401:
                page( Const.url.login );
                displayMessages( r.messages );
                return false;
            case 400:
            case 403:
            case 404:
            case 412:
            case 500:
                Message.halt( r.code, r.message );
                return false;
            case 200:
            default:
                displayMessages( r.messages );
                return r.status === STATUS_SUCCESS;
        }
    }

    function displayMessages ( messages ) {
        // Render any notifications
        if ( messages && messages.length ) {
            messages.forEach( function ( m ) {
                if ( m.message && m.message.length ) {
                    Message[ m.type ]( m.message );
                }
            });
        }
    }

    return {
        param: param,
        group: group,
        login: login,
        logout: logout,
        session: session,
        getParam: getParam,
        authorize: authorize,
        dashboard: dashboard,
        questions: questions,
        saveMember: saveMember,
        saveAnswer: saveAnswer,
        STATUS_INFO: STATUS_INFO,
        STATUS_ERROR: STATUS_ERROR,
        removeMember: removeMember,
        STATUS_SUCCESS: STATUS_SUCCESS
    };
}( Config, Const, Message ));