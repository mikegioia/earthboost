<?php

namespace App;

use DateTime
  , Particle\Validator\ValidationResult
  , Pixie\Connection as DatabaseConnection
  , App\Exceptions\Database as DatabaseException
  , Pixie\QueryBuilder\QueryBuilderHandler as QueryBuilder;

class Model
{
    // Static reference to database connection
    static private $db;

    protected $_table;
    protected $_limit = 10;

    const SORT_ASC = 'asc';
    const SORT_DESC = 'desc';
    const OPTION_DELETE = 'delete';

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

    static public function setDb( $connection )
    {
        self::$db = $connection;
    }

    /**
     * Load a new instance of the query builder.
     */
    public function qb()
    {
        return new QueryBuilder( self::$db );
    }

    /**
     * Helper method to retrieve a single item.
     * @param array $params
     * @param array $options
     * @return object
     */
    public function get( array $params = [], array $options = [] )
    {
        return $this->executeFetch( $params, $options, FALSE );
    }

    /**
     * Helper method to retrieve a collection of items.
     * @param array $params
     * @param array $options
     * @return array of objects
     */
    public function fetch( array $params = [], array $options = [] )
    {
        return $this->executeFetch( $params, $options );
    }

    /**
     * Same as fetch, but this ignores the limit.
     * @param array $params
     * @param array $options
     * @return array of objects
     */
    public function fetchAll( array $params = [], array $options = [] )
    {
        // Allow a limit if it came in
        if ( ! isset( $options[ 'limit' ] ) ) {
            $options[ 'limit' ] = 0;
        }

        return $this->executeFetch( $params, $options );
    }

    /**
     * Fetch all table IDs.
     */
    public function fetchAllIds()
    {
        $results = $this->fetch( [], [
            'limit' => 0,
            'select' => 'id'
        ]);

        return ( $results )
            ? array_column( $results, 'id' )
            : [];
    }

    private function executeFetch( array $params = [], array $options = [], $fetchAll = TRUE )
    {
        $qry = $this->qb()
            ->table( $this->_table )
            ->select( get( $options, 'select', '*' ) )
            ->orderBy(
                get( $options, 'sort', 'id' ),
                get( $options, 'sortDir', self::SORT_ASC ) );

        if ( valid( get( $options, 'limit', $this->_limit ) ) ) {
            $qry->offset( get( $options, 'offset', 0 ) )
                ->limit( get( $options, 'limit', $this->_limit ) );
        }

        if ( get( $options, 'includeDeleted' ) !== TRUE
            && property_exists( $this, 'is_deleted' ) )
        {
            $params[ 'is_deleted' ] = 0;
        }

        $this->applyParams( $params, $qry );

        return ( $fetchAll )
            ? $qry->get()
            : $qry->first();
    }

    /**
     * Saves a record to the database.
     * @param array $data
     * @param array $options
     * @throws DatabaseException
     * @return array
     */
    public function save( array $data = [], array $options = [] )
    {
        $this->validate( $data );
        $this->stripInvalid( $data );
        $this->addCreatedOn( $data );

        if ( valid( $this->id, INT ) ) {
            $updated = $this->qb()
                ->table( $this->_table )
                ->where( 'id', $this->id )
                ->update( $data );

            if ( ! is_numeric( $updated->rowCount() ) ) {
                throw new DatabaseException(
                    "Failed to update row #{$this->id} in database ".
                    "table {$this->_table}." );
            }
        }
        else {
            $inserted = $this->qb()
                ->table( $this->_table )
                ->insert( $data );

            if ( ! is_numeric( $inserted ) ) {
                throw new DatabaseException(
                    "Failed to insert new row into database table ".
                    "{$this->_table}." );
            }

            $this->id = $inserted;
        }

        return $this->getById( $this->id, $options );
    }

    public function upsert( array $data = [], array $options = [] )
    {
        $this->validate( $data );
        $this->stripInvalid( $data );
        $this->addCreatedOn( $data );

        $inserted = $this->qb()
            ->table( $this->_table )
            ->onDuplicateKeyUpdate( $data )
            ->insert( $data );

        if ( ! is_numeric( $inserted ) && ! is_null( $inserted ) ) {
            throw new DatabaseException(
                "Failed to upsert new row into database table ".
                "{$this->_table}." );
        }

        $this->id = $inserted;

        return $this->getById( $this->id, $options );
    }

    /**
     * Default behavior is to perform a "soft delete" by flipping
     * on an is_deleted flag. Hard delete can be specified in the
     * options with key 'delete' => TRUE.
     * @param array $params
     * @param array $options
     * @throws DatabaseException
     * @return boolean
     */
    public function remove( array $params, array $options = [] )
    {
        $qry = $this->qb()->table( $this->_table );

        if ( ! $params || ! count( $params ) ) {
            throw new DatabaseException(
                "Attempted to remove records with no search parameters!" );
        }

        $this->applyParams( $params, $qry );

        if ( get( $options, self::OPTION_DELETE ) === TRUE ) {
            return $qry->delete();
        }

        $updated = $qry->update([
            'is_deleted' => 1
        ]);

        if ( ! is_numeric( $updated->rowCount() ) ) {
            throw new DatabaseException(
                "Failed to mark deleted row #{$this->id} in database ".
                "table {$this->_table}." );
        }

        return TRUE;
    }

    /**
     * Reads in a set of parameters and applies them to the query.
     * @param array $params
     * @param QueryBuilder $qry Reference to query object
     */
    private function applyParams( array $params, QueryBuilder &$qry )
    {
        foreach ( $params as $key => $value ) {
            if ( is_null( $value ) ) {
                $qry->whereNull( $key );
            }
            else if ( is_array( $value ) ) {
                $qry->whereIn( $key, $value );
            }
            else {
                $qry->where( $key, "=", $value );
            }
        }
    }

    /**
     * Implemented in models.
     * @param array $data
     * @throws ValidationException
     * @return bool
     */
    public function validate( array $data ) {}

    private function stripInvalid( array &$data )
    {
        $data = array_intersect_key( $data, get_public_vars( $this ) );
    }

    private function addCreatedOn( array &$data )
    {
        if ( ! valid( $this->id, INT )
            && property_exists( $this, 'created_on' ) )
        {
            $data[ 'created_on' ] = (new DateTime)->format( DATE_SQL );
        }
    }

    public function getErrorString( ValidationResult $result, $message )
    {
        $return = [];
        $messages = $result->getMessages();

        foreach ( $messages as $key => $messages ) {
            $return = array_merge( $return, array_values( $messages ) );
        }

        return trim(
            sprintf(
                "%s\n\n%s",
                $message,
                implode( "\n", $return )),
             ". \t\n\r\0\x0B" ) . ".";
    }

    /**
     * Checks a date field for a valid date. If it doesn't exist,
     * this will set the date field to NULL.
     * @param array $data Reference to the data array
     * @param string $field
     */
    public function prepareDate( array &$data, $field )
    {
        if ( isset( $data[ $field ] ) ) {
            if ( $data[ $field ]
                && ( $time = strtotime( $data[ $field ] ) ) )
            {
                $data[ $field ] = (new DateTime( "@$time" ))->format( DATE_SQL );
            }
            else {
                $data[ $field ] = NULL;
            }
        }
    }

    /**
     * Catch-all for getByProperty and fetchByProperty. The
     * getBy call returns only 1 entry. The fetchBy call returns
     * an array of entries.
     */
    public function __call( $alias, array $args )
    {
        $value = reset( $args );
        $options = ( count( $args ) > 1 )
            ? $args[ 1 ]
            : [];

        // getByProperty method returns only 1 by key
        if ( strpos( $alias, 'getBy' ) === 0 ) {
            // Key is everything after getBy
            $key = camel_to_underscore( substr( $alias, 5 ) );

            if ( ! empty( $key ) ) {
                return $this->get([ $key => $value ], $options );
            }
        }
        // fetchByProperty method returns a collection by key
        elseif ( strpos( $alias, 'fetchBy' ) === 0 ) {
            // Key is everything after fetchBy
            $key = camel_to_underscore( substr( $alias, 7 ) );

            if ( ! empty( $key ) ) {
                return $this->fetchAll([ $key => $value ], $options );
            }
        }
    }
}