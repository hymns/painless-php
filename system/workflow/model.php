<?php

class PainlessModel
{
    const SUCCESS = 200;
    const FAILURE = 400;

    /**
     * A shorthand to return a properly formed response object to the calling
     * workflow.
     * @param boolean $status   TRUE if the operation is successful, FALSE if otherwise
     * @param mixed $data       usually, either an array or a string
     * @return array            an array where the key 'status' is TRUE or FALSE, and 'data' is the returned data
     */
    protected function response( $status, $data )
    {
        return array( 'status' => $status, 'data' => $data );
    }

    protected function validateNull( $v )                  { return empty( $v ); }
    protected function validateEmail( $v )                 { return ( filter_var( $v, FILTER_VALIDATE_EMAIL ) !== FALSE ); }
    protected function validateEquals( $v1, $v2 )          { return ( $v1 == $v2 ); }
    protected function validateIdentical( $v1, $v2 )       { return ( $v1 === $v2 ); }
}
