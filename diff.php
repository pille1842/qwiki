<?php
/**
 * This file contains the QuickDiff tool for Qwiki pages
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
require_once('./includes/class.Diff.php');

$page     = $_GET['page'];
if (!preg_match('/([A-Z][a-z]+){2,}/', $page)) {
    $page = $config['frontpage'];
}
$filename = $config['pages_dir'].$page.'.txt';
$bakname  = $config['pages_dir'].$page.'.txt~';

if (file_exists($filename)) {
	if (file_exists($bakname)) {
		$diff = Diff::toTable(Diff::compareFiles($bakname, $filename));
		$date_old = date("d.m.Y H:i", filemtime($bakname));
		$date_new = date("d.m.Y H:i", filemtime($filename));
	} else {
		$diff = q_('no_old_version', array('page' => $page));
		$date_old = "n/a";
		$date_new = date("d.m.Y H:i", filemtime($filename));
	}
} else {
	$diff = q_('page_does_not_exist', array('page' => $page));
	$date_old = "n/a";
	$date_new = "n/a";
}
include('./includes/templates/diff.php');
?>
