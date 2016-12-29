/**
 * Routes
 *
 * Sets up the route definitions and kicks off the page library.
 */

// Dashboard
page( '/', Router.Dashboard.view );

// Actions
page( '/logout', function () {
    alert( 'TBD!' );
});

// Group pages
page( '/:group/:year', Router.Group.view );
page( '/:group', Router.Group.view );

// Start the router
page();