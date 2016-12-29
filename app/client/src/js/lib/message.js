/**
 * Message and Notification Library
 *
 * This library can render messages to the DOM and display
 * full page error messages.
 */
var Message = (function ( Components ) {
    'use strict';
    // DOM container
    var $wrapper = DOM.get( '#notifications' );
    // Component
    var Notifications;
    // Status constants
    var INFO = 'info';
    var ERROR = 'error';
    var SUCCESS = 'success';
    var WARNING = 'warning';

    function info ( message ) {
        notify( INFO, message );
    }

    function error ( message ) {
        notify( ERROR, message );
    }

    function success ( message ) {
        notify( SUCCESS, message );
    }

    function warning ( message ) {
        notify( WARNING, message );
    }

    /**
     * Renders a new notification message.
     * @param String type
     * @param String message
     */
    function notify ( type, message ) {
        if ( ! Notifications ) {
             Notifications = new Components.Notifications( $wrapper );
        }

        Notifications.insert({
            type: type,
            message: message
        });
    }

    /**
     * Displays a full page error screen.
     * @param Integer code
     * @param String message
     */
    function halt ( code, message ) {
        if ( ! Notifications ) {
             Notifications = new Components.Notifications( $wrapper );
        }

        Notifications.halt( code, message );
    }

    return {
        halt: halt,
        info: info,
        error: error,
        success: success,
        warning: warning
    };
}( Components ));