<?php

namespace App\Entities;

use App\Entity
  , App\Entities\Member
  , App\Libraries\Emissions
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
     * Computes the group's emissions and optionally includes the total
     * from the staff members as well.
     * @param int $year
     * @param bool $includeMembers
     * @return float
     */
    public function getEmissions( $year, $includeMembers = TRUE )
    {
        $raw = $this->getRawEmissions( $year );
        $emissions = (new Emissions( $raw ))->calculate();

        if ( $includeMembers === TRUE ) {
            foreach ( $this->getMembers( $year ) as $member ) {
                $emissions += $member->emissions;
            }
        }

        return $emissions;
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
            'user_id' => NULL,
            'group_id' => $this->id
        ]);

        return $this->_rawEmissions;
    }

    /**
     * Computes the group's offset amount in USD.
     * @param int $year
     * @param bool $includeMembers
     * @return float
     */
    public function getOffsetAmount( $year, $includeMembers = TRUE )
    {
        return (new Emissions)->price(
            $this->getEmissions( $year, $includeMembers ) );
    }

    public function clearCache()
    {
        $this->_members = NULL;
        $this->_rawEmissions = NULL;
    }
}