<?php

namespace App\Entities;

use App\Entity
  , App\Entities\Group
  , App\Entities\Member
  , App\Libraries\Questions
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
     * Get the next tast for the user. This is determined from their
     * activity over the year.
     * @param Group $group
     * @param string $year
     * @return object {
     *   key: string
     *   number: int
     * }
     */
    public function getTask( Group $group, $year )
    {
        $counter = 1;
        // Find the membership
        $member = $this->getMember( $group, $year );
        $make = function ( $number, $key ) {
            return (object) [
                'key' => $key,
                'number' => $number
            ];
        };

        // Check if the group has any members
        // Check if the group has a complete profile
        if ( $member->isAdmin() ) {
            $members = $group->getMembers( $year );

            if ( count( $members ) <= 1 ) {
                return $make( $counter, 'add_member' );
            }

            $counter++;
            $questions = new Questions( $this->getQuestions() );
            $profile = $questions->getProfile( $group, new User, $year );

            if ( ! $profile->is_complete ) {
                return $make( $counter, 'group_profile' );
            }

            $counter++;
        }

        // Check user profile
        if ( $member->isStandard() ) {
            return $make( $counter, 'user_profile' );
        }

        $counter++;

        if ( $member->isAdmin() ) {
            return $make( $counter, 'group_notify' );
        }

        return $make( $counter, 'canopy_club' );
    }

    /**
     * Returns the calculator profile info.
     * @param Group $group
     * @param string $year
     * @return object
     */
    public function getProfile( Group $group, $year )
    {
        $questions = new Questions( $this->getQuestions() );
        $profile = $questions->getProfile( $group, $this, $year );
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