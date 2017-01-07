/**
 * Auth Controller
 *
 * Handles pages like logout and login.
 */
Pages.Auth = (function ( Request, DOM, Components ) {
    'use strict';
    // Components
    var Nav;
    var Main;

    /**
     * Public API method to setup the controller. This will render
     * the page's template to the main DOM section.
     */
    function setup () {
        // Instantiate components
        Nav = new Components.Nav( DOM.get( 'nav' ) );
        Main = new Components.Login( DOM.get( 'main' ) );
    }

    /**
     * Called to destroy some state and any event handlers.
     */
    function tearDown () {
        Main && Main.tearDown();
    }

    /**
     * Load the list of groups.
     * @route /login
     * @param Object ctx Contains URL params
     * @param Function next
     */
    function login ( ctx, next ) {
        DOM.title([ 'Login' ]);
        Nav.clear();
        Main.render();
    }

    /**
     * Shows the user a message to check their email.
     * @route /check-email
     * @param Object ctx Contains URL params
     * @param Function next
     */
    function checkEmail ( ctx, next ) {
        DOM.title([ 'Check Your Email' ]);
        Nav.clear();
        Main.renderNotice();
    }

    /**
     * Reads in a token and submits an auth request.
     * @route /authorize
     * @param Object ctx Contains URL params
     * @param Function next
     */
    function authorize ( ctx, next ) {
        Request.authorize(
            Request.getParam( 'token' ),
            function ( data ) {
                page( Const.url.dashboard );
            });
    }

    /**
     * Submit request to logout.
     * @route /logout
     * @param Object ctx Contains URL params
     * @param Function next
     */
    function logout ( ctx, next ) {
        Request.logout( function ( data ) {
            page( Const.url.login );
        });
    }

    return {
        login: login,
        setup: setup,
        logout: logout,
        tearDown: tearDown,
        authorize: authorize,
        checkEmail: checkEmail
    };
}( Request, DOM, Components ));