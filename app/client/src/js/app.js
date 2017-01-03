/**
 * --------------------------------------------------------------------
 *                    EarthBoost Client Application
 * --------------------------------------------------------------------
 *
 * @author   Mike Gioia <mikegioia@gmail.com>
 * @license  GPLv3
 *
 * This is how the app works:
 *    1. The Gruntfile combines all JavaScript files into one, minifies
 *       it, and then wraps it in an anonymous function so that nothing
 *       is exposed globally.
 *    2. The index.html file contains all of the template logic and the
 *       routes, and loads the corresponding path to the Router method.
 *
 * This app exposes the following within the application:
 *    URL           Page nagivation
 *    Const         Constants used in application
 *    Pages         Container for all pages/controllers
 *    Components    Container for all web components
 *    Router        Interface between page.js and the controllers
 *    Message       Library for notifications and messages
 *    Calculator    Library for the Carbon Calculator
 *
 * The vendor dependencies used are:
 *    Modernizr
 *    Mustache
 */
var Pages = {};
var Components = {};
var Const = {
    expire_ms: 5000,
    title_stem: 'EarthBoost',
    countries: {
        US: "United States",
        UK: "United Kingdom"
    },
    url: {
        group: '/{name}',
        logout: '/logout',
        dashboard: '/dashboard',
        group_year: '/{name}/{year}',
        questions: '/questions/{name}/{year}',
        save_member: '/savemember/{name}/{year}',
        save_answer: '/saveanswer/{name}/{year}',
        remove_member: '/removemember/{name}/{year}',
        finished_questions: '/questions/finished/{name}',
        questions_user: '/questions/{name}/{year}/{userid}',
        save_answer_user: '/saveanswer/{name}/{year}/{userid}'
    }
};

/**
 * Go to a particular route.
 */
var URL = {
    // Default method for navigating to a URL
    goto: function () {
        var args = Array.prototype.slice.call( arguments );
        var filterFn = function ( a ) {
            return a != undefined && a != null;
        };

        page( '/' + args.filter( filterFn ).join( '/' ) );
    },
    // Helpers
    group: function ( name, year ) {
        this.goto( name, year );
    },
    login: function () {
        this.goto( 'login' );
    },
    logout: function () {
        this.goto( 'logout' );
    },
    dashbord: function () {
        this.goto( 'dashboard' );
    }
};

/**
 * Extend the string library with an interpolation function.
 * Thank you yet again Douglas Crockford.
 * Usage: alert( "Mi chiamo {nome}".supplant({ nome: "Michele" }));
 * @param object o
 */
String.prototype.supplant = function ( o ) {
    return this.replace(
        /{([^{}]*)}/g,
        function ( a, b ) {
            var r = o[ b ];
            return typeof r === 'string' || typeof r === 'number' ? r : a;
        });
};

/**
 * Capitalize all of the words in a string.
 */
String.prototype.capitalize = function () {
    return this.replace(
        /(?:^|\s)\S/g,
        function ( a ) {
            return a.toUpperCase();
        });
};

/**
 * Replace a number string with commas.
 */
String.prototype.numberCommas = function () {
    return this.replace( /\B(?=(\d{3})+(?!\d))/g, ',' );
}
