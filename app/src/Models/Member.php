<?php

namespace App\Models;

use DateTime
  , App\Model
  , Particle\Validator\Validator
  , App\Exceptions\Validation as ValidationException;

class Member extends Model
{
    public $id;
    public $year;
    public $locale;
    public $user_id;
    public $group_id;
    public $is_admin;
    public $emissions;
    public $created_on;
    public $is_champion;
    public $is_standard;
    public $locale_percent;

    protected $_table = 'members';
    protected $_modelClass = 'Member';

    public function save( array $data = [], array $options = [] )
    {
        if ( ! valid( $this->id, INT ) ) {
            $data[ 'created_on' ] = (new DateTime)->format( DATE_SQL );
        }

        return parent::upsert( $data, $options );
    }

    public function validate( array $data )
    {
        $val = new Validator;
        $val->required( 'year', 'Year' )->integer();
        $val->required( 'locale', 'Locale' )->length( 5 );
        $val->required( 'user_id', 'User ID' )->integer();
        $val->required( 'group_id', 'Group ID' )->integer();
        $val->optional( 'emissions', 'Emissions' )->numeric();
        $val->required( 'is_admin', 'Is Admin' )->between( 0, 1 );
        $val->required( 'is_champion', 'Is Champion' )->between( 0, 1 );
        $val->required( 'is_standard', 'Is Standard' )->between( 0, 1 );
        $val->required( 'locale_percent', 'Locale Percentage' )->between( 1, 100 );
        $res = $val->validate( $data );

        if ( ! $res->isValid() ) {
            throw new ValidationException(
                $this->getErrorString(
                    $res,
                    "There was a problem validating this member."
                ));
        }

        // Remove any optional fields that are NULL

    }

    public function fetchByGroupYear( $groupId, $year )
    {
        return $this->buildBaseQuery()
            ->where( 'group_id', '=', $groupId )
            ->where( 'year', '=', $year )
            ->get();
    }

    public function fetchByUser( $userId )
    {
        return $this->buildBaseQuery()
            ->where( 'users.id', '=', $userId )
            ->get();
    }

    public function getFullMember( $memberId )
    {
        return $this->buildBaseQuery()
            ->where( 'members.id', '=', $memberId )
            ->first();
    }

    private function buildBaseQuery()
    {
        return $this->qb()
            ->table( $this->_table )
            ->select([
                "members.*",
                "users.name",
                "users.email",
                "users.id" => "user_id",
                "groups.name" => "group_name",
                "groups.type" => "group_type",
                "groups.label" => "group_label"
            ])
            ->join( 'users', 'users.id', '=', 'members.user_id' )
            ->join( 'groups', 'members.group_id', '=', 'groups.id' );
    }
}