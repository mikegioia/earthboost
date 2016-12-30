/**
 * DOM Manipulation Library
 *
 * This library interacts with the DOM and uses Mustache to
 * to parse and process templates, and write them to the DOM.
 */
var DOM = (function ( Const, Mustache ) {
    'use strict';

    /**
     * Returns an single DOM element.
     * @param String selector
     * @param Node ctx Defaults to document
     * @return Node
     */
    function get ( selector, ctx ) {
        return (ctx || document).querySelector( selector );
    }

    /**
     * Returns a collection of elements by selector.
     * @param String selector
     * @return NodeList
     */
    function find ( selector, ctx ) {
        return (ctx || document).querySelectorAll( selector );
    }

    /**
     * Reads the inner HTML of the DOM node.
     * @param Node ctx
     * @return String
     */
    function html ( ctx ) {
        return ctx.innerHTML;
    }

    /**
     * Clear the inner HTML of the DOM node.
     * @param Node ctx
     */
    function clear ( ctx ) {
        ctx.innerHTML = "";
    }

    /**
     * Create a new DOM node.
     * @param String nodeType
     * @param Oject properties {
     *   classes: String
     * }
     */
    function create ( nodeType, properties ) {
        var node = document.createElement( nodeType );

        if ( properties.classes ) {
            node.className = properties.classes;
        }

        if ( properties.html ) {
            node.innerHTML = properties.html;
        }

        return node;
    }

    /**
     * Append a node to a context node.
     * @param Node node
     * @param Node ctx
     */
    function append ( node, ctx ) {
        (ctx || document).appendChild( node );
    }

    /**
     * Remove a node.
     * @param Node node
     * @param Node ctx
     */
    function remove ( node, ctx ) {
        (ctx || document).removeChild( node );
    }

    /**
     * Serializes a form into an object.
     * @param Node form
     * @return Object
     */
    function serialize ( form ) {
        var data = {};
        var formData = new FormData( form );

        for ( var pair of formData.entries() ) {
            data[ pair[ 0 ] ] = pair[ 1 ];
        }

        return data;
    }

    /**
     * Renders a Mustache template to a DOM node. This can return
     * an object with two function-properties. One is to get the
     * HTML string, and the other is to render to an element.
     * @param String template
     * @param Object data
     * @param Object subTpl Sub-templates to pass to Mustache
     * @return Object or null
     */
    function render ( tpl, data, subTpl ) {
        var html = Mustache.render( tpl, data, subTpl || {} );

        return {
            html: function () {
                return html;
            },
            to: function ( ctx ) {
                ctx.innerHTML = html;
            }
        };
    }

    /**
     * Change the page title.
     * @param Array title
     */
    function title ( title ) {
        title.push( Const.title_stem );
        document.title = title.join( " • " );
    }

    return {
        get: get,
        find: find,
        html: html,
        clear: clear,
        title: title,
        append: append,
        create: create,
        remove: remove,
        render: render,
        serialize: serialize
    };
}( Const, Mustache ));
