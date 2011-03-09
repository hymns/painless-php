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

class Sanitizer
{
    /* never allowed, string replacement */

    protected $neverAllowedStr = array( 'document.cookie' => '[removed]',
        'document.write' => '[removed]',
        '.parentNode' => '[removed]',
        '.innerHTML' => '[removed]',
        'window.location' => '[removed]',
        '-moz-binding' => '[removed]',
        '<!--' => '&lt;!--',
        '-->' => '--&gt;',
        '<![CDATA[' => '&lt;![CDATA['
    );

    /* never allowed, regex replacement */
    protected $neverAllowedRegex = array( "javascript\s*:" => '[removed]',
        "expression\s*\(" => '[removed]', // CSS and IE
        "Redirect\s+302" => '[removed]'
    );

    protected $xssHash;

    public function validURI( $uriArray )
    {
        return $uriArray;
    }

    /* This function takes a string with a number (either an integer, or a float/double) and returns the same
       thing if it's valid, or '' if it's not. */
    public function cleanNumerics( $string )
    {
        if (is_numeric($string))
        {
            return (int)$string;
        }
        else
        {
            return '';
        }
    }

    public function cleanCurrency( $string )
    {
        $n = preg_replace( '/[^\d.]+/', '', $string );
        return sprintf( '%01.2f', $n );
    }

    public function cleanAlphaNumerics( $string )
    {
        $string = trim( $string );

        if ( ctype_digit( $string ) )
        {
            return $string;
        }
        else
        {
            // replace accented chars
            $accents = '/&([A-Za-z]{1,2})(grave|acute|circ|cedil|uml|lig);/';
            $string_encoded = htmlentities( $string, ENT_NOQUOTES, 'UTF-8' );

            $string = preg_replace( $accents, '$1', $string_encoded );

            // clean out the rest
            $replace = array( '([\40])', '([^a-zA-Z0-9-])', '(-{2,})' );
            $with = array( '-', '', '-' );
            $string = preg_replace( $replace, $with, $string );
        }

        return $string;
    }

    public function secureFilename( $filename )
    {
        $bad = array( "../",
            "./",
            "<!--",
            "-->",
            "<",
            ">",
            "'",
            '"',
            '&',
            '$',
            '#',
            '{',
            '}',
            '[',
            ']',
            '=',
            ';',
            '?',
            "%20",
            "%22",
            "%3c", // <
            "%253c", // <
            "%3e", // >
            "%0e", // >
            "%28", // (
            "%29", // )
            "%2528", // (
            "%26", // &
            "%24", // $
            "%3f", // ?
            "%3b", // ;
            "%3d"  // =
        );

        return stripslashes( str_replace( $bad, '', $str ) );
    }

    // --------------------------------------------------------------------
    /**
     * Validate IP v4 Address
     *
     * Updated version suggested by Geert De Deckere
     *
     * @access	public
     * @param	string
     * @return	string
     */
    public function validIPv4( $ip )
    {
        $ip_segments = explode( '.', $ip );

        // Always 4 segments needed
        if ( count( $ip_segments ) != 4 )
        {
            return FALSE;
        }

        // IP can not start with 0
        if ( substr( $ip_segments[0], 0, 1 ) == '0' )
        {
            return FALSE;
        }

        // Check each segment
        foreach ( $ip_segments as $segment )
        {
            // IP segments must be digits and can not be
            // longer than 3 digits or greater then 255
            if ( preg_match( "/[^0-9]/", $segment ) || $segment > 255 || strlen( $segment ) > 3 )
            {
                return FALSE;
            }
        }

        return TRUE;
    }

    //---------------------------------------------------------------
    /**
     * sqlParamClean
     *
     * clean sql parameter
     *
     * @scope public
     * @param	string		text
     * @return	string		text after cleaning
     */
    public function sqlParamClean( $str )
    {
        return addslashes( $str );
        //return mysql_real_escape_string( $str );
    }

    /**
     * Function: sanitize
     * Returns a sanitized string, typically for URLs.
     *
     * Parameters:
     *     $string - The string to sanitize.
     *     $force_lowercase - Force the string to lowercase?
     *     $anal - If set to *true*, will remove all non-alphanumeric characters.
     */
    public function cleanUrl( $string, $force_lowercase = false, $anal = false )
    {
        $strip = array( "~", "`", "@", "#", "$", "%", "^", "&", "*",
                        "(", ")", "_", "=", "+", "[", "{", "]", "}", "\\",
                        "|", ";", ":", "\"", "'", "&#8216;", "&#8217;",
                        "&#8220;", "&#8221;", "&#8211;", "&#8212;", "â€”",
                        "â€“", ",", "<", ">" );
        $clean = trim( str_replace( $strip, "", strip_tags( $string ) ) );
        $clean = preg_replace( '/\s+/', "-", $clean );
        $clean = ( $anal ) ? preg_replace( "/[^a-zA-Z0-9]/", "", $clean ) : $clean ;
        return ( $force_lowercase ) ?
            ( function_exists( 'mb_strtolower' ) ) ? mb_strtolower( $clean, 'UTF-8' ) :
            strtolower( $clean ) : $clean;
    }

    // --------------------------------------------------------------------
    /**
     * XSS Clean
     *
     * Sanitizes data so that Cross Site Scripting Hacks can be
     * prevented.� This function does a fair amount of work but
     * it is extremely thorough, designed to prevent even the
     * most obscure XSS attempts.� Nothing is ever 100% foolproof,
     * of course, but I haven't been able to get anything passed
     * the filter.
     *
     * Note: This function should only be used to deal with data
     * upon submission.� It's not something that should
     * be used for general runtime processing.
     *
     * This function was based in part on some code and ideas I
     * got from Bitflux: http://blog.bitflux.ch/wiki/XSS_Prevention
     *
     * To help develop this script I used this great list of
     * vulnerabilities along with a few other hacks I've
     * harvested from examining vulnerabilities in other programs:
     * http://ha.ckers.org/xss.html
     *
     * @access	public
     * @param	string
     * @return	string
     */
    public function xssClean( $str, $isImage = FALSE )
    {
        $engine = Painless::get( 'system/common/core' );

        // Is the string an array?
        if ( is_array( $str ) )
        {
            while ( list( $key ) = each( $str ) )
            {
                $str[$key] = $this->xssClean( $str[$key] );
            }

            return $str;
        }

        // Remove Invisible Characters
        $str = $this->removeInvisibleCharacters( $str );

/* 
         * Protect GET variables in URLs
 */

        // 901119URL5918AMP18930PROTECT8198

        $str = preg_replace( '|\&([a-z\_0-9]+)\=([a-z\_0-9]+)|i', $this->xssHash() . "\\1=\\2", $str );

        /*
         * Validate standard character entities
         *
         * Add a semicolon if missing.  We do this to enable
         * the conversion of entities to ASCII later.
         *
         */
        $str = preg_replace( '#(&\#?[0-9a-z]+)[\x00-\x20]*;?#i', "\\1;", $str );

        /*
         * Validate UTF16 two byte encoding (x00)
         *
         * Just as above, adds a semicolon if missing.
         *
         */
        $str = preg_replace( '#(&\#x?)([0-9A-F]+);?#i', "\\1\\2;", $str );

        // Un-Protect GET variables in URLs
        $str = str_replace( $this->xssHash(), '&', $str );

        /*
         * URL Decode
         *
         * Just in case stuff like this is submitted:
         *
         * <a href="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">Google</a>
         *
         * Note: Use rawurldecode() so it does not remove plus signs
         *
         */
        $str = rawurldecode( $str );

        /*
         * Convert character entities to ASCII
         *
         * This permits our tests below to work reliably.
         * We only convert entities that are within tags since
         * these are the ones that will pose security problems.
         *
         */
        $str = preg_replace_callback( "/[a-z]+=([\'\"]).*?\\1/si", array( $this, 'convertAttribute' ), $str );
        $str = preg_replace_callback( "/<\w+.*?(?=>|<|$)/si", array( $this, 'htmlEntityDecodeCallback' ), $str );

        // Remove Invisible Characters Again!
        $str = $this->removeInvisibleCharacters( $str );

        /*
         * Convert all tabs to spaces
         *
         * This prevents strings like this: ja	vascript
         * NOTE: we deal with spaces between characters later.
         * NOTE: preg_replace was found to be amazingly slow here on large blocks of data,
         * so we use str_replace.
         *
         */
        if ( strpos( $str, "\t" ) !== FALSE )
        {
            $str = str_replace( "\t", ' ', $str );
        }

        /*
         * Capture converted string for later comparison
         */
        $converted_string = $str;

        /*
         * Not Allowed Under Any Conditions
         */

        foreach ( $this->neverAllowedStr as $key => $val )
        {
            $str = str_replace( $key, $val, $str );
        }

        foreach ( $this->neverAllowedRegex as $key => $val )
        {
            $str = preg_replace( "#" . $key . "#i", $val, $str );
        }

        /*
         * Makes PHP tags safe
         *
         *  Note: XML tags are inadvertently replaced too:
         *
         * 	<?xml
         *
         * But it doesn't seem to pose a problem.
         *
         */
        if ( $isImage === TRUE )
        {
            // Images have a tendency to have the PHP short opening and closing tags every so often
            // so we skip those and only do the long opening tags.
            $str = str_replace( array( '<?php', '<?PHP' ), array( '&lt;?php', '&lt;?PHP' ), $str );
        }
        else
        {
            $str = str_replace( array( '<?php', '<?PHP', '<?', '?' . '>' ), array( '&lt;?php', '&lt;?PHP', '&lt;?', '?&gt;' ), $str );
        }

        /*
         * Compact any exploded words
         *
         * This corrects words like:  j a v a s c r i p t
         * These words are compacted back to their correct state.
         *
         */
        $words = array( 'javascript', 'expression', 'vbscript', 'script', 'applet', 'alert', 'document', 'write', 'cookie', 'window' );
        foreach ( $words as $word )
        {
            $temp = '';

            for ( $i = 0, $wordlen = strlen( $word ); $i < $wordlen; $i++ )
            {
                $temp .= substr( $word, $i, 1 ) . "\s*";
            }

            // We only want to do this when it is followed by a non-word character
            // That way valid stuff like "dealer to" does not become "dealerto"
            $str = preg_replace_callback( '#(' . substr( $temp, 0, -3 ) . ')(\W)#is', array( $this, 'compactExplodedWords' ), $str );
        }

        /*
         * Remove disallowed Javascript in links or img tags
         * We used to do some version comparisons and use of stripos for PHP5, but it is dog slow compared
         * to these simplified non-capturing preg_match(), especially if the pattern exists in the string
         */
        do
        {
            $original = $str;

            if ( preg_match( "/<a/i", $str ) )
            {
                $str = preg_replace_callback( "#<a\s*([^>]*?)(>|$)#si", array( $this, 'jsLinkRemoval' ), $str );
            }

            if ( preg_match( "/<img/i", $str ) )
            {
                $str = preg_replace_callback( "#<img\s*([^>]*?)(>|$)#si", array( $this, 'jsImgRemoval' ), $str );
            }

            if ( preg_match( "/script/i", $str ) || preg_match( "/xss/i", $str ) )
            {
                $str = preg_replace( "#<(/*)(script|xss)(.*?)\>#si", '[removed]', $str );
            }
        }
        while ( $original != $str );

        unset( $original );

        /*
         * Remove JavaScript Event Handlers
         *
         * Note: This code is a little blunt.  It removes
         * the event handler and anything up to the closing >,
         * but it's unlikely to be a problem.
         *
         */
        $event_handlers = array( 'on\w*', 'xmlns' );

        if ( $isImage === TRUE )
        {
            /*
             * Adobe Photoshop puts XML metadata into JFIF images, including namespacing,
             * so we have to allow this for images. -Paul
             */
            unset( $event_handlers[array_search( 'xmlns', $event_handlers )] );
        }

        $str = preg_replace( "#<([^><]+)(" . implode( '|', $event_handlers ) . ")(\s*=\s*[^><]*)([><]*)#i", "<\\1\\4", $str );

        /*
         * Sanitize naughty HTML elements
         *
         * If a tag containing any of the words in the list
         * below is found, the tag gets converted to entities.
         *
         * So this: <blink>
         * Becomes: &lt;blink&gt;
         *
         */
        $naughty = 'alert|applet|audio|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|layer|link|meta|object|plaintext|style|script|textarea|title|video|xml|xss';
        $str = preg_replace_callback( '#<(/*\s*)(' . $naughty . ')([^><]*)([><]*)#is', array( $this, 'sanitizeNaughtyHtml' ), $str );

        /*
         * Sanitize naughty scripting elements
         *
         * Similar to above, only instead of looking for
         * tags it looks for PHP and JavaScript commands
         * that are disallowed.  Rather than removing the
         * code, it simply converts the parenthesis to entities
         * rendering the code un-executable.
         *
         * For example:	eval( 'some code' )
         * Becomes:		eval&#40;'some code'&#41;
         *
         */
        $str = preg_replace( '#(alert|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si', "\\1\\2&#40;\\3&#41;", $str );

        /*
         * Final clean up
         *
         * This adds a bit of extra precaution in case
         * something got through the above filters
         *
         */
        foreach ( $this->neverAllowedStr as $key => $val )
        {
            $str = str_replace( $key, $val, $str );
        }

        foreach ( $this->neverAllowedRegex as $key => $val )
        {
            $str = preg_replace( "#" . $key . "#i", $val, $str );
        }

        /*
         *  Images are Handled in a Special Way
         *  - Essentially, we want to know that after all of the character conversion is done whether
         *  any unwanted, likely XSS, code was found.  If not, we return TRUE, as the image is clean.
         *  However, if the string post-conversion does not matched the string post-removal of XSS,
         *  then it fails, as there was unwanted XSS code found and removed/changed during processing.
         */
        if ( $isImage === TRUE )
        {
            if ( $str == $converted_string )
            {
                return TRUE;
            }
            else
            {
                return FALSE;
            }
        }

        //$engine->info( "XSS Filtering completed" );
        return $str;
    }

    // --------------------------------------------------------------------
    /**
     * Random Hash for protecting URLs
     *
     * @access	public
     * @return	string
     */
    public function xssHash()
    {
        if ( $this->xssHash == '' )
        {
            mt_srand( );

            $this->xssHash = md5( time( ) + mt_rand( 0, 1999999999 ) );
        }

        return $this->xssHash;
    }

    // --------------------------------------------------------------------
    /**
     * Remove Invisible Characters
     *
     * This prevents sandwiching null characters
     * between ascii characters, like Java\0script.
     *
     * @access	public
     * @param	string
     * @return	string
     */
    public function removeInvisibleCharacters( $str )
    {
        static $non_displayables;

        if ( !isset( $non_displayables ) )
        {
            // every control character except newline (10), carriage return (13), and horizontal tab (09),
            // both as a URL encoded character (::shakes fist at IE and WebKit::), and the actual character
            $non_displayables = array( '/%0[0-8]/', '/[\x00-\x08]/', // 00-08
                '/%11/', '/\x0b/', '/%12/', '/\x0c/', // 11, 12
                '/%1[4-9]/', '/%2[0-9]/', '/%3[0-1]/', // url encoded 14-31
                '/[\x0e-\x1f]/' );      // 14-31
        }

        do
        {
            $cleaned = $str;
            $str = preg_replace( $non_displayables, '', $str );
        }
        while ( $cleaned != $str );

        return $str;
    }

    // --------------------------------------------------------------------
    /**
     * Compact Exploded Words
     *
     * Callback function for xssClean() to remove whitespace from
     * things like j a v a s c r i p t
     *
     * @access	protected
     * @param	type
     * @return	type
     */
    protected function compactExplodedWords( $matches )
    {
        return preg_replace( '/\s+/s', '', $matches[1] ) . $matches[2];
    }

    // --------------------------------------------------------------------
    /**
     * Sanitize Naughty HTML
     *
     * Callback function for xssClean() to remove naughty HTML elements
     *
     * @access	protected
     * @param	array
     * @return	string
     */
    protected function sanitizeNaughtyHtml( $matches )
    {
        // encode opening brace
        $str = '&lt;' . $matches[1] . $matches[2] . $matches[3];

        // encode captured opening or closing brace to prevent recursive vectors
        $str .= str_replace( array( '>', '<' ), array( '&gt;', '&lt;' ), $matches[4] );

        return $str;
    }

    // --------------------------------------------------------------------
    /**
     * JS Link Removal
     *
     * Callback function for xssClean() to sanitize links
     * This limits the PCRE backtracks, making it more performance friendly
     * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
     * PHP 5.2+ on link-heavy strings
     *
     * @access	protected
     * @param	array
     * @return	string
     */
    protected function jsLinkRemoval( $match )
    {
        $attributes = $this->filterAttributes( str_replace( array( '<', '>' ), '', $match[1] ) );
        return str_replace( $match[1],
                preg_replace( "#href=.*?(alert\(|alert&\#40;|javascript\:|charset\=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si", "", $attributes ),
                $match[0] );
    }

    // --------------------------------------------------------------------
    /**
     * JS Image Removal
     *
     * Callback function for xssClean() to sanitize image tags
     * This limits the PCRE backtracks, making it more performance friendly
     * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
     * PHP 5.2+ on image tag heavy strings
     *
     * @access	private
     * @param	array
     * @return	string
     */
    protected function jsImgRemoval( $match )
    {
        $attributes = $this->filterAttributes( str_replace( array( '<', '>' ), '', $match[1] ) );

        return str_replace( $match[1],
                preg_replace( "#src=.*?(alert\(|alert&\#40;|javascript\:|charset\=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si", "", $attributes ),
                $match[0] );
    }

    // --------------------------------------------------------------------
    /**
     * Attribute Conversion
     *
     * Used as a callback for XSS Clean
     *
     * @access	protected
     * @param	array
     * @return	string
     */
    protected function convertAttribute( $match )
    {
        return str_replace( array( '>', '<' ), array( '&gt;', '&lt;' ), $match[0] );
    }

    // --------------------------------------------------------------------
    /**
     * HTML Entity Decode Callback
     *
     * Used as a callback for XSS Clean
     *
     * @access	protected
     * @param	array
     * @return	string
     */
    protected function htmlEntityDecodeCallback( $match )
    {
        return $this->htmlEntityDecode( $match[0] );
    }

    // --------------------------------------------------------------------
    /**
     * HTML Entities Decode
     *
     * This function is a replacement for html_entity_decode()
     *
     * In some versions of PHP the native function does not work
     * when UTF-8 is the specified character set, so this gives us
     * a work-around.  More info here:
     * http://bugs.php.net/bug.php?id=25670
     *
     * @access	protected
     * @param	string
     * @param	string
     * @return	string
     */
    /* -------------------------------------------------
      /*  Replacement for html_entity_decode()
      /* ------------------------------------------------- */
    /*
      NOTE: html_entity_decode() has a bug in some PHP versions when UTF-8 is the
      character set, and the PHP developers said they were not back porting the
      fix to versions other than PHP 5.x.
     */
    protected function htmlEntityDecode( $str, $charset='UTF-8' )
    {
        if ( stristr( $str, '&' ) === FALSE )
            return $str;

        // The reason we are not using html_entity_decode() by itself is because
        // while it is not technically correct to leave out the semicolon
        // at the end of an entity most browsers will still interpret the entity
        // correctly.  html_entity_decode() does not convert entities without
        // semicolons, so we are left with our own little solution here. Bummer.

        if ( function_exists( 'html_entity_decode' ) && ( strtolower( $charset ) != 'utf-8' || version_compare( phpversion(), '5.0.0', '>=' ) ) )
        {
            $str = html_entity_decode( $str, ENT_COMPAT, $charset );
            $str = preg_replace( '~&#x(0*[0-9a-f]{2,5})~ei', 'chr(hexdec("\\1"))', $str );
            return preg_replace( '~&#([0-9]{2,4})~e', 'chr(\\1)', $str );
        }

        // Numeric Entities
        $str = preg_replace( '~&#x(0*[0-9a-f]{2,5});{0,1}~ei', 'chr(hexdec("\\1"))', $str );
        $str = preg_replace( '~&#([0-9]{2,4});{0,1}~e', 'chr(\\1)', $str );

        // Literal Entities - Slightly slow so we do another check
        if ( stristr( $str, '&' ) === FALSE )
        {
            $str = strtr( $str, array_flip( get_html_translation_table( HTML_ENTITIES ) ) );
        }

        return $str;
    }

    // --------------------------------------------------------------------
    /**
     * Filter Attributes
     *
     * Filters tag attributes for consistency and safety
     *
     * @access	protected
     * @param	string
     * @return	string
     */
    protected function filterAttributes( $str )
    {
        $out = '';

        if ( preg_match_all( '#\s*[a-z\-]+\s*=\s*(\042|\047)([^\\1]*?)\\1#is', $str, $matches ) )
        {
            foreach ( $matches[0] as $match )
            {
                $out .= "{$match}";
            }
        }

        return $out;
    }
}
