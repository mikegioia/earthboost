/**
 * Login Component
 */
Components.Login = (function ( DOM ) {
'use strict';
// Returns a new instance
return function ( $root ) {
    // Event namespace
    var namespace = '.login';
    // DOM template nodes
    var $login = DOM.get( '#login' );
    var $loginNotice = DOM.get( '#login-notice' );
    // Templates
    var tpl = {
        login: DOM.html( $login ),
        login_notice: DOM.html( $loginNotice )
    };

    /**
     * Load the <main> element with the login form.
     */
    function render () {
        $root.className = 'login';
        DOM.render( tpl.login, {} ).to( $root );
        DOM.get( 'form' ).onsubmit = onSubmit;
    }

    function renderNotice () {
        $root.className = 'login';
        DOM.render( tpl.login_notice, {} ).to( $root );
    }

    /**
     * When the login form is submitted.
     */
    function onSubmit ( e ) {
        e.preventDefault();
        Request.login(
            DOM.serialize( this ),
            function ( data ) {
                console.log( data );
            });

        return false;
    }

    function tearDown () {
        tpl = {};
        $login = null;
        $root.className = '';
    }

    return {
        render: render,
        tearDown: tearDown,
        renderNotice: renderNotice
    };
}}( DOM ));