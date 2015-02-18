<?php
/**
 * This file contains the main Qwiki class
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

define('QWIKI_VERSION', '2.0');

define('QWIKI_ERR_CONSTRUCTOR', 100);
define('QWIKI_ERR_PARAMETER', 110);
define('QWIKI_ERR_DB', 120);
define('QWIKI_ERR_FILE', 130);

define('QWIKI_ACTION_VIEW', 1);
define('QWIKI_ACTION_EDIT', 2);
define('QWIKI_ACTION_PREVIEW', 3);
define('QWIKI_ACTION_SAVE', 4);
define('QWIKI_ACTION_DIFF', 5);
define('QWIKI_ACTION_SEARCH', 6);
define('QWIKI_ACTION_SETUSERNAME', 7);

class QwikiException extends Exception {
    public function __construct($message, $code = 0) {
        parent::__construct($message, $code);
    }
    
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}";
    }
    
    public function messagePage() {
        header('HTTP/1.0 500 Internal Server Error');
        echo '<!DOCTYPE html>'."\n"
            .'<html lang="de">'."\n"
            .'<head>'."\n"
            .'<meta charset="UTF-8">'."\n"
            .'<title>Fatal Error</title>'."\n"
            .'</head>'."\n"
            .'<body>'."\n"
            .'<h1>Oops! There was an error.</h1>'."\n"
            .'<p>'.$this->getMessage().'</p>'."\n"
            .'<hr><i>Qwiki/'.QWIKI_VERSION.' '.$this->getCode().'</i>'."\n"
            .'</body>'."\n"
            .'</html>'."\n";
    }
}

class Qwiki {
    protected $input = array();
    protected $regex_camelcase = '/([A-Z][a-z]+){2,}/';
    protected $camelcase_function = 'Qwiki::parse_camelcase';
    protected $username = null;
    private $smarty;
    private $db;
    
    public function __construct($get, $post) {
        if (is_array($get) && is_array($post)) {
            $this->input = array_merge($get, $post);
        } else {
            throw new QwikiException('Parameters $get and $post have to be arrays.', QWIKI_ERR_CONSTRUCTOR);
        }
        $this->smarty = new QwikiSmarty();
        $this->smarty->assign('arrInput', $this->input);
        $this->smarty->assign('conffile', QWIKI_LOCALE.'.conf');
        $this->smarty->assign('logofile', QWIKI_LOGO);
        $this->smarty->assign('findpagename', QWIKI_FINDPAGE);
        if (file_exists(QWIKI_INDEX_FILE) && is_writable(QWIKI_INDEX_FILE)) {
            $this->db = new SQLite3(QWIKI_INDEX_FILE);
            if (!$this->db) {
                throw new QwikiException('Error creating database handle for '.QWIKI_INDEX_FILE.'.', QWIKI_ERR_DB);
            }
            $result = $this->db->exec("CREATE VIRTUAL TABLE IF NOT EXISTS qwiki_index USING fts3(pagename VARCHAR(255) NOT NULL, content TEXT, modified_at DATETIME, modified_by VARCHAR(15))");
            if (!$result) {
                throw new QwikiException('Error creating nonexisting qwiki_index table in database file '.QWIKI_INDEX_FILE.'.', QWIKI_ERR_DB);
            }
        } elseif (!file_exists(QWIKI_INDEX_FILE)) {
            $result = @touch(QWIKI_INDEX_FILE);
            if (!$result) {
                throw new QwikiException('Error creating database file '.QWIKI_INDEX_FILE.'.', QWIKI_ERR_DB);
            }
            $this->db = new SQLite3(QWIKI_INDEX_FILE);
            if (!$this->db) {
                throw new QwikiException('Error creating database handle for '.QWIKI_INDEX_FILE.'.', QWIKI_ERR_DB);
            }
            $result = $this->db->exec("CREATE VIRTUAL TABLE IF NOT EXISTS qwiki_index USING fts3(pagename VARCHAR(255) NOT NULL, content TEXT, modified_at DATETIME, modified_by VARCHAR(15))");
            if (!$result) {
                throw new QwikiException('Error creating nonexisting qwiki_index table in database file '.QWIKI_INDEX_FILE.'.', QWIKI_ERR_DB);
            }
            $this->rebuild_index();
        } elseif (file_exists(QWIKI_INDEX_FILE) && !is_writable(QWIKI_INDEX_FILE)) {
            throw new QwikiException('Database file '.QWIKI_INDEX_FILE.' is not writable.', QWIKI_ERR_DB);
        }
        if (isset($_COOKIE['qwiki_username']) && $_COOKIE['qwiki_username'] != '') {
            $this->username = $_COOKIE['qwiki_username'];
        } else {
            $this->username = '';
        }
        $this->smarty->assign('username', $this->username);
    }
    
    public function run() {
        if (isset($this->input['action']) && $this->input['action'] != '') {
            switch ($this->input['action']) {
                case 'view':
                    $action = QWIKI_ACTION_VIEW;
                    break;
                case 'edit':
                    $action = QWIKI_ACTION_EDIT;
                    break;
                case 'preview':
                    $action = QWIKI_ACTION_PREVIEW;
                    break;
                case 'save':
                    $action = QWIKI_ACTION_SAVE;
                    break;
                case 'diff':
                    $action = QWIKI_ACTION_DIFF;
                    break;
                case 'search':
                    $action = QWIKI_ACTION_SEARCH;
                    break;
                case 'setusername':
                    $action = QWIKI_ACTION_SETUSERNAME;
                    break;
                default:
                    throw new QwikiException("Unknown action parameter '{$this->input['action']}'.", QWIKI_ERR_PARAMETER);
                    break;
            }
        } else {
            $action = QWIKI_ACTION_VIEW;
        }
        switch ($action) {
            case QWIKI_ACTION_VIEW:
                if (isset($this->input['page']) && $this->input['page'] != '') {
                    $page = $this->input['page'];
                } else {
                    $page = QWIKI_FRONTPAGE;
                }
                if (preg_match($this->regex_camelcase, $page)) {
                    $this->view($page);
                } else {
                    header('HTTP/1.0 404 Not Found');
                    $this->smarty->assign('error', 'invalid_pagename');
                    $this->view($page, true);
                }
                break;
            case QWIKI_ACTION_EDIT:
                if (isset($this->input['page']) && $this->input['page'] != '' && preg_match($this->regex_camelcase, $this->input['page'])) {
                    $this->edit($this->input['page']);
                } else {
                    $this->smarty->assign('error', 'invalid_pagename');
                    $this->view($this->input['page'], true);
                }
                break;
            case QWIKI_ACTION_PREVIEW:
                if (isset($this->input['page']) && $this->input['page'] != '' && preg_match($this->regex_camelcase, $this->input['page'])) {
                    $this->preview($this->input['page']);
                } else {
                    $this->smarty->assign('error', 'invalid_pagename');
                    $this->view($this->input['page'], true);
                }
                break;
            case QWIKI_ACTION_SAVE:
                if (isset($this->input['page']) && $this->input['page'] != '' && preg_match($this->regex_camelcase, $this->input['page'])) {
                    if (trim($this->input['edittext']) != '') {
                        $this->save($this->input['page']);
                    } else {
                        $this->smarty->assign('error', 'invalid_edittext');
                        $this->view($this->input['page'], true);
                    }
                } else {
                    $this->smarty->assign('error', 'invalid_pagename');
                    $this->view($this->input['page'], true);
                }
                break;
            case QWIKI_ACTION_DIFF:
                if (isset($this->input['page']) && $this->input['page'] != '' && preg_match($this->regex_camelcase, $this->input['page'])) {
                    $this->diff($this->input['page']);
                } else {
                    $this->smarty->assign('error', 'invalid_pagename');
                    $this->view($this->input['page'], true);
                }
                break;
            case QWIKI_ACTION_SEARCH:
                if (isset($this->input['term']) && $this->input['term'] != '') {
                    $this->search($this->input['term']);
                } else {
                    $this->view(QWIKI_FINDPAGE);
                }
                break;
            case QWIKI_ACTION_SETUSERNAME:
                if (isset($this->input['username']) && $this->input['username'] != '') {
                    if (isset($this->input['page']) && $this->input['page'] != '') {
                        $page = $this->input['page'];
                    } else {
                        $page = QWIKI_FRONTPAGE;
                    }
                    $this->setusername($this->input['username'], $page);
                }
                break;
        }
    }
    
    private function view($page, $error = false) {
        if (Qwiki::page_exists($page)) {
            $info = $this->page_info($page);
            if (!$info) {
                $this->update_page_info($page, $this->page_wikitext($page), $this->page_mtime($page), '');
            }
            $this->smarty->assign('template', 'view.tpl');
            $this->smarty->assign('title', Qwiki::expand_camelcase($page));
            $this->smarty->assign('page', $page);
            if ($error !== false) {
                $this->smarty->assign('content', $this->page_html(QWIKI_ERRORPAGE));
            } else {
                $this->smarty->assign('content', $this->page_html($page));
            }
            $this->smarty->assign('pagemodtime', strftime("%c", Qwiki::page_mtime($page)));
            $this->smarty->assign('backupexists', Qwiki::backup_exists($page));
        } else {
            $this->smarty->assign('template', 'view.tpl');
            $this->smarty->assign('title', Qwiki::expand_camelcase($page));
            $this->smarty->assign('page', $page);
            $this->smarty->assign('error', 'page_not_found');
            $this->smarty->assign('content', $this->page_html(QWIKI_ERRORPAGE));
            $this->smarty->assign('pagemodtime', strftime("%c"));
        }
        $this->smarty->display('index.tpl');
    }
    
    private function edit($page) {
        $this->smarty->assign('template', 'edit.tpl');
        $this->smarty->assign('title', Qwiki::expand_camelcase($page));
        $this->smarty->assign('page', $page);
        $this->smarty->assign('edittext', $this->page_wikitext($page));
        $this->smarty->display('index.tpl');
    }
    
    private function preview($page) {
        $this->smarty->assign('template', 'edit.tpl');
        $this->smarty->assign('title', Qwiki::expand_camelcase($page));
        $this->smarty->assign('page', $page);
        $this->smarty->assign('edittext', $this->input['edittext']);
        $parser = new WikiParser($this->input['edittext'], $this->camelcase_function);
        $this->smarty->assign('preview', $parser->parse());
        $this->smarty->display('index.tpl');
    }
    
    private function save($page) {
        $info = $this->page_info($page);
        $now = date("Y-m-d H:i:s");
        $nowtime = time();
        $ipaddr = $_SERVER['REMOTE_ADDR'];
        if ($this->username != '') {
            $username = $this->username;
        } else {
            $username = $ipaddr;
        }
        $edittext = str_replace("~~~~", "--".$username." ".strftime("%c", $nowtime), $this->input['edittext']);
        if (!$info) {
            $this->update_page_info($page, $edittext, $now, $ipaddr);
            $info = array('pagename' => $page, 'content' => $edittext, 'modified_at' => $now, 'modified_by' => $ipaddr);
        }
        if ($info['content'] != $edittext) {
            if ($ipaddr != $info['modified_by']) {
                $f = @fopen($this->page_backupname($page), 'w');
                if (!$f) {
                    throw new QwikiException("Error creating file handle for $page's backup file.", QWIKI_ERR_FILE);
                }
                $result = @fwrite($f, $info['content']);
                if ($result === false) {
                    throw new QwikiException("Error writing contents of $page to backup file.", QWIKI_ERR_FILE);
                }
                @fclose($f);
                $this->update_recentchanges($page, strftime("%c", $nowtime), $username);
            }
            $f = @fopen($this->page_filename($page), 'w');
            if (!$f) {
                throw new QwikiException("Error creating file handle for $page's file.", QWIKI_ERR_FILE); 
            }
            $result = @fwrite($f, $edittext);
            if ($result === false) {
                throw new QwikiException("Error writing contents of $page to file.", QWIKI_ERR_FILE);
            }
            @fclose($f);
            $this->update_page_info($page, $edittext, $now, $ipaddr);
        }
        $this->smarty->assign('template', 'save.tpl');
        $this->smarty->assign('title', Qwiki::expand_camelcase($page));
        $this->smarty->assign('page', $page);
        $this->smarty->display('index.tpl');
    }
    
    private function diff($page) {
        if (Qwiki::page_exists($page) && Qwiki::backup_exists($page)) {
            $diff = Diff::toTable(Diff::compareFiles(Qwiki::page_backupname($page), Qwiki::page_filename($page)));
            $this->smarty->assign('template', 'diff.tpl');
            $this->smarty->assign('title', Qwiki::expand_camelcase($page));
            $this->smarty->assign('page', $page);
            $this->smarty->assign('diff', $diff);
            $this->smarty->display('index.tpl');
        } elseif (Qwiki::page_exists($page) && !Qwiki::backup_exists($page)) {
            $this->smarty->assign('template', 'diff.tpl');
            $this->smarty->assign('title', Qwiki::expand_camelcase($page));
            $this->smarty->assign('page', $page);
            $this->smarty->assign('diff', "There is no backup version of $page yet.");
            $this->smarty->display('index.tpl');
        } else {
            $this->smarty->assign('error', 'nonexistent_files');
            $this->view($page, true);
        }
    }
    
    private function search($term) {
        $term = $this->db->escapeString($term);
        $index = array();
        $result = $this->db->query("SELECT pagename, snippet(qwiki_index) AS sn FROM qwiki_index WHERE pagename LIKE '%$term%'");
        if (!$result) {
            throw new QwikiException('The given search term resulted in a database error.', QWIKI_ERR_DB);
        }
        while ($row = $result->fetchArray()) {
            $index[$row['pagename']] = $row['sn'];
        }
        $result = $this->db->query("SELECT pagename, snippet(qwiki_index) AS sn FROM qwiki_index WHERE qwiki_index MATCH '$term'");
        if (!$result) {
            throw new QwikiException('The given search term resulted in a database error.', QWIKI_ERR_DB);
        }
        while ($row = $result->fetchArray()) {
            $index[$row['pagename']] = $row['sn'];
        }
        $this->smarty->assign('template', 'search.tpl');
        $this->smarty->assign('title', Qwiki::expand_camelcase(QWIKI_FINDPAGE));
        $this->smarty->assign('page', QWIKI_FINDPAGE);
        $this->smarty->assign('results', $index);
        $this->smarty->display('index.tpl');
    }
    
    private function setusername($username, $page) {
        @setcookie(QWIKI_COOKIE_NAME, $username, time()+QWIKI_COOKIE_EXPIRE);
        $this->username = $username;
        $this->smarty->assign('username', $username);
        $this->view($page);
    }
    
    private function page_html($page) {
        $parser = new WikiParser($this->page_wikitext($page), $this->camelcase_function);
        $result = $parser->parse();
        $result = str_replace("QWIKI_SEARCH", '<form method="post" action="'.QWIKI_DOCROOT.'index.php"><input type="text" name="term" value=""><button type="submit" name="action" value="search">OK</button></form>', $result);
        $result = str_replace("QWIKI_SETUSERNAME", '<form method="post" action="'.QWIKI_DOCROOT.'index.php"><input type="text" name="username" value="'.$this->username.'"><button type="submit" name="action" value="setusername">OK</button><input type="hidden" name="page" value="'.$page.'"></form>', $result);
        return $result;
    }
    
    private function page_wikitext($page) {
        $filename = Qwiki::page_filename($page);
        if (file_exists($filename)) {
            return @file_get_contents($filename);
        } else {
            return "";
        }
    }
    
    private function page_backuptext($page) {
        $filename = Qwiki::page_backupname($page);
        if (file_exists($filename)) {
            return @file_get_contents($filename);
        } else {
           return "";
        }
    }
    
    private function page_info($page) {
        $page = $this->db->escapeString($page);
        $result = $this->db->query("SELECT pagename, content, modified_at, modified_by FROM qwiki_index WHERE pagename = '$page'");
        if (!$result) {
            throw new QwikiException("Error querying database for page '$page'.", QWIKI_ERR_DB);
        }
        return $result->fetchArray();
    }
    
    private function update_page_info($page, $content, $modified_at, $modified_by) {
        $page = $this->db->escapeString($page);
        $content = $this->db->escapeString($content);
        $modified_at = $this->db->escapeString($modified_at);
        $modified_by = $this->db->escapeString($modified_by);
        if (!$this->page_info($page)) {
            $result = $this->db->exec("INSERT INTO qwiki_index (pagename, content, modified_at, modified_by) VALUES ('$page', '$content', '$modified_at', '$modified_by')");
            if (!$result) {
                throw new QwikiException("Error inserting new row for page '$page' into qwiki_index.", QWIKI_ERR_DB); 
            }
        } else {
            $result = $this->db->exec("UPDATE qwiki_index SET content = '$content', modified_at = '$modified_at', modified_by = '$modified_by' WHERE pagename = '$page' LIMIT 1");
            if (!$result) {
                throw new QwikiException("Error updating row for page '$page' in qwiki_index.", QWIKI_ERR_DB); 
            }
        }
    }
    
    private function rebuild_index() {
        $dirh = @opendir(QWIKI_DIR_PAGES);
        if (!$dirh) {
            throw new QwikiException("Directory handle for ".QWIKI_DIR_PAGES." could not be created.", QWIKI_ERR_FILE);
        }
        while (($fname = readdir($dirh)) !== false) {
            if ($fname != '.' && $fname != '..' && substr($fname, -4, 4) == '.txt') {
                $page = Qwiki::file_pagename($fname);
                $content = $this->page_wikitext($page);
                $modified_at = Qwiki::page_mtime($page);
                $this->update_page_info($page, $content, $modified_at, '');
            }
        }
        @closedir($dirh);
    }
    
    private function update_recentchanges($page, $date, $username) {
        $rcpage = QWIKI_RECENTCHANGES;
        $oldrc = $this->page_wikitext($rcpage);
        $f = @fopen(Qwiki::page_filename($rcpage), "w");
        if (!$f) {
            throw new QwikiException("File handle for $rcpage couldn't be created.", QWIKI_ERR_FILE);
        }
        $newrc = $oldrc."* $date: $page ($username)\n";
        $result = @fwrite($f, $newrc);
        if (!$result) {
            throw new QwikiException("Contents could not be written to $rcpage file.", QWIKI_ERR_FILE);
        }
        @fclose($f);
    }
    
    public static function page_exists($page) {
        return file_exists(Qwiki::page_filename($page));
    }
    
    public static function backup_exists($page) {
        return file_exists(Qwiki::page_backupname($page));
    }
    
    public static function page_mtime($page) {
        return filemtime(Qwiki::page_filename($page));
    }
    
    public static function page_filename($page) {
        return QWIKI_DIR_PAGES . $page . '.txt';
    }
    
    public static function file_pagename($file) {
        return substr($file, 0, -4);
    }
    
    public static function page_backupname($page) {
        return Qwiki::page_filename($page) . '~';
    }
    
    public static function parse_camelcase($match) {
        $w = $match[0];
        if (Qwiki::page_exists($w)) {
            $ret = '<a href="'.QWIKI_DOCROOT.$w.'">'.$w.'</a>';
        } else {
            $ret = $w.'<a href="'.QWIKI_DOCROOT.$w.'/edit">?</a>';
        }
        return $ret;
    }
    
    public static function expand_camelcase($word) {
        return preg_replace("/(([a-z])([A-Z])|([A-Z])([A-Z][a-z]))/","\\2\\4 \\3\\5", $word);
    }
}
?>