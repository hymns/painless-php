<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
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
