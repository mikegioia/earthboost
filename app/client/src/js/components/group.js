/**
 * Group Component
 */
Components.Group = (function ( DOM, Const ) {
'use strict';
// Returns a new instance
return function ( $root ) {
    // Event namespace
    var namespace = '.group';
    // DOM template nodes
    var $task = DOM.get( '#task' );
    var $group = DOM.get( '#group' );
    var $members = DOM.get( '#members' );
    // Subcomponents
    var AddMemberForm;
    // Templates
    var tpl = {
        task: DOM.html( $task ),
        group: DOM.html( $group ),
        members: DOM.html( $members )
    };
    var GROUP_TYPES = {
        home: 'Home',
        office: 'Company'
    };
    // DOM nodes used and internal state
    var data;
    var $addButtons;

    /**
     * Load the <main> element with our list of groups.
     * @param Object data {
     *   year: Integer
     *   user: Object
     *   task: Object
     *   group: Object
     *   groups: Array
     *   members: Array
     *   is_admin: Bool
     *   locales: Object
     *   emissions: Float
     *   offset_amount: Float
     * }
     */
    function render ( _data ) {
        data = _data;
        updateData( data );
        updateTask( data );
        $root.className = 'group';
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
        // Add to user object
        data.user.is_admin = data.is_admin;
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
            m.is_standard = ( m.is_standard == 1 );
            m.edit_url = Const.url.questions_user.supplant({
                year: data.year,
                userid: m.user_id,
                name: data.group.name
            }) + "/intro";
        });

        // Sort by name for now
        data.members.sort( function ( a, b ) {
            return (a.name > b.name)
                ? 1
                : ((b.name > a.name) ? -1 : 0);
            });

        // Pick out the user's record
        data.member = findMember( data.user.id, 'user_id' );
        data.member.profile = data.user.profile;
    }

    /**
     * Adds more data to the task, like a label and URL.
     * @param Object data
     */
    function updateTask ( data ) {
        var t = data.task;
        var groupType = GROUP_TYPES[ data.group.type ];

        if ( ! data.task ) {
            return;
        }

        switch ( t.key ) {
            case 'add_member':
                t.color = 'blue';
                t.url = 'javascript:;';
                t.classes = 'add-member';
                t.label = "Add a Staff Member";
                break;
            case 'group_profile':
                t.color = 'purple';
                t.url = Const.url.questions.supplant({
                    year: data.year,
                    name: data.group.name
                });
                t.label = "Complete " + groupType + " Profile";
                break;
            case 'user_profile':
                t.color = 'blue';
                t.label = "Complete Your Profile";
                t.url = Const.url.questions_user.supplant({
                    year: data.year,
                    userid: data.user.id,
                    name: data.group.name
                });
                break;
            case 'group_notify':
                t.url = '#';
                t.color = 'purple';
                t.label = "Notify Your " + groupType;
                break;
            case 'canopy_club':
                t.url = '#';
                t.color = 'green';
                t.label = "Join the Canopy Club!";
                break;
        }
    }

    /**
     * Adds event handler to the new member button. Button might not
     * exist for non-admins.
     */
    function addButtonEvent () {
        $addButtons = DOM.find( '.add-member', $root );

        [].forEach.call(
            $addButtons,
            function ( $addButton ) {
                $addButton.onclick = renderAddMemberForm;
            });
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
        var remove = confirm(
            "Are you sure you want to remove this group member?" +
            "Their data will not be lost, but you will need to " +
            "re-add them by email to restore it." );

        if ( ! remove ) {
            return;
        }

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
        $addButtons = null;
        $root.className = '';
    }

    return {
        render: render,
        tearDown: tearDown
    };
}}( DOM, Const ));