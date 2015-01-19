<?php
/**
 * This file contains the full-text index updating tool for Qwiki
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
require_once('./includes/application_top.php');
$DBFILE = $config['fullindex_file'];
echo "BEGINNING OPERATION ".date("Y-m-d H:i:s")."\n";
if (file_exists($DBFILE) && !is_writable($DBFILE)) {
    die(q_('dbfile_write_error')."\n");
} elseif (!file_exists($DBFILE)) {
    touch($DBFILE);
    echo q_('dbfile_info_created')."\n";
}

// prepare database, create table if necessary
$db = new SQLite3($DBFILE);
if (!$db) {
    die(q_('dbfile_error_handle')."\n");
}
$result = $db->exec("CREATE VIRTUAL TABLE IF NOT EXISTS qwiki_index USING fts3(pagename VARCHAR(256) NOT NULL, content TEXT, modified_at DATETIME)");
if (!$result) {
    die(q_('dbfile_error_qwiki_table')."\n");
}
// Read list of pages with modification time from database
$index = array();
$result = $db->query('SELECT pagename, modified_at FROM qwiki_index');
if (!$result) {
    die(q_('dbfile_read_error')."\n");
}
while ($row = $result->fetchArray()) {
    $index[$row['pagename']] = new DateTime($row['modified_at']);
}
$dirh = opendir($config['pages_dir']);
if (!$dirh) {
    die(q_('dbfile_pages_dir_error')."\n");
}
while (($fname = readdir($dirh)) !== false) {
    if (($fname != '.') && ($fname != '..') && (substr($fname, -4, 4) == '.txt')) {
        if (!isset($index[substr($fname, 0, -4)])) {
            // entry nonexistent in index
            $db->exec("INSERT INTO qwiki_index (pagename, content, modified_at) VALUES ('".substr($fname, 0, -4)."', '".$db->escapeString(file_get_contents($config['pages_dir'].$fname))."', '".date("Y-m-d H:i:s", filemtime($config['pages_dir'].$fname))."')");
            echo "INSERT ".substr($fname, 0, -4)."\n";
        } elseif (new DateTime(date("Y-m-d H:i:s", filemtime($config['pages_dir'].$fname))) > $index[substr($fname, 0, -4)]) {
            // Index needs to be refreshed
            $db->exec("UPDATE qwiki_index SET content = '".$db->escapeString(file_get_contents($config['pages_dir'].$fname))."', modified_at = '".date("Y-m-d H:i:s", filemtime($config['pages_dir'].$fname))."' WHERE pagename = '".substr($fname, 0, -4)."'");
            echo "UPDATE ".substr($fname, 0, -4)."\n";
        } else {
            echo "IGNORE ".substr($fname, 0, -4)."\n";
        }
    }
}
closedir($dirh);
$db->close();
echo "OPERATION COMPLETE ".date("Y-m-d H:i:s")."\n\n\n";
?>
