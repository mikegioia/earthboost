<?php

namespace App\Entities;

use App\Entity
  , App\Entities\Group
  , App\Entities\Member
  , App\Models\User as UserModel;

class User extends Entity
{
    public $id;
    public $name;
    public $email;

    // Cache
    private $_groups;

    protected $_modelClass = 'User';

    public function getGroups()
    {
        if ( $this->_groups ) {
            return $this->_groups;
        }

        $this->_groups = Member::findByUser( $this );

        return $this->_groups;
    }

    public function isMemberOf( Group $group )
    {
        $groups = $this->getGroups();

        foreach ( $groups as $memberGroup ) {
            if ( $group->id == $memberGroup->group_id ) {
                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * Finds the Member record for the given group and year.
     * @param Group $group
     * @param integer $year
     * @return Member
     */
    public function getMember( Group $group, $year )
    {
        $groups = $this->getGroups();

        foreach ( $groups as $memberGroup ) {
            if ( $group->id == $memberGroup->group_id
                && $year == $memberGroup->year )
            {
                return $memberGroup;
            }
        }

        return new Member;
    }

    /**
     * Computes the emissions for the group member.
     * @param Group $group
     * @param integer $year
     * @param bool $computedOnly Ignores the standard or hard-set value.
     * @return Member
     */
    public function getEmissions( Group $group, $year, $computedOnly = TRUE )
    {
        $member = $this->getMember( $group, $year );

        return $member->getEmissions( $computedOnly, $computedOnly );
    }

    public function getOffsetAmount( Group $group, $year, $computedOnly = TRUE )
    {
        $member = $this->getMember( $group, $year );
        $emissions = $member->getEmissions( $computedOnly, $computedOnly );

        return $member->getOffsetAmount( $emissions );
    }

    static public function getByEmail( $email )
    {
        $user = new static;
        $sqlUser = (new UserModel)->getByEmail( $email );

        if ( $sqlUser ) {
            $user->populateArray( $sqlUser );
        }

        return $user;
    }
}