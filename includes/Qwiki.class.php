<?php
/**
 * This file contains the main Qwiki class
 * @package Qwiki
 * @copyright 2015 Eric Haberstroh
 * @author Eric Haberstroh <eric@erixpage.de>
 * @version 2.0.2
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
 * This version of Qwiki
 */
define('QWIKI_VERSION', '2.0.2');

/**
 * This exception code is used when the constructor parameters are not
 * given or are not arrays (as one would expect $_GET and $_POST to be).
 */
define('QWIKI_ERR_CONSTRUCTOR', 100);
/**
 * This exception code is used when an unknown action parameter is found
 * in $get or $post.
 */
define('QWIKI_ERR_PARAMETER', 110);
/**
 * This exception code is used when anything goes wrong querying the database
 * or reading/writing the database file.
 */
define('QWIKI_ERR_DB', 120);
/**
 * This error code is used when anything goes wrong with file I/O.
 */
define('QWIKI_ERR_FILE', 130);

/**
 * Various action definitions for internal usage
 */
define('QWIKI_ACTION_VIEW', 1);
define('QWIKI_ACTION_EDIT', 2);
define('QWIKI_ACTION_PREVIEW', 3);
define('QWIKI_ACTION_SAVE', 4);
define('QWIKI_ACTION_DIFF', 5);
define('QWIKI_ACTION_SEARCH', 6);
define('QWIKI_ACTION_SETUSERNAME', 7);

/**
 * Class definition: custom exception class
 */
class QwikiException extends Exception {
    /**
     * Class constructor (calls parent constructor)
     * @param string Error message
     * @param integer Error code
     */
    public function __construct($message, $code = 0) {
        parent::__construct($message, $code);
    }
    
    /**
     * Custom function for transforming the exception into a string
     * @return string The generated exception string
     */
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}";
    }
    
    /**
     * Generate a basic HTML page to display the exception
     */
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

/**
 * Class definition: Main Qwiki class
 */
class Qwiki {
    /**
     * Array of input values from $get and $post ($post overrides $get)
     * @var array
     * @access protected
     */
    protected $input = array();
    /**
     * Regular expression for handling CamelCase words
     * @var string
     * @access protected
     */
    protected $regex_camelcase = '/([A-Z][a-z]+){2,}/';
    /**
     * Name of function to call for replacing CamelCase words by links
     * @var string
     * @access protected
     */
    protected $camelcase_function = 'Qwiki::parse_camelcase';
    /**
     * Will contain the username when the appropriate cookie is set
     * @var string
     * @access protected
     */
    protected $username = null;
    /**
     * Will contain the Smarty instance object
     * @var object
     * @access private
     */
    private $smarty;
    /**
     * Will contain the database object
     * @var object
     * @access private
     */
    private $db;
    
    /**
     * Class constructor
     * @param array Array of $_GET values
     * @param array Array of $_POST values
     * @access public
     */
    public function __construct($get, $post) {
        // Check parameters
        if (is_array($get) && is_array($post)) {
            $this->input = array_merge($get, $post);
        } else {
            throw new QwikiException('Parameters $get and $post have to be arrays.', QWIKI_ERR_CONSTRUCTOR);
        }
        // Create Smarty object and fill in some basic values
        $this->smarty = new QwikiSmarty();
        $this->smarty->assign('arrInput', $this->input);
        $this->smarty->assign('conffile', QWIKI_LOCALE.'.conf');
        $this->smarty->assign('logofile', QWIKI_LOGO);
        $this->smarty->assign('findpagename', QWIKI_FINDPAGE);
        // Check the fulltext index and create it if necessary
        if (file_exists(QWIKI_INDEX_FILE) && is_writable(QWIKI_INDEX_FILE)) {
            $this->db = new SQLite3(QWIKI_INDEX_FILE);
            if (!$this->db) {
                throw new QwikiException('Error creating database handle for '.QWIKI_INDEX_FILE.'.', QWIKI_ERR_DB);
            }
            $result = $this->db->exec("CREATE VIRTUAL TABLE IF NOT EXISTS qwiki_index USING fts3(pagename VARCHAR(255) NOT NULL, content TEXT, modified_at DATETIME, modified_by VARCHAR(15), username VARCHAR(100) DEFAULT '')");
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
            $result = $this->db->exec("CREATE VIRTUAL TABLE IF NOT EXISTS qwiki_index USING fts3(pagename VARCHAR(255) NOT NULL, content TEXT, modified_at DATETIME, modified_by VARCHAR(15), username VARCHAR(100) DEFAULT '')");
            if (!$result) {
                throw new QwikiException('Error creating nonexisting qwiki_index table in database file '.QWIKI_INDEX_FILE.'.', QWIKI_ERR_DB);
            }
            $this->rebuild_index();
        } elseif (file_exists(QWIKI_INDEX_FILE) && !is_writable(QWIKI_INDEX_FILE)) {
            throw new QwikiException('Database file '.QWIKI_INDEX_FILE.' is not writable.', QWIKI_ERR_DB);
        }
        // Check the username and set the appropriate variable
        if (isset($_COOKIE['qwiki_username']) && $_COOKIE['qwiki_username'] != '') {
            $this->username = $_COOKIE['qwiki_username'];
        } else {
            $this->username = '';
        }
        $this->smarty->assign('username', $this->username);
    }
    
    /**
     * Main function to display the site
     * @access public
     */
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
    
    /**
     * Action dispatcher: VIEW
     * @param string Name of the page to display
     * @param boolean If this is an error, the content of QWIKI_ERRORPAGE will be displayed
     * @access private
     */
    private function view($page, $error = false) {
        if (Qwiki::page_exists($page)) {
            $info = $this->page_info($page);
            if (!$info) {
                $this->update_page_info($page, $this->page_wikitext($page), $this->page_mtime($page), '', '');
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
    
    /**
     * Action dispatcher: EDIT
     * @param string Name of the page to edit
     * @access private
     */
    private function edit($page) {
        $this->smarty->assign('template', 'edit.tpl');
        $this->smarty->assign('title', Qwiki::expand_camelcase($page));
        $this->smarty->assign('page', $page);
        $this->smarty->assign('edittext', $this->page_wikitext($page));
        $this->smarty->display('index.tpl');
    }
    
    /**
     * Action dispatcher: PREVIEW
     * @param string Name of the page that is edited
     * @access private
     */
    private function preview($page) {
        $this->smarty->assign('template', 'edit.tpl');
        $this->smarty->assign('title', Qwiki::expand_camelcase($page));
        $this->smarty->assign('page', $page);
        $edittext = $this->input['edittext'];
        $nowtime = time();
        $ipaddr = $_SERVER['REMOTE_ADDR'];
        $username = $this->username;
        // Replace four tildes (~~~~) by username and timestamp
        $previewtext = str_replace("~~~~", $username." ".strftime("%c", $nowtime), $edittext);
        // Replace two tildes (~~) by username
        $previewtext = str_replace("~~", $username, $previewtext);
        $this->smarty->assign('edittext', $edittext);
        $parser = new WikiParser($previewtext, $this->camelcase_function);
        $this->smarty->assign('preview', $parser->parse());
        $this->smarty->display('index.tpl');
    }
    
    /**
     * Action dispatcher: SAVE
     * @param string Name of the page to save
     * @access private
     */
    private function save($page) {
        $info = $this->page_info($page);
        $nowtime = time();
        $ipaddr = $_SERVER['REMOTE_ADDR'];
        $username = $this->username;
        // Replace four tildes (~~~~) by username and timestamp
        $edittext = str_replace("~~~~", $username." ".strftime("%c", $nowtime), $this->input['edittext']);
        // Replace two tildes (~~) by username
        $edittext = str_replace("~~", $username, $edittext);
        if (!$info) {
            $this->update_page_info($page, $edittext, $nowtime, $ipaddr, $username);
            $info = array('pagename' => $page, 'content' => '', 'modified_at' => date("Y-m-d H:i:s", $nowtime), 'modified_by' => $ipaddr, 'username' => '');
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
            $this->update_page_info($page, $edittext, $nowtime, $ipaddr, $username);
        }
        if ($this->page_wikitext($page) == "delete" && $this->page_backuptext($page) == "delete") {
            // Page file as well as backup file only contain the word "delete". Confirm this delete and remove
            // the page from the pages directory and index.
            @unlink($this->page_filename($page));
            @unlink($this->page_backupname($page));
            $this->remove_page_info($page);
        }
        $this->smarty->assign('template', 'save.tpl');
        $this->smarty->assign('title', Qwiki::expand_camelcase($page));
        $this->smarty->assign('page', $page);
        $this->smarty->display('index.tpl');
    }
    
    /**
     * Action dispatcher: DIFF
     * @param string Name of the page to display a quickdiff of
     * @access private
     */
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
    
    /**
     * Action dispatcher: SEARCH
     * @param string Term to search the database for
     * @access private
     */
    private function search($term) {
        // show snippets only when search term is not a CamelCase word
        $isWikiWord = preg_match("/([A-Z][a-z]+){2,}/", $term);
        $term = $this->db->escapeString($term);
        $index = array();
        $result = $this->db->query("SELECT pagename, snippet(qwiki_index) AS sn FROM qwiki_index WHERE pagename LIKE '%$term%'");
        if (!$result) {
            throw new QwikiException('The given search term resulted in a database error.', QWIKI_ERR_DB);
        }
        while ($row = $result->fetchArray()) {
            if (!$isWikiWord) {
                $snippet = $row['sn'];
            } else {
                $snippet = '';
            }
            $index[$row['pagename']] = $snippet;
        }
        $result = $this->db->query("SELECT pagename, snippet(qwiki_index) AS sn FROM qwiki_index WHERE qwiki_index MATCH '$term'");
        if (!$result) {
            throw new QwikiException('The given search term resulted in a database error.', QWIKI_ERR_DB);
        }
        while ($row = $result->fetchArray()) {
            if (!$isWikiWord) {
                $snippet = $row['sn'];
            } else {
                $snippet = '';
            }
            $index[$row['pagename']] = $this->sanitize_snippet($snippet);
        }
        ksort($index);
        $this->smarty->assign('template', 'search.tpl');
        $this->smarty->assign('title', Qwiki::expand_camelcase(QWIKI_FINDPAGE).": ".$term);
        $this->smarty->assign('page', QWIKI_FINDPAGE);
        $this->smarty->assign('results', $index);
        $this->smarty->display('index.tpl');
    }
    
    /**
     * Remove any HTML special characters from FTS3 snippets except "bold" tags inserted by FTS3
     * @param string snippet
     * @return string sanitized snippet
     * @access private
     */
    private function sanitize_snippet($snippet) {
        $snippet = str_replace("[[TAG_BOLD]]", "&#91;&#91;TAG_BOLD&#93;&#93;", $snippet);
        $snippet = str_replace("[[/TAG_BOLD]]", "&#91;&#91;/TAG_BOLD&#93;&#93;", $snippet);
        $snippet = str_replace("<b>", "[[TAG_BOLD]]", $snippet);
        $snippet = str_replace("</b>", "[[/TAG_BOLD]]", $snippet);
        $snippet = htmlspecialchars($snippet);
        $snippet = str_replace("[[TAG_BOLD]]", "<b>", $snippet);
        $snippet = str_replace("[[/TAG_BOLD]]", "</b>", $snippet);
        return $snippet;
    }
    
    /**
     * Action dispatcher: SETUSERNAME
     * @param string Username to set
     * @param string Page to display afterwards
     * @access private
     */
    private function setusername($username, $page) {
        @setcookie(QWIKI_COOKIE_NAME, $username, time()+QWIKI_COOKIE_EXPIRE);
        $this->username = $username;
        $this->smarty->assign('username', $username);
        $this->view($page);
    }
    
    /**
     * Generate HTML code of the given page
     * @param string Page name
     * @return string HTML code
     * @access private
     */
    private function page_html($page) {
        $parser = new WikiParser($this->page_wikitext($page), $this->camelcase_function);
        $result = $parser->parse();
        $result = str_replace("%%QWIKI_SEARCH%%", '<form method="post" action="'.QWIKI_DOCROOT.'index.php"><input type="text" name="term" value=""><button type="submit" name="action" value="search">OK</button></form>', $result);
        $result = str_replace("%%QWIKI_SETUSERNAME%%", '<form method="post" action="'.QWIKI_DOCROOT.'index.php"><input type="text" name="username" value="'.$this->username.'"><button type="submit" name="action" value="setusername">OK</button><input type="hidden" name="page" value="'.$page.'"></form>', $result);
        $result = str_replace("%%QWIKI_RECENTCHANGES%%", $this->generate_recentchanges(), $result);
        $result = str_replace("%%QWIKI_RECENTCHANGES_SHORT%%", $this->generate_recentchanges(QWIKI_RECENTCHANGES_INTERVAL_SHORT), $result);
        $result = str_replace("%%QWIKI_CREATEPAGE%%", '<form method="get" action="'.QWIKI_DOCROOT.'index.php"><input type="text" name="page" value=""><button type="submit" name="action" value="edit">OK</button></form>', $result);
        return $result;
    }
    
    /**
     * Get source text of the given page
     * @param string Page name
     * @return string Source text (QwikiText)
     * @access private
     */
    private function page_wikitext($page) {
        $filename = Qwiki::page_filename($page);
        if (file_exists($filename)) {
            return @file_get_contents($filename);
        } else {
            return "";
        }
    }
    
    /**
     * Get source text of the backup file of the given page
     * @param string Page name
     * @return string Content of the backup file (empty string if no backup exists)
     * @access private
     */
    private function page_backuptext($page) {
        $filename = Qwiki::page_backupname($page);
        if (file_exists($filename)) {
            return @file_get_contents($filename);
        } else {
           return "";
        }
    }
    
    /**
     * Get information about the given page from the database
     * @param string Page name
     * @return mixed Array of values or false if the entry does not exist in the database
     * @access private
     */
    private function page_info($page) {
        $page = $this->db->escapeString($page);
        $result = $this->db->query("SELECT pagename, content, modified_at, modified_by FROM qwiki_index WHERE pagename = '$page'");
        if (!$result) {
            throw new QwikiException("Error querying database for page '$page'.", QWIKI_ERR_DB);
        }
        return $result->fetchArray();
    }
    
    /**
     * Update page information in the database
     * @param string Page name
     * @param string Content of the page
     * @param string Date and time of last modification in SQL-apt format
     * @param string IP address of the modifier in dot notation
     * @access private
     */
    private function update_page_info($page, $content, $modified_at, $modified_by, $username) {
        $page = $this->db->escapeString($page);
        $content = $this->db->escapeString($content);
        $modified_at = $this->db->escapeString(date("Y-m-d H:i:s", $modified_at));
        $modified_by = $this->db->escapeString($modified_by);
        $username = $this->db->escapeString($username);
        if (!$this->page_info($page)) {
            $result = $this->db->exec("INSERT INTO qwiki_index (pagename, content, modified_at, modified_by, username) VALUES ('$page', '$content', '$modified_at', '$modified_by', '$username')");
            if (!$result) {
                throw new QwikiException("Error inserting new row for page '$page' into qwiki_index.", QWIKI_ERR_DB);
            }
        } else {
            $result = $this->db->exec("UPDATE qwiki_index SET content = '$content', modified_at = '$modified_at', modified_by = '$modified_by', username = '$username' WHERE pagename = '$page' LIMIT 1");
            if (!$result) {
                throw new QwikiException("Error updating row for page '$page' in qwiki_index.", QWIKI_ERR_DB);
            }
        }
    }
    
    private function remove_page_info($page) {
        $page = $this->db->escapeString($page);
        $result = $this->db->exec("DELETE FROM qwiki_index WHERE pagename = '$page'");
        if (!$result) {
            throw new QwikiException("Error removing index information on page $page from qwiki_index.", QWIKI_ERR_DB);
        }
    }
    
    /**
     * Rebuild complete index from the files in the QWIKI_DIR_PAGES directory
     * @access private
     */
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
                $this->update_page_info($page, $content, $modified_at, '', '');
            }
        }
        @closedir($dirh);
    }
    
    /**
     * Generate Recent Changes from database
     * @return string The generated HTML output
     * @access private
     */
    private function generate_recentchanges($interval = QWIKI_RECENTCHANGES_INTERVAL) {
        $until = new DateTime();
        $until->sub(new DateInterval($interval));
        $untild = $until->format('Y-m-d H:i:s');
        $sql = "SELECT pagename, modified_at, modified_by, username FROM qwiki_index WHERE modified_at >= '$untild' ORDER BY modified_at DESC";
        $result = $this->db->query($sql);
        if (!$result) {
            throw new QwikiException("Error querying database for recent changes.", QWIKI_ERR_DB);
        }
        $rc = array();
        while ($row = $result->fetchArray()) {
            $rc[] = $row;
        }
        $year = null;
        $month = null;
        $day = null;
        $o = "";
        if (empty($rc)) {
            $o = "**No changes during interval ".$interval.".**";
        }
        foreach ($rc as $r) {
            $pagename = $r['pagename'];
            $modified_at = $r['modified_at'];
            $modified_by = $r['modified_by'];
            $username = $r['username'];
            $time = strtotime($modified_at);
            $ryear = strftime("%G", $time);
            $rmonth = strftime("%B", $time);
            $rday = strftime("%A, %e.%m.", $time);
            $rtime = date("H:i:s", $time);
            if ($year != $ryear) {
                $o .= "== $ryear ==\n";
                $year = $ryear;
            }
            if ($month != $rmonth) {
                $o .= "=== $rmonth ===\n";
                $month = $rmonth;
            }
            if ($day != $rday) {
                $o .= "==== $rday ====\n";
                $day = $rday;
            }
            if ($username != '' && $modified_by != '') {
                $o .= "* $rtime $pagename ($username - $modified_by)\n";
            } elseif ($username == '' && $modified_by != '') {
                $o .= "* $rtime $pagename ($modified_by)\n";
            } elseif ($username != '' && $modified_by == '') {
                $o .= "* $rtime $pagename ($username)\n";
            } else {
                $o .= "* $rtime $pagename\n";
            }
        }
        $parser = new WikiParser($o, $this->camelcase_function);
        return $parser->parse();
    }
    
    /**
     * Check if a page exists on disk
     * @param string Page name
     * @return boolean
     * @access public
     */
    public static function page_exists($page) {
        return file_exists(Qwiki::page_filename($page));
    }
    
    /**
     * Check if a backup file for the given page exists
     * @param string Page name
     * @return boolean
     * @access public
     */
    public static function backup_exists($page) {
        return file_exists(Qwiki::page_backupname($page));
    }
    
    /**
     * Get the modification timestamp for the given page
     * @param string Page name
     * @return integer Timestamp
     * @access public
     */
    public static function page_mtime($page) {
        return filemtime(Qwiki::page_filename($page));
    }
    
    /**
     * Get the full path to the given page
     * @param string Page name
     * @return string Path
     * @access public
     */
    public static function page_filename($page) {
        return QWIKI_DIR_PAGES . $page . '.txt';
    }
    
    /**
     * Extract the page name from a file name by subtracting the last 4 characters (.txt)
     * @param string Filename
     * @return string Page name
     * @access public
     */
    public static function file_pagename($file) {
        return substr($file, 0, -4);
    }
    
    /**
     * Get the full path to the backup file of the given page
     * @param string Page name
     * @return string Path
     * @access public
     */
    public static function page_backupname($page) {
        return Qwiki::page_filename($page) . '~';
    }
    
    /**
     * Transform CamelCase words to links (called via preg_replace_callback by WikiParser)
     * @param array Array of matches (only first value is evaluated)
     * @return string Link to the page (or to the EditText form of that page if it doesn't exist)
     * @access public
     */
    public static function parse_camelcase($match) {
        $w = $match[0];
        if (Qwiki::page_exists($w)) {
            $ret = '<a href="'.QWIKI_DOCROOT.$w.'">'.$w.'</a>';
        } else {
            $ret = $w.'<a href="'.QWIKI_DOCROOT.$w.'/edit">?</a>';
        }
        return $ret;
    }
    
    /**
     * Expand CamelCase words for displaying in headings (CamelCaseWord => Camel Case Word)
     * @param string CamelCase word
     * @return string expanded word
     * @access public
     */
    public static function expand_camelcase($word) {
        return preg_replace("/(([a-z])([A-Z])|([A-Z])([A-Z][a-z]))/","\\2\\4 \\3\\5", $word);
    }
}
?>