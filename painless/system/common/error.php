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

class PainlessError
{
    public function handleError( $errNo, $errStr, $errFile, $errLine )
    {
        // load the renderer
        $render = Painless::get( 'system/common/render' );

        $errStr = <<<ERROR
[$errNo] $errStr<br />
&nbsp;&nbsp;&nbsp;&nbsp;- File: $errFile (Line $errLine)<br />
ERROR;

        $traceOutput = <<<HTML
<div id="trace">
    <table cellspacing="0" cellpadding="0">
        <thead>
            <tr>
                <th>Method</th>
                <th>File</th>
                <th>Line</th>
            </tr>
        </thead>
        <tbody>
HTML;
        $trace = debug_backtrace( );
        foreach( $trace as $element )
        {
            $object = get_class( $element['object'] );

            $traceOutput .= '<tr>';
            $traceOutput .= '<td>' . $object . '::' . $element['function'] . '( )</td>';
            $traceOutput .= '<td>' . $element['file'] . '</td>';
            $traceOutput .= '<td>' . $element['line'] . '</td>';
            $traceOutput .= '</tr>';
        }
        $traceOutput .= '</tbody></table></div>';

        //$vars = print_r( get_defined_vars( ), TRUE );

        $output = <<<HTML
<!doctype html>
<html>
    <head>
        <title>Painless Error Debug</title>
        <style>
            body {
                margin: 0;
                padding: 1em;
                font-family: Verdana, Helvetica, san-serif;
                font-size: 9pt;
                line-height: 2em;
                color: #444;
            }

            h1 {
                font-size: 1.4em;
                color: #333;
            }
            table { font-family: Courier New, system; border: 0px solid white; margin: 0; padding: 0; border-top: 1px dashed #555; border-left: 1px dashed #555; }
            table td, table th { margin: 0; padding: 1em; font-size: 0.8em; border-bottom: 1px dashed #555; border-right: 1px dashed #555; }
            table th { background-color: #efefef; }
            table td { background-color: #fafafa; }
        </style>
    </head>
    <body>
        <h1>Error</h1>
        $errStr
        <hr /><br /><br />
        <h1>Debug Trace</h1>
        $traceOutput
        <hr />
    </body>
</html>
HTML;

        ob_start( );
        echo $output;
        ob_end_flush( );
        die;
    }

    public function handleException( $exception )
    {
        // load the renderer
        $render = Painless::get( 'system/common/render' );

        var_dump( $exception ); die;
    }

    protected function generateRequest( )
    {
        return array(
            'params' => array( ),
            'agent' => '',
            'type' => '',
        );
    }

    protected function generateResponse( )
    {
        return array(
            'code' => 500,
            'message' => 'Fatal system error',
            'payload' => array( )
        );
    }
}
