/**
 * Group Controller
 *
 * Loads the list of members, the total emissions, and all other
 * information about the group for the given year from the API,
 * and renders it to the page. This also allows admins to manage
 * the group.
 */
Pages.Group = (function ( Request, DOM, Components ) {
    'use strict';
    // Components
    var Nav;
    var Main;

    /**
     * Public API method to setup the controller. This will render
     * the page's template to the main DOM section.
     */
    function setup () {
        Nav = new Components.Nav( DOM.get( 'nav' ) );
        Main = new Components.Group( DOM.get( 'main' ) );
    }

    /**
     * Called to destroy all state and any event handlers.
     */
    function tearDown () {
        Nav && Nav.tearDown();
        Main && Main.tearDown();
    }

    /**
     * Load the group's data.
     * @route /:group[/:year]
     * @param Object ctx Contains URL params
     * @param Function next
     */
    function view ( ctx, next ) {
        Request.group(
            Request.param( ctx, 'group' ),
            Request.param( ctx, 'year' ),
            function ( data ) {
                DOM.title([ data.group.label ]);
                Nav.render({
                    user: data.user,
                    year: data.year,
                    group: data.group,
                    groups: data.groups,
                });
                Main.render({
                    user: data.user,
                    year: data.year,
                    group: data.group,
                    groups: data.groups,
                    members: data.members,
                    locales: data.locales,
                    emissions: data.emissions,
                    offset_amount: data.offset_amount
                });
            });
    }

    return {
        view: view,
        setup: setup,
        tearDown: tearDown
    };
}( Request, DOM, Components ));