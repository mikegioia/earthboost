<?php

namespace App;

use Pixie\Connection as DatabaseConnection
  , Pixie\QueryBuilder\QueryBuilderHandler as QueryBuilder;

class Model
{
    // Static reference to database connection
    static private $db;

    protected $_table;
    protected $_limit = 10;

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
        exit( 'todo' );
        $this->validate( $data );
        $data = $this->quoteIdentifiers( $data );

        if ( valid( $this->id ) ) {
            $updated = $this->db( WRITE, FALSE )
                ->update(
                    $this->table,
                    $data, [
                        'id' => $this->id
                    ]);

            // This appears to return 0 on update when it should return
            // the number of non-affected rows. Keep an eye on this, because
            // it could be a DBAL bug, or we could be looking for a boolean
            // false on error.
            if ( ! is_numeric( $updated ) ) {
                throw new DatabaseException(
                    "Failed to update row #{$this->id} in database ".
                    "table {$this->table}." );
            }
        }
        else {
            $inserted = $this->db( WRITE, FALSE )
                ->insert( $this->table, $data );

            if ( ! $inserted ) {
                throw new DatabaseException(
                    "Failed to insert new row into database table ".
                    "{$this->table}." );
            }

            $this->id = $this->db( WRITE, FALSE )->lastInsertId();
        }

        return $this->getById( $this->id, $options );
    }

    /**
     * Default behavior is to perform a "soft delete" by flipping
     * on an is_deleted flag. Hard delete can be specified in the
     * options with key 'delete' => TRUE.
     * @param array $params
     * @param array $options
     * @return boolean
     */
    public function remove( array $params, array $options = [] )
    {
        exit( 'todo' );
        $qry = $this->db( WRITE );
        $conn = $this->dbConn( WRITE );

        if ( ! $params || ! count( $params ) ) {
            throw new DatabaseException(
                "Attempted to remove records with no search parameters!" );
        }

        $this->applyParams( $params, $qry, $conn );

        if ( get( $options, 'delete' ) === TRUE ) {
            return $qry->delete( $this->table )->execute();
        }

        return $qry
            ->update( $this->table )
            ->set( 'is_deleted', 1 )
            ->execute();
    }

    /**
     * Applies updates to the table, using params as where clauses.
     * @param array $params Where clauses
     * @param array $changes Set statements
     * @param array $options
     * @return boolean
     */
    public function update( array $params, array $changes, array $options = [] )
    {
        exit( 'todo' );
        $qry = $this->db( WRITE );
        $conn = $this->dbConn( WRITE );

        if ( ! $params || ! count( $params ) ) {
            throw new DatabaseException(
                "Attempted to update records with no search parameters!" );
        }

        if ( ! $changes || ! count( $changes ) ) {
            throw new DatabaseException(
                "Attempted to update records with no changes!" );
        }

        $this->applyParams( $params, $qry, $conn );

        foreach ( $changes as $key => $value ) {
            $qry->set( $key, $conn->quote( $value ) );
        }

        $updated = $qry->update( $this->table )->execute();

        // See comment in save()
        return is_numeric( $updated );
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
                implode( "\n", $return )
            ));
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