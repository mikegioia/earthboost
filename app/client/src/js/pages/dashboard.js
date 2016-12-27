/**
 * Dashboard Controller
 *
 * Loads the list of groups from the API and renders them as
 * cards to the page.
 */
Pages.Dashboard = (function ( Request ) {
    'use strict';

    /**
     * Public API method to setup the controller. This will render
     * the page's template to the main DOM section.
     */
    function setup () {
        // render layout
        // instantiate components
    }

    /**
     * Called to destroy all state and any event handlers.
     */
    function tearDown () {
        // destroy components
    }

    /**
     * Load the list of groups.
     * @route /
     * @param object ctx Contains URL params
     * @param function next
     */
    function view ( ctx, next ) {
        Request.dashboard( function ( data ) {
            console.log( data );
        });
    }

    return {
        view: view,
        setup: setup,
        tearDown: tearDown
    };
}( Request ));