/**
 * Routes
 *
 * Sets up the route definitions and kicks off the page library.
 */

// Dashboard
page( '/', Router.Dashboard.view );

// Actions
page( '/login', function () {
    alert( 'TBD!' );
});
page( '/logout', function () {
    alert( 'TBD!' );
});

// Questions
page( '/questions/:group/:year/:userid/:questionid', Router.Questions.view );
page( '/questions/:group/:year/:questionid', Router.Questions.view );
page( '/questions/:group/:year', Router.Questions.view );

// Group pages
page( '/:group/:year', Router.Group.view );
page( '/:group', Router.Group.view );

// Start the router
page();