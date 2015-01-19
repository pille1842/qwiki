<?php
/**
 * This file contains the full-text search tool for Qwiki pages
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
    if ($term == "") {
        $term = $config['frontpage'];
    }
}
$index = array();
$db = new SQLite3($config['fullindex_file']);
if (!$db) {
    $error = q_('error_opening_index_db');
}
$result = $db->query("SELECT pagename, snippet(qwiki_index) AS sn FROM qwiki_index WHERE content MATCH '".$db->escapeString($term)."'");
if (!$result) {
    $error = q_('error_searching_index');
} else {
    while ($row = $result->fetchArray()) {
        $index[$row['pagename']] = $row['sn'];
    }
}
$page = q_('search_results');
include('./includes/templates/fullsearch.php');
?>
