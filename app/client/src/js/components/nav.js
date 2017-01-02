/**
 * Top Navigation Component
 */
Components.Nav = (function ( DOM, URL ) {
'use strict';
// Returns a new instance
return function ( $root ) {
    // Event namespace
    var namespace = '.nav';
    // DOM template nodes
    var $nav = DOM.get( '#nav' );
    // Templates
    var tpl = {
        nav: DOM.html( $nav )
    };
    // Internal state
    var year;
    var group;
    var $select;

    /**
     * Load the <nav> element with our buttons and account <select>.
     * @param Object data {
     *   user: Object
     *   year: Integer
     *   group: Object
     *   groups: Array
     * }
     */
    function render ( data ) {
        var groupName = ( data.group )
            ? data.group.name
            : null;

        // Add a property for the selected one
        data.groups.forEach( function ( group ) {
            group.selected = ( group.group_name == groupName );
        });

        data.showLogout = true;
        data.showGroupSelect = true;
        DOM.render( tpl.nav, data ).to( $root );
        $select = DOM.get( 'select', $root );
        $select.onchange = switchGroup;
    }

    /**
     * Load the <nav> element with a cancel button.
     * @param String groupName
     * @param String year
     */
    function renderCalculator ( groupName, year ) {
        var data = {
            year: year,
            showCancel: true,
            groupName: groupName
        };

        DOM.render( tpl.nav, data ).to( $root );
    }

    function tearDown () {
        tpl = {};
        $nav = null;
        year = null;
        group = null;
        $select = null;
        DOM.clear( $root );
    }

    /**
     * Event handler for select change.
     * @param Event e
     */
    function switchGroup ( e ) {
        URL.group( this.value, year );
    }

    return {
        render: render,
        tearDown: tearDown,
        renderCalculator: renderCalculator
    };
}}( DOM, URL ));