/**
 * Dashboard Component
 */
Components.Dashboard = (function ( DOM ) {
'use strict';
// Returns a new instance
return function ( $root ) {
    // Event namespace
    var namespace = '.dashboard';
    // DOM template nodes
    var $dashboard = DOM.get( '#dashboard' );
    // Templates
    var tpl = {
        dashboard: DOM.html( $dashboard )
    };

    // Group types
    const TYPE_HOME = 'home';
    const TYPE_OFFICE = 'office';

    /**
     * Load the <main> element with our list of groups.
     * @param Object data {
     *   groups: Array
     * }
     */
    function render ( data ) {
        // Add a new property member_label for our template
        data.groups.forEach( function ( group ) {
            group.member_label = ( group.group_type == TYPE_OFFICE )
                ? 'Employee'
                : 'Housemate';
        });

        $root.className = 'dashboard';
        DOM.render( tpl.dashboard, data ).to( $root );
    }

    function tearDown () {
        tpl = {};
        $dashboard = null;
        DOM.clear( $root );
        $root.className = '';
    }

    return {
        render: render,
        tearDown: tearDown
    };
}}( DOM ));