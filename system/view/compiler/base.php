<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

abstract class PainlessBaseCompiler
{
    abstract public function process( $view );

    protected function handleStatus( $response )
    {
        $status = $response->status;

        // See if there's an appropriate status handler for this response code
        $func = 'handle' . $status;
        if ( method_exists( $this, $func ) )
            return $this->$func( $response );

        return FALSE;
    }
}