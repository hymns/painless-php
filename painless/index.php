<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

$string = 'system/common/cli-console';
echo preg_replace( '/(\/[a-z])|(\-[a-z])/', "\\", $string );