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

    protected $_modelClass = 'User';

    public function getGroups()
    {
        return Member::findByUser( $this );
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