<?php

namespace App\Entities;

use App\Entity
  , App\Emissions
  , App\Entities\Group
  , App\Models\Member as MemberModel
  , App\Models\Emissions as EmissionsModel;

class Member extends Entity
{
    public $id;
    public $year;
    public $name;
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
    public $locale_percent;
    // Loaded from the database
    public $emissions_data = [];

    // Cached
    private $_rawEmissions;

    const POPULATE_EMISSIONS = 'populate_emissions';

    public function __construct( $id = NULL, $options = [] )
    {
        parent::__construct( $id, $options );

        $this->is_admin = ( $this->is_admin == 1 );
        $this->is_champion = ( $this->is_champion == 1 );
        $this->is_standard = ( $this->is_standard == 1 );

        if ( is_null( $this->emissions )
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
        if ( $this->is_standard ) {
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

        if ( ! $this->exists() || $this->is_standard ) {
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