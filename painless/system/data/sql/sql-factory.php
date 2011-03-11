<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Painless\System\Data\Sql;

class SqlFactory
{
    const WHERE = 1;
    const ORDER = 2;
    const LIMIT = 4;

    // Shorthand for WHERE | ORDER | LIMIT
    const ALL = 7;

    protected $where = array( );
    protected $order = array( );
    protected $limit = array( );

    protected $lastOp = '';

    public function open( )
    {
        $op = $this->lastOp;
        $this->{$op}[] = array( '(', '( ' );
        return $this;
    }

    public function close( )
    {
        $op = $this->lastOp;
        $this->{$op}[] = array( ')', ' )' );
        return $this;
    }

    /**
     * WHERE Clause Builder
     *
     * Usage:
     *  $sql = new PainlessSqlFactory;
     *  $sql->where( 'id = 1' );
     *  // generates WHERE id = 1
     *
     *  $sql->where( 'id = 1' )->where( 'id = 1' );
     *  // generates WHERE id = 1 AND id = 2
     *
     *  $sql->whereOr( 'id = 1' )->where( 'id = 2' );
     *  // generates WHERE id = 1 OR id = 2
     *
     *  $sql->where( 'id = 1' )->open( )->whereOr( 'id = 2' )->where( 'id = 3' )->close( );
     *  $sql->where( 'id = 1' )->open( )->whereOr( 'id = 2' )->whereOr( 'id = 3' )->close( );
     *  // generates WHERE id = 1 AND ( id = 2 OR id = 3 )
     *
     *  $sql->whereOr( 'id = 1' )->open( )->where( 'id = 2' )->where( 'id = 3' ) )->close( );
     *  // generates WHERE id = 1 OR ( id = 3 AND id = 3 )
     *
     *  $sql->where( 'id = 1' )
     *      ->open( )
     *      ->open( )
     *      ->whereOr( 'id = 2' )
     *      ->where( 'id = 3' )
     *      ->close( )
     *      ->open( )
     *      ->where( 'id = 4' )
     *      ->close( )
     *      ->close( )
     *  // generates WHERE id = 1 AND ( ( id = 2 OR id = 3 ) AND ( id = 4 ) )
     *  // although if your WHERE query gets this complex, you might as well use plain
     *  // text instead:
     *  // $sql->where( 'id = 1 AND ( ( id = 2 OR id = 3 ) AND ( id = 4 ) )' );
     * 
     * @param <type> $cond
     * @param <type> $child
     */
    public function where( $cond )
    {
        $this->where[] = array( ' AND ', $cond );
        $this->lastOp = 'where';
        return $this;
    }

    public function whereOr( $cond )
    {
        $this->where[]= array( ' OR ', $cond );
        $this->lastOp = 'where';
        return $this;
    }

    public function limit( $count, $offset = 0 )
    {
        $this->limit = array( (int) $count, (int) $offset );
        return $this;
    }

    public function build( $option = self::ALL )
    {
        $where      = '';
        $limit      = '';

        // Build the WHERE clause
        $whereArr   = $this->where;
        $count      = count( $whereArr );

        // Only proceed if there's a where clause
        if ( $count > 0 && (bool) ( $option & self::WHERE ) )
        {
            $where .= 'WHERE ' . $whereArr[0][1] . ( ( $count > 1 && $whereArr[0][0] !== '(' && $whereArr[0][0] !== ')' ) ? ' ' . $whereArr[0][0] . ' ' : '' );
            $lastOp = '';
            for( $i = 1; $i < $count; $i++ )
            {
                $isEnd      = ( $i === $count - 1 );
                $prevClose  = ( $whereArr[$i - 1][0] === ')' );
                $nextClose  = ( ! $isEnd && $whereArr[$i + 1][0] === ')' );

                list( $op, $cond ) = $whereArr[$i];

                // We only need to prepend the operator on this segment if (1) the
                // previous segment is a closing bracket; and (2) if the current
                // segment is not a closing bracket
                $prepend = ( $prevClose && $op !== ')' );

                // We only need to append the operator on this segment if (1) the
                // current segment is not a bracket; (2) if the next segment
                // is not a closing bracket; and (3) this is not the last segment
                $append = ( ! $nextClose && $op !== '(' && $op !== ')' && ! $isEnd );

                $where .= ( $prepend ? " $lastOp " : '' ) . $cond . ( $append ? " $op " : '' );

                if ( $op !== '(' && $op !== ')' )
                    $lastOp = $op;
            }
        }

        // Build the LIMIT clause
        if ( ! empty( $this->limit ) )
        {
            list( $count, $offset ) = $this->limit;

            if ( $offset === 0 )
                $limit = "LIMIT $count";
            else
                $limit = "LIMIT $offset, $count";
        }

        // Wipe out all operations
        $this->where = NULL;
        $this->order = NULL;
        $this->limit = NULL;

        return $where . ' ' . $limit;
    }
}

/**
Test WHERE clause generation:
$sql = \Painless::app( )->load( 'system/data/sql/sql-factory' );
$sql->where( 'id = 1' )
     ->open( )
     ->open( )
     ->whereOr( 'id = 2' )
     ->where( 'id = 3' )
     ->close( )
     ->open( )
     ->where( 'id = 4' )
     ->close( )
     ->close( )
     ->build( );
// WHERE id = 1  AND  ( ( id = 2  OR  id = 3 )  AND  ( id = 4 ) )

$sql2 = new PainlessSqlFactory;
$sql2->whereOr( 'id = 3' )->where( 'id = 5')->build( );
// WHERE id = 3  OR  id = 5

$sql3 = new PainlessSqlFactory;
$sql3->where( 'id = 1' )->open( )->whereOr( 'id = 2' )->where( 'id = 3' )->whereOr( 'id = 4' )->close( )->where( 'id = 6' )->build( );
// WHERE id = 1  AND  ( id = 2  OR  id = 3  AND  id = 4 )  OR  id = 6

$sql4 = new PainlessSqlFactory;
// empty
 */