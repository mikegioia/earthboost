/**
 * Router Library
 *
 * Manages setup/teardown state transitions between pages.
 * All page.js callbacks on routes target this library and
 * for each change in controller, the layout is re-rendered
 * and events/state from the previous controller is torn down.
 */
var Router = (function ( Pages ) {
    'use strict';
    // Currently loaded controller
    var current = null;
    // Controller keys
    var controllers = {
        GROUP: 'group',
        DASHBOARD: 'dashboard'
    };
    // Route handlers
    var Group = {};
    var Dashboard = {};

    /**
     * Dashboard page.
     * @route /
     */
    Dashboard.view = function ( ctx, next ) {
        load( controllers.DASHBOARD ).view( ctx, next );
    };

    /**
     * Load the full group page.
     * @route /{group}
     * @route /{group}/{year}
     */
    Group.view = function ( ctx, next ) {
        load( controllers.GROUP ).view( ctx, next );
    };

    /**
     * Loads a controller at the specified key. This will call
     * setup() and optionally tearDown() on the controller.
     * @param string controller
     * @throws Error
     * @return Controller
     */
    function load ( controller ) {
        var Controller;
        var CurrentController = getController( current );

        if ( CurrentController ) {
            if ( current === controller ) {
                return CurrentController;
            }

            CurrentController.tearDown();
        }

        current = controller;
        Controller = getController( controller );

        if ( ! Controller ) {
            throw new Error( "Failed to find controller '" + controller + "'" );
        }

        Controller.setup();

        return Controller;
    }

    /**
     * Takes in a name like 'group.view' and returns the
     * controller located at Pages.Group.View.
     * @param string controller
     * @return string
     */
    function getController ( controller ) {
        var pieces;
        var obj = Pages;

        if ( ! controller ) {
            return;
        }

        return controller.split( '.' )
            .map( function ( a ) {
                return a.capitalize();
            })
            .reduce(
                function ( a, b ) {
                    return a[ b ];
                },
                Pages );
    }

    return {
        Group: Group,
        Dashboard: Dashboard
    };
}( Pages ));