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
 *    Const         Constants used in application
 *    Pages         Container for all pages/controllers
 *    Components    Container for all web components
 *    Router        Interface between page.js and the controllers
 *
 * The vendor dependencies used are:
 *    Modernizr
 *    Mustache
 */
var Pages = {};
var Components = {};
var Const = {
    url: {
        group: '/{name}',
        dashboard: '/dashboard',
        group_year: '/{name}/{year}'
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
