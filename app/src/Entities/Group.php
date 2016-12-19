<?php

namespace App\Entities;

use App\Entity
  , App\Models\Group as GroupModel;

class Group extends Entity
{
    public $id;
    public $name;
    public $label;
    public $created_on;

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
}