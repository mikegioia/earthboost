<?php

namespace App\Entities;

use App\Model
  , App\Entity
  , App\Entities\Group
  , App\Libraries\Emissions
  , App\Models\Member as MemberModel
  , App\Models\Emissions as EmissionsModel;

class Member extends Entity
{
    public $id;
    public $year;
    public $name;
    public $email;
    public $locale;
    public $user_id;
    public $group_id;
    public $is_admin;
    public $emissions;
    public $group_name;
    public $group_type;
    public $group_label;
    public $is_champion;
    public $is_standard;
    public $offset_amount;
    public $locale_months;
    public $locale_percent;
    // Loaded from the database
    public $emissions_data = [];

    // Cached
    private $_rawEmissions;

    protected $_modelClass = 'Member';

    const POPULATE_FULL = 'populate_full';
    const POPULATE_EMISSIONS = 'populate_emissions';

    public function __construct( $id = NULL, $options = [] )
    {
        parent::__construct( $id, $options );

        if ( is_numeric( $id )
            && get( $options, self::POPULATE_FULL, FALSE ) === TRUE )
        {
            $this->buildProfile();
        }

        $this->locale_months = ( $this->locale_percent )
            ? round( $this->locale_percent * 12 / 100 )
            : 12;

        if ( $this->exists()
            && is_null( $this->emissions )
            && get( $options, self::POPULATE_EMISSIONS, TRUE ) === TRUE )
        {
            $this->emissions = $this->getEmissions();
            $this->emissions_data = $this->getEmissionsData();
            $this->offset_amount = (new Emissions)->price( $this->emissions );
        }
    }

    /**
     * Returns the emissions if it's hard-set on the member record.
     * Otherwise, computes it from the database.
     */
    public function getEmissions()
    {
        if ( $this->emissions ) {
            return $this->emissions;
        }

        // If this is a "standard" calculation then use the
        // locale value.
        if ( $this->is_standard == 1 ) {
            $locale = $this->getLocale( $this->locale );

            return ( $locale->mt * $this->locale_percent / 100 );
        }

        $raw = $this->getRawEmissions();

        return (new Emissions( $raw ))->calculate();
    }

    /**
     * Load the raw emissions records.
     * @return array of objects
     */
    public function getRawEmissions()
    {
        if ( $this->_rawEmissions ) {
            return $this->_rawEmissions;
        }

        if ( ! $this->exists() || $this->is_standard == 1 ) {
            return [];
        }

        $this->_rawEmissions = (new EmissionsModel)->fetchAll([
            'year' => $this->year,
            'user_id' => $this->user_id,
            'group_id' => $this->group_id
        ]);

        return $this->_rawEmissions;
    }

    public function getEmissionsData()
    {
        $raw = $this->getRawEmissions();

        return (new Emissions( $raw ))->getEmissionsData();
    }

    public function buildProfile()
    {
        if ( ! $this->exists() ) {
            return;
        }

        $this->populateArray( (new MemberModel)->getFullMember( $this->id ) );
    }

    /**
     * Saves a new member. This will also add a new User account if
     * the email doesn't exist.
     * @param array $data
     * @param Group $group
     * @param integer $year
     */
    public function saveToGroup( array $data, $group, $year )
    {
        expects( $data )->toHave([
            'name', 'email', 'locale', 'is_admin',
            'is_champion', 'locale_percent'
        ]);

        // Find the user
        $user = User::getByEmail( $data[ 'email' ] );
        $user->save([
            'name' => $data[ 'name' ],
            'email' => $data[ 'email' ]
        ]);

        // Create the new member association
        $this->year = $year;
        $this->user_id = $user->id;
        $this->group_id = $group->id;
        $this->populateArray( $data );
        $this->is_admin = ( $data[ 'is_admin' ] == 1 ) ? 1 : 0;
        $this->is_champion = ( $data[ 'is_champion' ] == 1 ) ? 1 : 0;
        $this->is_standard = ( is_numeric( $this->is_standard ) )
            ? $this->is_standard
            : 1;
        $this->save();

        // Clear this to reload the emissions/members
        $group->clearCache();
    }

    /**
     * Removes a member from the group.
     * @param array $data
     * @param Group $group
     * @param integer $year
     */
    public function removeFromGroup()
    {
        $this->remove([
            Model::OPTION_DELETE => TRUE
        ]);
    }

    static public function findByGroup( Group $group, $year )
    {
        $members = (new MemberModel)->fetchByGroupYear( $group->id, $year );

        return self::hydrate( $members );
    }

    static public function findByUser( User $user )
    {
        $members = (new MemberModel)->fetchByUser( $user->id );

        return self::hydrate( $members );
    }
}