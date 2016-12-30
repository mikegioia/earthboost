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
    var $members = DOM.get( '#members' );
    // Subcomponents
    var AddMemberForm;
    // Templates
    var tpl = {
        group: DOM.html( $group ),
        members: DOM.html( $members )
    };
    // DOM nodes used and internal state
    var data;
    var $addButton;

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
    function render ( _data ) {
        data = _data;
        updateData( data );
        DOM.render( tpl.group, data, tpl ).to( $root );
        addButtonEvent();
    }

    /**
     * Adds properties and cleans up other properties from the
     * response data.
     * @param Object data
     */
    function updateData ( data ) {
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
    }

    /**
     * Adds event handler to the new member button. Button might not
     * exist for non-admins.
     */
    function addButtonEvent () {
        $addButton = DOM.get( 'button.add-member', $root );

        if ( $addButton ) {
            $addButton.onclick = renderAddMemberForm;
        }
    }

    /**
     * Renders the add a new user form to the DOM.
     */
    function renderAddMemberForm () {
        var $panel = DOM.get( '.panel' );

        AddMemberForm = new Components.AddMemberForm( $panel );
        AddMemberForm.render(
            function () {
                DOM.render( tpl.members, data ).to( $panel );
                addButtonEvent();
            },
            function ( data ) {
                render( data );
                // Reload the form
                $addButton && $addButton.click();
            },
            data.group.name,
            data.year );
    }

    function tearDown () {
        tpl = {};
        data = null;
        $group = null;
        $addButton = null;
        DOM.clear( $root );
    }

    return {
        render: render,
        tearDown: tearDown
    };
}}( DOM ));