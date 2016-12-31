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
     *   locales: Object
     *   emissions: Float
     *   offset_amount: Float
     * }
     */
    function render ( _data ) {
        data = _data;
        updateData( data );
        DOM.render( tpl.group, data, tpl ).to( $root );
        addButtonEvent();
        memberActionEvents();
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
        data.member = findMember( data.user.id, 'user_id' );
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
     * Sets up an event listener for edit/remove members.
     */
    function memberActionEvents () {
        var $panel = DOM.get( '.panel' );

        $panel.addEventListener( 'click', function ( e ) {
            var $target = e.target;

            if ( $target.className === 'edit-member' ) {
                renderAddMemberForm( null, findMember( $target.dataset.id ) );
            }
            else if ( $target.className === 'remove-member' ) {
                removeMember( $target.dataset.id );
            }
        }, false );
    }

    /**
     * Renders the add a new user form to the DOM.
     * @param Event e
     * @param Object member
     */
    function renderAddMemberForm ( e, member ) {
        var $panel = DOM.get( '.panel' );

        AddMemberForm = new Components.AddMemberForm( $panel );
        AddMemberForm.render(
            // onCancel
            function () {
                DOM.render( tpl.members, data ).to( $panel );
                addButtonEvent();
            },
            // onSubmit
            function ( responseData ) {
                var m = responseData.member;
                // Re-render the data and reload the form
                render( responseData );
                renderAddMemberForm( null, member ? m : undefined );
            },
            data.group.name,
            data.year,
            data.locales,
            member );
    }

    /**
     * Removes a member from the group.
     * @param Integer id
     */
    function removeMember ( id ) {
        // @TODO PROMPT
        Request.removeMember(
            data.group.name,
            data.year,
            id,
            function ( responseData ) {
                render( responseData );
            });
    }

    function findMember ( id, field ) {
        field = field || 'id';

        return data.members.filter( function ( member ) {
            return member[ field ] == id;
        })[ 0 ];
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