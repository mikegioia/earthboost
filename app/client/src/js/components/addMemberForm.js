/**
 * New Member Form Component
 */
Components.AddMemberForm = (function ( DOM, Request ) {
'use strict';
// Returns a new instance
return function ( $root ) {
    // Event namespace
    var namespace = '.group.add-member-form';
    // DOM template nodes
    var $addForm = DOM.get( '#add-member-form' );
    // Templates
    var tpl = {
        addForm: DOM.html( $addForm )
    };
    // Internal storage
    var year;
    var onSave;
    var onCancel;
    var groupName;

    function render ( _onCancel, _onSave, _groupName, _year, member ) {
        year = _year;
        onSave = _onSave;
        onCancel = _onCancel;
        groupName = _groupName;
        member = member || {
            id: '',
            name: '',
            email: '',
            is_admin: 0,
            is_champion: 0,
            locale: 'US-NY',
            locale_months: 12
        };

        DOM.render( tpl.addForm, member ).to( $root );
        DOM.get( 'input[name="name"]' ).focus();
        DOM.get( 'a.cancel' ).onclick = onCancel;
        DOM.get( 'form' ).onsubmit = onSubmit;
    }

    function onSubmit ( e ) {
        e.preventDefault();
        Request.saveMember(
            groupName,
            year,
            DOM.serialize( this ),
            onSave );

        return false;
    }

    return {
        render: render
    };
}}( DOM, Request ));