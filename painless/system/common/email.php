<?php

/**
 * Painless PHP - the painless path to development
 *
 * Copyright (c) 2011, Tan Long Zheng (soggie)
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  * Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *  * Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *  * Neither the name of Rendervault Solutions nor the names of its
 *    contributors may be used to endorse or promote products derived from
 *    this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package     Painless PHP
 * @author      Tan Long Zheng (soggie) <ruben@rendervault.com>
 * @copyright   2011 Tan Long Zheng (soggie) <ruben@rendervault.com>
 * @license     BSD 3 Clause (New BSD)
 * @link        http://painless-php.com
 */

namespace Painless\System\Common;

class Email
{
    const TEXT = '';
    const HTML = 'text/html';

    protected $to           = array( );
    protected $cc           = array( );
    protected $bcc          = array( );
    protected $subject      = '';
    protected $content      = '';
    protected $contentType  = '';
    protected $charset      = '';
    protected $encoding     = '';
    protected $mailbox      = '';

    //--------------------------------------------------------------------------
    protected function init( )
    {
        // Get the config component
        $config = \Painless::load( 'system/common/config' );
        $email  = $config->get( 'email.*' );
        
        // set the default content type if none specified.
        if ( '' === $this->contentType )
            $this->contentType = array_get( $email, 'email.content_type' );

        // set the default charset if none specified.
        if ( '' === $this->charset )
            $this->charset = array_get( $email, 'email.charset' );

        // set the default content transfer encoding if none specified.
        if ( '' === $this->encoding )
            $this->encoding = array_get( $email, 'email.content_transfer_encoding' );
    }

    //--------------------------------------------------------------------------
    public function to( $name, $address = '' )
    {
        if ( empty( $address ) )
            $this->to[] = array( 'name' => '', 'address' => $name );
        else
            $this->to[] = array( 'name' => $name, 'address' => $address );
        
        return $this;
    }

    //--------------------------------------------------------------------------
    protected function generateTo( )
    {
        $toStr = '';
        $count = count( $this->to ) - 1;
        for ( $i = 0; $i < $count; ++$i )
        {
            $toName = $this->to[$i]['name'];
            $toAddress = $this->to[$i]['address'];
            $toStr .= "$toName <$toAddress>, ";
        }
        if ( $count >= 1 )
        {
            $toName = $this->to[count( $this->to ) - 1]['name'];
            $toAddress = $this->to[count( $this->to ) - 1]['address'];
            $toStr .= "$toName <$toAddress>";
        }

        return $toStr;
    }

    //--------------------------------------------------------------------------
    public function cc( $name, $address )
    {
        $this->cc[] = array( 'name' => $name, 'address' => $address );
        return $this;
    }

    //--------------------------------------------------------------------------
    protected function generateCc( )
    {
        $ccStr = '';
        $count = count( $this->cc ) - 1;
        for ( $i = 0; $i < $count; ++$i )
        {
            $ccName = $this->cc[$i]['name'];
            $ccAddress = $this->cc[$i]['address'];
            $ccStr .= "$ccName <$ccAddress>, ";
        }
        if ( $count >= 1 )
        {
            $ccName = $this->cc[count( $this->cc ) - 1]['name'];
            $ccAddress = $this->cc[count( $this->cc ) - 1]['address'];
            $ccStr .= "$ccName <$ccAddress>";
        }

        return $ccStr;
    }

    //--------------------------------------------------------------------------
    public function bcc( $name, $address )
    {
        $this->bcc[] = array( 'name' => $name, 'address' => $address );
        return $this;
    }

    //--------------------------------------------------------------------------
    protected function generateBcc( )
    {
        $bccStr = '';
        $count = count( $this->bcc ) - 1;
        for ( $i = 0; $i < $count; ++$i )
        {
            $bccName = $this->bcc[$i]['name'];
            $bccAddress = $this->bcc[$i]['address'];
            $bccStr .= "$bccName <$bccAddress>, ";
        }
        if ( $count >= 1 )
        {
            $bccName = $this->bcc[count( $this->bcc ) - 1]['name'];
            $bccAddress = $this->bcc[count( $this->bcc ) - 1]['address'];
            $bccStr .= "$bccName <$bccAddress>";
        }

        return $bccStr;
    }
    
    //--------------------------------------------------------------------------
    public function subject( $subject )
    {
        $this->subject = $subject;
        return $this;
    }

    //--------------------------------------------------------------------------
    public function content( $content )
    {
        $this->content = $content;
        return $this;
    }

    //--------------------------------------------------------------------------
    public function contentType( $contentType )
    {
        $this->contentType = $contentType;
        return $this;
    }

    //--------------------------------------------------------------------------
    public function charset( $charset )
    {
        $this->charset = $charset;
        return $this;
    }

    //--------------------------------------------------------------------------
    public function encoding( $contentTransferEncoding )
    {
        $this->encoding = $contentTransferEncoding;
        return $this;
    }

    //--------------------------------------------------------------------------
    public function mailbox( $identity )
    {
        $this->mailbox = $identity;
        return $this;
    }

    //--------------------------------------------------------------------------
    /*
     * Sends the e-mail.
     * @return boolean      TRUE if success, FALSE otherwise.
     */
    public function send( )
    {
        $this->init( );

        $config = \Painless::load( 'system/common/config' );

        $smtpServer = $config->get( 'email.host' );
        $port = $config->get( 'email.port' );
        $timeout = $config->get( 'email.timeout' );

        $mailbox = $this->mailbox;
        if ( '' === $mailbox )
        {
            $mailbox = 'default';
        }

        // Get the correct email mailbox profile
        $fromName       = $config->get( "email.$mailbox.from_name" );
        $fromAddress    = $config->get( "email.$mailbox.from_address" );
        $username       = $config->get( "email.$mailbox.username" );
        $password       = $config->get( "email.$mailbox.password" );

        // Connect to the host on the specified port
        $smtpConnect = fsockopen( $smtpServer, $port, $errno, $errstr, $timeout );
        fgets( $smtpConnect, 515 );
        if ( empty( $smtpConnect ) )
        {
            return FALSE;
        }

        $newLine = "\r\n";

        // Request Auth Login
        fputs( $smtpConnect, "AUTH LOGIN" . $newLine );
        fgets( $smtpConnect, 515 );

        // Send username
        fputs( $smtpConnect, base64_encode( $username ) . $newLine );
        $ret = fgets( $smtpConnect, 515 );
        if ( stripos( $ret, 'error' ) !== FALSE )
        {
            return FALSE;
        }

        // Send password
        fputs( $smtpConnect, base64_encode( $password ) . $newLine );
        $ret = fgets( $smtpConnect, 515 );
        if ( stripos( $ret, 'error' ) !== FALSE )
        {
            return FALSE;
        }

        // Say Hello to SMTP
        fputs( $smtpConnect, "HELO $smtpServer" . $newLine );
        fgets( $smtpConnect, 515 );

        // Email From
        $from = $fromAddress;
        fputs( $smtpConnect, "MAIL FROM: $from" . $newLine );
        fgets( $smtpConnect, 515 );

        // Email To
        // includes all to, cc, bcc addresses.
        $count = count( $this->to );
        for ( $i = 0; $i < $count; ++$i )
        {
            $toAddress = $this->to[$i]['address'];
            fputs( $smtpConnect, "RCPT TO: $toAddress" . $newLine );
            fgets( $smtpConnect, 515 );
        }

        $count = count( $this->cc );
        for ( $i = 0; $i < $count; ++$i )
        {
            $ccAddress = $this->cc[$i]['address'];
            fputs( $smtpConnect, "RCPT TO: $ccAddress" . $newLine );
            fgets( $smtpConnect, 515 );
        }

        $count = count( $this->bcc );
        for ( $i = 0; $i < $count; ++$i )
        {
            $bccAddress = $this->bcc[$i]['address'];
            fputs( $smtpConnect, "RCPT TO: $bccAddress" . $newLine );
            fgets( $smtpConnect, 515 );
        }

        // The Email	
        fputs( $smtpConnect, "DATA" . $newLine );
        fgets( $smtpConnect, 515 );

        // if "text/html" is sent, send along a plain text version as well for great compatibility.
        if ( self::HTML === $this->contentType )
        {
            $randomHash = md5( date( 'r', time( ) ) );

            $toStr = $this->generateTo( );
            $ccStr = $this->generateCc( );
            $bccStr = $this->generateBcc( );

            $plainTextContent = strip_tags( $this->content ); // TODO: need better html stripper than this
            $subject = $this->subject;
            $content = $this->content;
            $charset = $this->charset;

            // Construct Headers
            $headers = "MIME-Version: 1.0" . $newLine;
            $headers .= "Subject: $subject" . $newLine;
            $headers .= "From: $fromName <$fromAddress>" . $newLine;
            $headers .= "To: $toStr" . $newLine;
            $headers .= "Cc: $ccStr" . $newLine;
            $headers .= "Bcc: $bccStr" . $newLine;
            $headers .= "Content-Type: multipart/alternative; boundary=$randomHash" . $newLine;

            $headers .= "--$randomHash" . $newLine;
            $headers .= "Content-Type: text/plain; charset=ISO-8859-1" . $newLine;
            $headers .= "$plainTextContent" . $newLine;

            $headers .= "--$randomHash" . $newLine;
            $headers .= "Content-Type: text/html; charset=\"$charset\"" . $newLine;
            $headers .= "$content" . $newLine;

            $headers .= "--$randomHash--" . $newLine;

            fputs( $smtpConnect, "$headers.\n" );
            fgets( $smtpConnect, 515 );
        }
        else
        {
            // Construct Headers
            $headers        = "MIME-Version: 1.0" . $newLine;
            $contentType    = $this->contentType;
            $charset        = $this->charset;
            $encoding       = $this->encoding;
            $headers        .= "Content-Type: $contentType; charset=\"$charset\"" . $newLine;
            $headers        .= "Content-transfer-encoding: $encoding" . $newLine;

            $toStr          = $this->generateTo( );
            $ccStr          = $this->generateCc( );
            $bccStr         = $this->generateBcc( );
            $subject        = $this->subject;
            $content        = $this->content;
            
            fputs( $smtpConnect, "To: $toStr\nCc: $ccStr\nBcc: $bccStr\nFrom: $fromName <$fromAddress>\nSubject: $subject\n$headers\n$content\n.\n" );
            fgets( $smtpConnect, 515 );
        }

        // Say Bye to SMTP
        fputs( $smtpConnect, "QUIT" . $newLine );
        fgets( $smtpConnect, 515 );

        return TRUE;
    }

}