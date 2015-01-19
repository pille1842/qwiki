<?php
/**
 * This file contains the page-title search tool for Qwiki pages
 * @package Qwiki
 * @copyright 2015 Eric Haberstroh
 * @author Eric Haberstroh <eric@erixpage.de>
 * @version 0.3
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
include('./includes/application_top.php');

$term = $_GET['term'];
if ($term == "") {
    $term = $_POST['term'];
}
$term = strtolower($term);
$index = array();
$total = 0;
$dirh = opendir($config['pages_dir']);
while (($fname = readdir($dirh)) !== false) {
    if (($fname != '.') && ($fname != '..') && (substr($fname, -4, 4) == '.txt')) {
        $low = strtolower($fname);
        if (strpos($low, $term) !== false || $term == "") {
            $index[] = substr($fname, 0, -4);
        }
        $total += 1;
    }
}
closedir($dirh);
sort($index);
$page = q_('search_results');
include('./includes/templates/search.php');
?>
