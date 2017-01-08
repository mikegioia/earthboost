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

    /**
     * Load all of the groups by user ID.
     * @return array of Members
     */
    public function getGroups()
    {
        if ( $this->_groups ) {
            return $this->_groups;
        }

        $this->_groups = Member::findByUser( $this );

        return $this->_groups;
    }

    /**
     * Check if a user is a member of a group.
     * @param Group $group
     * @return bool
     */
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
     * @param bool $throwNowFound Throws exception if not found
     * @throws NotFoundException
     * @return Member
     */
    public function getMember( Group $group, $year, $throwNotFound = FALSE )
    {
        $groups = $this->getGroups();

        foreach ( $groups as $memberGroup ) {
            if ( $group->id == $memberGroup->group_id
                && $year == $memberGroup->year )
            {
                return $memberGroup;
            }
        }

        if ( $throwNotFound ) {
            throw new NotFoundException(
                NULL,
                NULL,
                "Failed to find Member for User #{$this->id} and ".
                "Group '{$group->name}'." );
        }

        return new Member;
    }

    /**
     * Computes the emissions for the group member.
     * @param Group $group
     * @param integer $year
     * @param bool $computedOnly Ignores the standard or hard-set value.
     * @return float
     */
    public function getEmissions( Group $group, $year, $computedOnly = TRUE )
    {
        $member = $this->getMember( $group, $year );

        return $member->getEmissions( $computedOnly, $computedOnly );
    }

    /**
     * Computes the offset amount in USD.
     * @param Group $group
     * @param integer $year
     * @param bool $computedOnly Ignores the standard or hard-set value.
     * @return float
     */
    public function getOffsetAmount( Group $group, $year, $computedOnly = TRUE )
    {
        $member = $this->getMember( $group, $year );
        $emissions = $member->getEmissions( $computedOnly, $computedOnly );

        return $member->getOffsetAmount( $emissions );
    }

    /**
     * Write access to a user. Load the group from the params and
     * check if the user has write access to that group and year.
     * @param User $user
     * @param array $params
     * @param bool
     */
    public function isWrittenBy( User $user, array $params )
    {
        expects( $params )->toHave([ 'group' ]);
        $year = get_year( get( $params, 'year' ) );
        $group = Group::loadByName( $params[ 'group' ] );

        return $this->id == $user->id
            || $user->getMember( $group, $year )->isAdmin();
    }

    /**
     * Read access check. Load the group from the params and check
     * if the user has read access to that group and year.
     * @param User $user
     * @param array $params
     * @param bool
     */
    public function isReadBy( User $user, array $params )
    {
        expects( $params )->toHave([ 'group' ]);
        $year = get_year( get( $params, 'year' ) );
        $group = Group::loadByName( $params[ 'group' ] );

        return $this->id == $user->id
            || $user->getMember( $group, $year )->exists();
    }

    /**
     * Find a user by email address.
     * @param string $email
     * @return User
     */
    static public function loadByEmail( $email )
    {
        $user = new static;
        $sqlUser = (new UserModel)->getByEmail( $email );

        if ( $sqlUser ) {
            $user->populateArray( $sqlUser );
        }

        return $user;
    }
}