<?php

/**
 * TODO: Rename this file and remove the .sample extension
 */
// DB configurations
$config['mysql.host']                           = 'DATABASE URL';
$config['mysql.database']                       = 'DATABASE NAME';
$config['mysql.username']                       = 'DATABASE USERNAME';
$config['mysql.password']                       = 'PASSWORD';

// Alternate DB configuration styles - using database profiles
/* <-- remove this to enable multiple profile support
$config['mysql.profiles']                       = array( 'development', 'testing' );

$config['mysql.development.host']               = 'DATABASE URL';
$config['mysql.development.database']           = 'DATABASE NAME';
$config['mysql.development.username']           = 'DATABASE USERNAME';
$config['mysql.development.password']           = 'PASSWORD';

$config['mysql.testing.host']                   = 'DATABASE URL';
$config['mysql.testing.database']               = 'DATABASE NAME';
$config['mysql.testing.username']               = 'DATABASE USERNAME';
$config['mysql.testing.password']               = 'PASSWORD';
remove this to enable multiple profile support --> */
// FTP configurations
$config['ftp.host']                             = 'edgeyo.com';
$config['ftp.protocol']                         = 'ftp';
$config['ftp.username']                         = 'edgeyo';
$config['ftp.password']                         = '123456';

// Cookie configurations
$config['cookie.domain']                        = FALSE;
$config['cookie.path']                          = '/';          // set to '/' to allow it to all paths
$config['cookie.httpOnly']                      = TRUE;
$config['cookie.expire']                        = 1209600;      // expires in 2 weeks
$config['cookie.useHttps']                      = FALSE;

// Session cookie configurations
$config['session.id.hash_algo']                 = 'whirlpool';
$config['session.name']                         = 'SESSID';     // by default it's "PHPSESSID"
$config['session.cookie_path']                  = '/';
$config['session.cookie_lifetime']              = 3600;         // 1 hour
$config['session.cookie_domain']                = FALSE;
$config['session.cache_limiter']                = 'private_no_expire';
$config['session.cache_expire']                 = 180;          // # of seconds cached session pages are made available before new pages are created
$config['session.gc_maxlifetime']               = 1440;         // duration, in seconds for which session data is considered valid
$config['session.namespace']                    = 'edgeyo';

$config['system.router.default.module']         = 'public';
$config['system.router.default.workflow']       = 'frontpage';
$config['system.router.default.step']           = 'index';

// E-mail configuration
$config['email.host']                           = 'smtp.webfaction.com';
$config['email.port']                           = '587';
$config['email.timeout']                        = '30';
$config['email.content_type']                   = 'text/plain';
$config['email.charset']                        = 'iso-8859-1';
$config['email.content_transfer_encoding']      = '8bit';

/// identity #1 name=ac_noreply
$config['email.default.from_name']              = 'Edgeyo Accounts'; // the name you want to appear in the "From".
$config['email.default.from_address']           = 'accounts-noreply@edgeyo.com'; // the e-mail address to appear in the "From"
$config['email.default.username']               = 'edgeyo_operations'; // need to go to webfaction control panel mailbox, and create a new account.
$config['email.default.password']               = 'D36q1BI'; // need to go to webfaction control panel mailbox, and create a new account.