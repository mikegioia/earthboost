/**
 * New Member Form Component
 */
Components.AddMemberForm = (function ( DOM, Request, Const ) {
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
    var locales;
    var onCancel;
    var groupName;

    function render ( _onCancel, _onSave, _groupName, _year, _locales, member ) {
        year = _year;
        onSave = _onSave;
        locales = _locales;
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

        member.locales = processLocales( locales, member.locale );
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

    /**
     * Creates a list of locales for a select menu.
     * @param Object locales
     * @param String selectedCode
     * @return Object
     */
    function processLocales ( locales, selectedCode ) {
        var country;
        var flat = [];
        var regionCode;
        var localeCode;
        var countryCode;

        for ( countryCode in locales ) {
            country = {
                regions: [],
                country: Const.countries[ countryCode ]
            };

            if ( ! Const.countries[ countryCode ] ) {
                return;
            }

            for ( regionCode in locales[ countryCode ] ) {
                localeCode = countryCode + '-' + regionCode;
                country.regions.push({
                    code: localeCode,
                    selected: localeCode == selectedCode,
                    region: locales[ countryCode ][ regionCode ][ 'name' ]
                });
            };

            flat.push( country );
        }

        return flat;
    }

    function tearDown () {
        year = null;
        onSave = null;
        locales = null;
        onCancel = null;
        $addForm = null;
        groupName = null;
    }

    return {
        render: render,
        tearDown: tearDown
    };
}}( DOM, Request, Const ));