<?php

namespace App\Exceptions;

use App\Exception
  , App\Entities\User
  , App\Entities\Group;

/**
 * Thrown when a user does not belong to a group.
 */
class NoMember extends Exception
{
    public $httpCode = 412;

    public function __construct( User $user, Group $group )
    {
        parent::__construct(
            $this->statusCode,
            sprintf(
                "%s is not a member of %s.",
                $user->name,
                $group->label
            ));
    }
}