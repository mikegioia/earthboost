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
 *
 * The vendor dependencies used are:
 *    Modernizr
 *    Mustache
 */
var Const = {};
var Pages = {};
var Components = {};