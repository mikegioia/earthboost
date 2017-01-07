<?php

namespace App\Entities;

use App\Model
  , App\Entity
  , App\Entities\Group
  , App\Libraries\Calculator
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
            $this->offset_amount = $this->getOffsetAmount();
            $this->emissions_data = $this->getEmissionsData();
        }
    }

    /**
     * Returns the emissions if it's hard-set on the member record.
     * Otherwise, computes it from the database.
     * @param bool $ignoreCache
     * @param bool $ignoreStandard
     * @return float
     */
    public function getEmissions( $ignoreCache = FALSE, $ignoreStandard = FALSE )
    {
        if ( $this->emissions && ! $ignoreCache ) {
            return $this->emissions;
        }

        // If this is a "standard" calculation then use the
        // locale value.
        if ( $this->is_standard == 1 && ! $ignoreStandard ) {
            $locale = $this->getLocale( $this->locale );

            return ( $locale->mt * $this->locale_percent / 100 );
        }

        $raw = $this->getRawEmissions( $ignoreStandard );

        return (new Calculator( $raw ))->calculate();
    }

    /**
     * Load the raw emissions records from the database.
     * @return array of objects
     */
    public function getRawEmissions( $ignoreStandard = FALSE )
    {
        if ( $this->_rawEmissions ) {
            return $this->_rawEmissions;
        }

        if ( ! $this->exists()
            || $this->is_standard == 1 && ! $ignoreStandard )
        {
            return [];
        }

        $this->_rawEmissions = (new EmissionsModel)->fetchAll([
            'year' => $this->year,
            'user_id' => $this->user_id,
            'group_id' => $this->group_id
        ]);

        return $this->_rawEmissions;
    }

    /**
     * Returns the raw emissions data from the database.
     * @return array
     */
    public function getEmissionsData()
    {
        $raw = $this->getRawEmissions( TRUE );

        return (new Calculator( $raw ))->getEmissionsData();
    }

    /**
     * Computes the price to offset the emissions in MT.
     * @param float $emissions
     * @return float
     */
    public function getOffsetAmount( $emissions = NULL )
    {
        $emissions = ( is_null( $emissions ) )
            ? $this->emissions
            : $emissions;

        return (new Calculator)->price( $emissions );
    }

    public function isAdmin()
    {
        return $this->exists()
            && $this->is_admin == 1;
    }

    /**
     * Loads all of the data for the full member profile.
     */
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

    /**
     * Find all members by group and year.
     * @param Group $group
     * @param int $year
     * @param bool $populateEmissions
     * @return array
     */
    static public function findByGroup( Group $group, $year, $populateEmissions = FALSE )
    {
        $members = (new MemberModel)->fetchByGroupYear( $group->id, $year );

        return self::hydrate( $members, [
            self::POPULATE_EMISSIONS => $populateEmissions
        ]);
    }

    /**
     * Find all members by user ID.
     * @param User $user
     * @param bool $populateEmissions
     * @return array
     */
    static public function findByUser( User $user, $populateEmissions = FALSE )
    {
        $members = (new MemberModel)->fetchByUser( $user->id );

        return self::hydrate( $members, [
            self::POPULATE_EMISSIONS => $populateEmissions
        ]);
    }
}