<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Painless\System\View\Compiler;

class Raw extends \Painless\System\View\Compiler\Base
{
    public function process( $view )
    {
        return $view->response;
    }
}