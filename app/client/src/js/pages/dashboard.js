/**
 * Dashboard Controller
 *
 * Loads the list of groups from the API and renders them as
 * cards to the page.
 */
Pages.Dashboard = (function ( Request, DOM, Components ) {
    'use strict';
    // Components
    var Nav;
    var Main;

    /**
     * Public API method to setup the controller. This will render
     * the page's template to the main DOM section.
     */
    function setup () {
        DOM.title([ 'Dashboard' ]);
        // Instantiate components
        Nav = new Components.Nav( DOM.get( 'nav' ) );
        Main = new Components.Dashboard( DOM.get( 'main' ) );
    }

    /**
     * Called to destroy some state and any event handlers.
     */
    function tearDown () {
        Main && Main.tearDown();
    }

    /**
     * Load the list of groups.
     * @route /
     * @param Object ctx Contains URL params
     * @param Function next
     */
    function view ( ctx, next ) {
        Request.dashboard( function ( data ) {
            Nav.render({
                year: null,
                group: null,
                user: data.user,
                groups: data.groups
            });
            Main.render({
                groups: data.groups
            });
        });
    }

    return {
        view: view,
        setup: setup,
        tearDown: tearDown
    };
}( Request, DOM, Components ));