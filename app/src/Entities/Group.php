<?php

namespace App\Entities;

use App\Entity
  , App\Emissions
  , App\Entities\Member
  , App\Models\Group as GroupModel
  , App\Models\Emissions as EmissionsModel;

class Group extends Entity
{
    public $id;
    public $name;
    public $type;
    public $label;

    // Cached
    private $_members;
    private $_emissions;
    private $_rawEmissions;

    /**
     * Load the group by it's unique name.
     * @param string $name
     */
    static public function loadByName( $name )
    {
        $group = new static;
        $sqlGroup = (new GroupModel)->getByName( $name );

        if ( $sqlGroup ) {
            $group->populateArray( $sqlGroup );
        }

        return $group;
    }

    /**
     * Creates a new group from the name and label.
     * @param string $name
     * @param string $label
     */
    public function create( $name, $label )
    {
        $sqlGroup = (new GroupModel)->save([
            'name' => $name,
            'label' => $label
        ]);

        if ( $sqlGroup ) {
            $this->populateArray( $sqlGroup );
        }
    }

    /**
     * Returns an array of Member objects containing all members
     * of the group and their emissions for the year.
     * @param int $year
     * @return array of Members
     */
    public function getMembers( $year )
    {
        if ( ! is_null( $this->_members ) ) {
            return $this->_members;
        }

        $this->_members = Member::findByGroup( $this, $year );

        return $this->_members;
    }

    /**
     * Computes the group's emissions and includes the total from
     * the staff members as well.
     * @param int $year
     * @return float
     */
    public function getEmissions( $year )
    {
        if ( ! $this->_emissions ) {
            $raw = $this->getRawEmissions( $year );
            $this->_emissions = (new Emissions( $raw ))->calculate();
        }

        return $this->_emissions;
    }

    /**
     * Load the raw emissions records.
     * @param int $year
     * @return array of objects
     */
    public function getRawEmissions( $year )
    {
        if ( $this->_rawEmissions ) {
            return $this->_rawEmissions;
        }

        $this->_rawEmissions = (new EmissionsModel)->fetchAll([
            'year' => $year,
            'group_id' => $this->id
        ]);

        return $this->_rawEmissions;
    }

    public function getOffsetAmount( $year )
    {
        return (new Emissions)->price( $this->getEmissions( $year ) );
    }
}