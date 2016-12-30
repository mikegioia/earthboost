/**
 * Notifications Component
 */
Components.Notifications = (function ( DOM, Const ) {
'use strict';
// Returns a new instance
return function ( $root ) {
    // Event namespace
    var namespace = '.notifications';
    // DOM template nodes
    var $error = DOM.get( '#error' );
    var $notification = DOM.get( '#notification' );
    // Templates
    var tpl = {
        error: DOM.html( $error ),
        notification: DOM.html( $notification )
    };

    /**
     * Add a new notification to the DOM.
     * @param Object data {
     *   type: String
     *   message: String
     * }
     * @param Bool expire
     */
    function insert ( data, expire ) {
        var timeout;
        var newNode = DOM.create( 'div', {
            classes: 'notification',
            html: DOM.render( tpl.notification, data ).html()
        });

        DOM.append( newNode, $root );
        DOM.get( '.close', newNode ).onclick = function ( e ) {
            DOM.remove( newNode, $root );
            timeout && clearTimeout( timeout );
        };

        if ( expire === true ) {
            timeout = setTimeout( function () {
                DOM.remove( newNode, $root );
            }, Const.expire_ms );
        }
    }

    function closeAll () {
        var i;
        var notifications = DOM.find( '.notification' );

        for ( i = 0; i < notifications.length; i++ ) {
            DOM.remove( notifications[ i ], $root );
        }
    }

    /**
     * Renders a full page error overlay.
     * @param Integer code
     * @param String message
     */
    function halt ( code, message ) {
        DOM.render( tpl.error, {
            code: code,
            message: message
        }).to( DOM.get( 'main' ) );
    }

    return {
        halt: halt,
        insert: insert,
        closeAll: closeAll
    };
}}( DOM, Const ));