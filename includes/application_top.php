<?php
/**
 * This file contains various general-purpose functions for Qwiki
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
require_once('./includes/WikiParser.class.php');

$config = parse_ini_file('./includes/config.ini');
$logo = '<img src="images/logo.png" alt="Qwiki:">&nbsp;';

setlocale(LC_ALL, $config['locale']);
// Try to load internationalized strings
$i18n_file = './includes/i18n/i18n.'.$config['locale'].'.ini';
$i18n_fallback_file = './includes/i18n/i18n.'.$config['fallback_locale'].'.ini';
$i18n_strings = array();
$i18n_fallback_strings = array();
if (file_exists($i18n_file)) {
    $i18n_strings = parse_ini_file($i18n_file);
}
if (file_exists($i18n_fallback_file)) {
    $i18n_fallback_strings = parse_ini_file($i18n_fallback_file);
}
$i18n = array_merge($i18n_fallback_strings, $i18n_strings);

function q_($string, $replacements = null) {
    global $i18n;
    if (isset($i18n[$string])) {
        $translation = $i18n[$string];
        if ($replacements !== null) {
            foreach ($replacements as $key => $value) {
                $translation = str_replace('$'.$key.'$', $value, $translation);
            }
        }
    } else {
        $translation = $string;
    }
    return $translation;
}

function expand_camelcase($word) {
    return preg_replace("/(([a-z])([A-Z])|([A-Z])([A-Z][a-z]))/","\\2\\4 \\3\\5", $word);
}

function parse_camelcase($match) {
    global $config;
    $w = $match[0];
    if (file_exists($config['pages_dir'].$w.'.txt')) {
        $ret = '<a href="wiki.php?page='.$w.'">'.$w.'</a>';
    } else {
        $ret = $w.'<a href="edit.php?page='.$w.'">?</a>';
    }
    return $ret;
}

function get_page_html($page) {
    global $config;
    if (file_exists($config['pages_dir'].$page.'.txt')) {
        return parse_wikitext(get_page_wikitext($page));
    } else {
        return parse_wikitext(get_page_wikitext($config['errorpage']));
    }
}

function get_page_wikitext($page) {
    global $config;
    $filename = $config['pages_dir'].$page.'.txt';
    if (file_exists($filename)) {
        return file_get_contents($filename);
    } else {
        return "";
    }
    return "Something went wrong in get_page_wikitext when called with page = '$page'.";
}

function parse_wikitext($txt) {
    $parser = new WikiParser($txt, 'parse_camelcase');
    $text = $parser->parse();
    $text = str_replace('QWIKI_SEARCH', '<form method="POST" action="search.php"><input type="text" name="term" value=""><input type="submit" name="search" value="Suchen"></form>', $text);
    $text = str_replace('QWIKI_FULLSEARCH', '<form method="POST" action="fullsearch.php"><input type="text" name="term" value=""><input type="submit" name="search" value="Suchen"></form>', $text);
    return $text;
}
?>
