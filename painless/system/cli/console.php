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

namespace Painless\System\Cli;

/**
 * Console
 *
 * This provides a simple interface to writing CLI applications easily by handling
 * the input and output of a console
 */
class Console
{

    /**
     * Copied verbatim from http://styledna.pastebin.com/f69855cc9
     * @author  Philip Sturgeon
     * @created 7 Oct 2008
     */
    public function read( )
    {
        // Work out whats what based on what params are given
        $args = func_get_args( );

        // Ask question with options
        if ( count( $args ) == 2 )
        {
            list( $output, $options ) = $args;


        }
        // No question (probably been asked already) so just show options
        elseif ( count( $args ) == 1 && is_array( $args[0] ) )
        {
            $output = '';
            $options = $args[0];


        }
        // Question without options
        elseif ( count( $args ) == 1 && is_string( $args[0] ) )
        {
            $output = $args[0];
            $options = array();

            
        }
        // Nothing or too many, forget trying to be clever and just get what they asked for
        else
        {
            $output = '';
            $options = array( );
        }

        // If a question has been asked with the read
        if ( ! empty( $output ) )
        {

            $options_output = '';
            if ( ! empty( $options ) )
            {
                $options_output = ' [ ' . implode( ', ', $options ) . ' ]';
            }

            fwrite( STDOUT, $output . $options_output . ': ' );
        }

        // Read the input from keyboard.
        $input = trim( fgets( STDIN ) );

        // If options are provided and the choice is not in the array, tell them to try again
        if ( ! empty( $options ) && ! in_array( $input, $options ) )
        {
            $this->write( 'This is not a valid option. Please try again.' );

            $input = $this->read( $output, $options );
        }

        return $input;
    }

    /**
     * Copied verbatim from http://styledna.pastebin.com/f69855cc9
     * @author  Philip Sturgeon
     * @created 7 Oct 2008
     */
    public function write( $output = '' )
    {
        // If $output is an array, implode them into multiple lines
        if ( is_array( $output ) )
            $output = implode( PHP_EOL, $output );

        fwrite( STDOUT, $output . PHP_EOL );
    }

    /**
     * Copied verbatim from http://styledna.pastebin.com/f69855cc9
     * @author  Philip Sturgeon
     * @created 7 Oct 2008
     */
    function wait( $seconds = 0, $countdown = FALSE )
    {

        // Diplay the countdown
        if ( $countdown == TRUE )
        {
            $i = $seconds;
            while ( $i > 0 )
            {
               fwrite( STDOUT, $i.'... ' );
               sleep( 1 );
               $i--;
            }
        }
        // No countdown timer please
        else
        {
            // Set number of seconds?
            if( $seconds > 0 )
            {
                sleep( $seconds );
            }
            // No seconds mentioned, lets wait for user input
            else
            {
                $this->write( $this->wait_msg );
                $this->read( );
            }
        }

        return TRUE;
    }

}