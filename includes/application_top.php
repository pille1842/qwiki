<?php
/**
 * This file prepares Qwiki for runtime
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

if (!file_exists('./includes/configuration.php')) {
    die('No configuration file found. Please follow the instructions in README.md for setting up Qwiki.');
}

require_once('./includes/configuration.php');
require_once('./includes/Diff.class.php');
require_once('./includes/WikiParser.class.php');
require_once('./includes/smarty/Smarty.class.php');
require_once('./includes/Qwiki.class.php');

setlocale(LC_ALL, QWIKI_LOCALE);

class QwikiSmarty extends Smarty {
    public $template_dir = QWIKI_DIR_TEMPLATE;
    public $compile_dir = QWIKI_DIR_COMPILE;
    public $config_dir = QWIKI_DIR_CONFIG;
    public $cache_dir = QWIKI_DIR_CACHE;
}

function printvar($r) {
   echo '<pre>';
   print_r($r);
   echo '</pre>';
}
?>