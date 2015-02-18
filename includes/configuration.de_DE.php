<?php
/**
 * This file contains configuration values for this Qwiki installation
 * @package Qwiki
 * @copyright 2015 Eric Haberstroh
 * @author Eric Haberstroh <eric@erixpage.de>
 * @version 2.0
 */
/*  This file is part of Qwiki by Eric Haberstroh <eric@erixpage.de>.
    
    Qwiki is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if (!defined('QWIKI_EXEC')) {
    die('Restricted');
}

/**
 * Document root for use in URLs (with full or relative domain)
 */
define('QWIKI_DOCROOT', '/');
/**
 * Document root for use by scripts
 */
define('QWIKI_HTDOCS', '/var/www/');
/**
 * Name of the FrontPage (see pages_dist directory for your locale)
 */
define('QWIKI_FRONTPAGE', 'StartSeite');
/**
 * Error page (for 404 errors, see pages_dist directory for your locale)
 */
define('QWIKI_ERRORPAGE', 'SeiteNichtGefunden');
/**
 * RecentChanges page for the edit log
 */
define('QWIKI_RECENTCHANGES', 'LetzteAenderungen');
/**
 * FindPage name (page that contains the search form)
 */
define('QWIKI_FINDPAGE', 'SeiteFinden');
/**
 * Locale of this installation (e.g. en_US or de_DE)
 */
define('QWIKI_LOCALE', 'de_DE');
/**
 * Path to logo image (displayed in upper left corner)
 */
define('QWIKI_LOGO', QWIKI_DOCROOT.'images/logo.png');
/**
 * Directory that contains pages files (has to be writable by webserver)
 */
define('QWIKI_DIR_PAGES', QWIKI_DOCROOT.'pages/');
/**
 * Path to the index SQLite database file (has to be writable by webserver)
 */
define('QWIKI_INDEX_FILE', QWIKI_DIR_PAGES.'fullindex.sqlite');
/**
 * Directory for templates
 */
define('QWIKI_DIR_TEMPLATE', QWIKI_HTDOCS.'templates/');
/**
 * Compile directory for Smarty (has to be writable by webserver)
 */
define('QWIKI_DIR_COMPILE', QWIKI_HTDOCS.'templates_c/');
/**
 * Directory that contains config files (translations) for Smarty
 */
define('QWIKI_DIR_CONFIG', QWIKI_HTDOCS.'configs/');
/**
 * Cache directory for Smarty (has to be writable by webserver)
 */
define('QWIKI_DIR_CACHE', QWIKI_HTDOCS.'cache/');
/**
 * Name of the cookie that will contain the username if set
 */
define('QWIKI_COOKIE_NAME', 'qwiki_username');
/**
 * Time for the username cookie to expire (in seconds)
 */
define('QWIKI_COOKIE_EXPIRE', 60*60*24*365); // 365 days
/**
 * Site for searching for ISBN numbers. Must contain $1 (which is the extracted ISBN).
 */
define('QWIKI_ISBN_SEARCH', 'http://www.amazon.de/gp/search/?field-isbn=$1');
?>