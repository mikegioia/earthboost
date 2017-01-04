<?php

namespace App\Entities;

use App\Entity
  , App\Models\Emissions as EmissionsModel;

class Emissions extends Entity
{
    public $id;
    public $year;
    public $value;
    public $type_id;
    public $user_id;
    public $event_id;
    public $group_id;

    protected $_modelClass = 'Emissions';

    /**
     * Saves the data to the entity in SQL.
     * @param array $data
     */
    public function save( array $data = NULL )
    {
        // Load the ID and other data to not create a dupe.
        $this->checkExists();

        return parent::save( $data );
    }

    /**
     * Check to see if there's a record for this user/group/question.
     * @param bool $populate Whether to update this object
     * @return bool
     */
    public function checkExists( $populate = TRUE )
    {
        $params = [
            'year' => $this->year,
            'type_id' => $this->type_id,
            'group_id' => $this->group_id,
            'user_id' => ( $this->user_id ) ?: NULL
        ];

        if ( $this->event_id ) {
            $params[ 'event_id' ] = $this->event_id;
        }

        $emissions = (new EmissionsModel)->get( $params );

        if ( $emissions && $populate ) {
            $this->populateArray( $emissions );
        }

        return ( $emissions ) ? TRUE : FALSE;
    }
}