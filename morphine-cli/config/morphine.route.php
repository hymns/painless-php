<?php
/**
 * Morphine - the command line toolkit for Painless PHP to take away the pain
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
 * @package     Morphine
 * @author      Tan Long Zheng (soggie) <ruben@rendervault.com>
 * @copyright   2011 Tan Long Zheng (soggie) <ruben@rendervault.com>
 * @license     BSD 3 Clause (New BSD)
 * @link        http://painless-php.com
 */

/**
 * The config option routes.uri-init tells the param parser which token is the
 * module and which one is the workflow.
 *
 * module       - the module namespace
 * workflow     - the workflow namespace
 * param        - a parameter segment
 * param-all    - parse the rest of the URI string as a param array
 */

/**
 * routes.uri.default.module    - the default module to load if none is specified (no URI string)
 * routes.uri.default.workflow  - the default workflow to load if none is specified (no URI string)
 */
$config['routes.uri.default.module']    = 'help';
$config['routes.uri.default.workflow']  = 'main';

/**
 * routes.uri.config            - how to parse the original URI string. This array will determine which
 *                                token refers to what.
 *
 *                                currently, 4 kinds of tokens are supported:
 *                                  * module    - maps to a module
 *                                  * workflow  - maps to a workflow in a module
 *                                  * param     - designates a single parameter token (which will be
 *                                                amended to the final param array
 *                                  * alias     - maps to an alias in routes.alias. WARNING: YOU CAN'T
 *                                                USE module AND workflow IF USING alias! An exception
 *                                                will be thrown if that is the case
 *
 *                                Example: http://foo.com/bar/so/fine/now
 *                                         1. array( 'module', 'workflow' );
 *                                              - module = bar
 *                                              - workflow = so
 *                                              - params = array( 'fine', 'now' )
 *                                         2. array( 'param', 'module', 'workflow' );
 *                                              - module = so
 *                                              - workflow = fine
 *                                              - params = array( 'bar', 'now' )
 *                                         3. array( 'param', 'alias' )
 *                                              - alias = so
 *                                              - params = array( 'bar', 'fine', 'now' )
 */
$config['routes.uri.config']            = array( 'alias' );

$config['routes.alias']['execute']      = array( 'execute', 'main' );
$config['routes.alias']['config']       = array( 'config', 'main' );
$config['routes.alias']['generate']     = array( 'generate', 'main' );
$config['routes.alias']['install']      = array( 'install', 'main' );
$config['routes.alias']['update']       = array( 'update', 'main' );
$config['routes.alias']['test']         = array( 'test', 'main' );
$config['routes.alias']['help']         = array( 'help', 'main' );
$config['routes.alias']['*']            = array( 'help', 'main' );