/**
 * Router Library
 *
 * Manages setup/teardown state transitions between pages.
 * All page.js callbacks on routes target this library and
 * for each change in controller, the layout is re-rendered
 * and events/state from the previous controller is torn down.
 */
var Router = (function ( Pages, Message ) {
    'use strict';
    // Currently loaded controller
    var current = null;
    // Controller keys
    var controllers = {
        AUTH: 'auth',
        GROUP: 'group',
        DASHBOARD: 'dashboard',
        QUESTIONS: 'questions'
    };
    // Route handlers
    var Auth = {};
    var Group = {};
    var Dashboard = {};
    var Questions = {};

    /**
     * Dashboard page.
     * @route /
     */
    Dashboard.view = function ( ctx, next ) {
        load( controllers.DASHBOARD ).view( ctx, next );
    };

    /**
     * Login page.
     * @route /login
     */
    Auth.login = function ( ctx, next ) {
        load( controllers.AUTH ).login( ctx, next );
    };

    /**
     * Check email notice page.
     * @route /check-email
     */
    Auth.checkEmail = function ( ctx, next ) {
        load( controllers.AUTH ).checkEmail( ctx, next );
    };

    /**
     * Submit authorization request.
     * @route /authorize
     */
    Auth.authorize = function ( ctx, next ) {
        load( controllers.AUTH ).authorize( ctx, next );
    };

    /**
     * Logout page.
     * @route /logout
     */
    Auth.logout = function ( ctx, next ) {
        load( controllers.AUTH ).logout( ctx, next );
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
     * Load the questions form.
     * @route /questions/{group}/{year}
     * @route /questions/{group}/{year}/{userid}
     */
    Questions.view = function ( ctx, next ) {
        load( controllers.QUESTIONS ).view( ctx, next );
    }

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

    function notFound () {
        Message.halt( 404, "Page not found" );
    }

    return {
        Auth: Auth,
        Group: Group,
        notFound: notFound,
        Dashboard: Dashboard,
        Questions: Questions
    };
}( Pages, Message ));