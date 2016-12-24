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
    public $emissions;
    public $is_standard;
    public $locale_percent;
    // Loaded from the database
    public $emissions_data = [];

    // Cached
    private $_rawEmissions;

    const POPULATE_EMISSIONS = 'populate_emissions';

    public function __construct( $id = NULL, $options = [] )
    {
        parent::__construct( $id, $options );

        if ( is_null( $this->emissions )
            && get( $options, self::POPULATE_EMISSIONS, TRUE ) === TRUE )
        {
            $this->emissions = $this->getEmissions();
            $this->emissions_data = $this->getEmissionsData();
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

        if ( ! $this->exists() ) {
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
}