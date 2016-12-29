/**
 * Group Component
 */
Components.Group = (function ( DOM ) {
'use strict';
// Returns a new instance
return function ( $root ) {
    // Event namespace
    var namespace = '.group';
    // DOM template nodes
    var $group = DOM.get( '#group' );
    // Templates
    var tpl = {
        group: DOM.html( $group )
    };

    /**
     * Load the <main> element with our list of groups.
     * @param Object data {
     *   year: Integer
     *   user: Object
     *   group: Object
     *   groups: Array
     *   members: Array
     *   emissions: Float
     *   offset_amount: Float
     * }
     */
    function render ( data ) {
        // Format certain numbers
        data.emissions = data.emissions.toFixed( 1 );
        data.offset_amount = data.offset_amount
            .toFixed( 2 )
            .toString()
            .numberCommas();
        data.members.forEach( function ( m ) {
            m.emissions = m.emissions.toFixed( 1 );
            m.offset_amount = m.offset_amount
                .toFixed( 2 )
                .toString()
                .numberCommas();
        });

        // Sort by name for now
        data.members.sort( function ( a, b ) {
            return (a.name > b.name)
                ? 1
                : ((b.name > a.name) ? -1 : 0);
            }); 

        // Pick out the user's record
        data.member = data.members.filter( function ( member ) {
            return member.user_id == data.user.id;
        })[ 0 ];

        DOM.render( tpl.group, data ).to( $root );
    }

    function tearDown () {
        tpl = {};
        $group = null;
        DOM.clear( $root );
    }

    return {
        render: render,
        tearDown: tearDown
    };
}}( DOM ));