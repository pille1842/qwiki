<?php
/**
 * This file contains the "EditPage" tool for Qwiki pages
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
if (isset($_POST['preview']) && $_POST['preview'] != '') {
    // Vorschau anzeigen
    $preview = parse_wikitext($_POST['edittext']);
}

if (isset($_POST['save']) && $_POST['save'] != '' && preg_match('/([A-Z][a-z]+){2,}/', $_POST['page'])) {
    // Seite speichern
    $filename = $config['pages_dir'].$_POST['page'].'.txt';
    if (file_exists($filename)) {
        $sf = fopen($filename.'~', 'w');
        if ($sf) {
            fwrite($sf, file_get_contents($filename));
            fclose($sf);
        } else {
            $error = q_('error_creating_backup_file', array('filename' => $filename));
        }
    }
    $fs = fopen($filename, 'w');
    if ($fs) {
        fwrite($fs, $_POST['edittext']);
        fclose($fs);
    } else {
        $error = q_('error_writing_file', array('filename' => $filename));
    }
    $page = $_POST['page'];
    // RecentChanges ändern
    $user = $_SERVER['PHP_AUTH_USER'];
    if ($user == "") {
        $user = $_SERVER['REMOTE_ADDR'];
        if ($user == "") {
            $user = q_('unknown');
        }
    }
    $rc = fopen($config['pages_dir'].$config['recentchanges'].'.txt', 'a');
    fwrite($rc, '* '.strftime("%c").': '.$page.' '.q_('by').' '.$user."\n");
    fclose($rc);
    include('./includes/templates/save.php');
} else {
    if (preg_match('/([A-Z][a-z]+){2,}/', $_GET['page']) || preg_match('/([A-Z][a-z]+){2,}/', $_POST['page'])) {
        if (isset($_POST['preview']) && $_POST['preview'] != '') {
            $page = $_POST['page'];
            $text = $_POST['edittext'];
        } else {
            $page = $_GET['page'];
            $text = get_page_wikitext($page);
        }
    } else {
        $page = $config['frontpage'];
        $text = get_page_wikitext($page);
    }
    include('./includes/templates/edit.php');
}
?>
