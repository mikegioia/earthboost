<?php

use App\Libraries\Expects;

/**
 * Get a value from an object/array. Optionally you can
 * specify a default to return if the key isn't set. The
 * final parameter will return only whether or not the
 * index exists.
 */
function get( $object, $index, $default = FALSE, $checkIndexExists = FALSE )
{
    if ( is_array( $object ) ) {
        if ( isset( $object[ $index ] ) ) {
            return ( $checkIndexExists ) ? TRUE : $object[ $index ];
        }
    }
    else {
        if ( isset( $object->$index ) ) {
            return ( $checkIndexExists ) ? TRUE : $object->$index;
        }
    }
    return $default;
}

/**
 * Check if a variable is valid for the specified type.
 */
function valid( $mixed, $expectedType = INT )
{
    if ( $expectedType === INT ) {
        return is_numeric( $mixed )
            && strlen( $mixed )
            && intval( $mixed ) > 0;
    }
    elseif ( $expectedType === STRING ) {
        return is_string( $mixed )
            && strlen( $mixed ) > 0;
    }
    elseif ( $expectedType === DATE ) {
        // Check if date is of form YYYY-MM-DD HH:MM:SS and that it
        // is not 0000-00-00 00:00:00.
        if ( strlen( $mixed ) === 19 && ! str_eq( $mixed, DATE_DB_EMPTY ) ) {
            return TRUE;
        }

        // Check for MM/DD/YYYY type dates
        $parts = explode( "/", $mixed );

        return count( $parts ) === 3
            && checkdate( $parts[ 0 ], $parts[ 1 ], $parts[ 2 ] );
    }
    elseif ( $expectedType === OBJECT ) {
        // Iterate through object and check if there are any properties
        foreach ( $mixed as $property ) {
            if ( $property ) {
                return TRUE;
            }
        }
    }
    elseif ( $expectedType === URL ) {
        return preg_match(
            "#(^|\s|\()((http(s?)://)|(www\.))(\w+[^\s\)\<]+)#i",
            $mixed );
    }

    return FALSE;
}

function camel_to_underscore( $string )
{
    return ltrim(
        strtolower(
            preg_replace(
                '/[A-Z]/',
                '_$0',
                $string
            )),
        '_' );
}

/**
 * Returns the public object properties
 */
function get_public_vars( $object )
{
    $array = [];
    $data = get_object_vars( $object );

    foreach ( $data as $k => $v ) {
        $array[ $k ] = $v;
    }

    return $array;
}

/**
 * Wrapper for Expects assertion library.
 */
function expects( $data )
{
    return new Expects( $data );
}

function get_year( $year = NULL )
{
    return ( $year ) ?: date( "Y" ) - (date( "m" ) <= 3 ? 1 : 0);
}