<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Painless\System\Common;

class Factory
{
    public function request( $method, $module, $controller, $params = array( ), $contentType = '', $agent = '' )
    {
        $request = \Painless::load( 'system/workflow/request', \Painless::LP_LOAD_NEW );
        $request->method( $method );
        $request->module( $module );
        $request->controller( $controller );
        $request->params( $params );
        $request->contentType( $contentType );
        $request->agent( $agent );

        return $request;
    }

    public function response( $status, $message, $payload = array( ) )
    {
        $response = \Painless::load( 'system/workflow/response', \Painless::LP_LOAD_NEW );
        $response->status( $status );
        $response->message( $message );
        $response->payload( $payload );

        return $response;
    }
}