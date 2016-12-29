<?php

namespace App\Entities;

use App\Entity
  , App\Entities\Member
  , App\Models\User as UserModel;

class User extends Entity
{
    public $id;
    public $name;
    public $email;

    public function getGroups()
    {
        return Member::findByUser( $this );
    }
}