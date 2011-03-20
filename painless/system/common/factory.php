<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Painless\System\Common;

class Factory
{
    public function email( $mailbox, $to, $subject, $template, $data = NULL, $type = \Painless\System\Common\Email::TEXT )
    {
        // Send a notification e-mail to the user
        $response = \Painless::execute( \Painless::RUN_INTERNAL, 'POST email/index.html', array(
            'mailbox'   => $mailbox,
            'to'        => $to,
            'subject'   => $subject,
            'template'  => $template,
            'data'      => $data,
        ) );

        // Only send the e-mail if the response code is 200
        if ( $response->status === 200 )
        {
            $email = \Painless::load( 'system/common/email' );
            $email->contentType( $type );
            $email->content( $response->payload );
            return $email;
        }
        
        return NULL;
    }
    
    public function request( $method, $module, $controller, $params = array( ), $contentType = '', $agent = '' )
    {
        $request = \Painless::load( 'system/workflow/request', \Painless::LP_LOAD_NEW );

        // Set the variables that don't need any setter functions
        $request->method        = $method;
        $request->module        = $module;
        $request->controller    = $controller;
        $request->contentType   = $contentType;
        $request->agent         = $agent;

        // Set the params using the setter function
        $request->params( $params );

        return $request;
    }

    public function response( $status, $message, $payload = array( ) )
    {
        $response = \Painless::load( 'system/workflow/response', \Painless::LP_LOAD_NEW );
        $response->status   = (int)$status;
        $response->message  = $message;
        $response->payload  = $payload;

        return $response;
    }
}