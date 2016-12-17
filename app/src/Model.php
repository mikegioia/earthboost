<?php

namespace App;

class Model
{
    protected $table;
    protected $alias;
    protected $limit = 10;

    const SORT_ASC = 'asc';
    const SORT_DESC = 'desc';

    public function __construct( $data = [] )
    {
        if ( ! $data ) {
            return;
        }

        if ( valid( $data, INT ) ) {
            $data = [ 'id' => $data ];
        }

        foreach ( $data as $key => $value ) {
            if ( property_exists( $this, $key ) ) {
                $this->$key = $value;
            }
        }
    }
}