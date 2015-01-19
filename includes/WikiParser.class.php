<?php
/**
 * This file contains the WikiParser class
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

/**
 * Parse QwikiText and produce HTML
 */
class WikiParser {
    /**
     * @var string QwikiText to be parsed
     */
    public $sourceText;
    /**
     * @var string generated HTML output
     */
    public $htmlOutput;
    /**
     * @var string name of the CamelCase function
     */
    public $camelCaseFunction = null;
    /**
     * @var array Array of URLs in QwikiText
     */
    public $arrURLs;
    /**
     * @var array Array of <nowiki> blocks in QwikiText
     */
    public $arrNowiki;
    
    /**
     * Class constructor
     * @param string QwikiText
     * @param string Name of the CamelCase function
     */
    public function __construct($sourceText, $camelCaseFunction) {
        $this->sourceText = $sourceText;
        $this->camelCaseFunction = $camelCaseFunction;
    }
    
    /**
     * main function for parsing the given QwikiText
     * @return string erzeugter HTML-Code
     */
    public function parse() {
        $output = $this->specialsBefore($this->sourceText);
        $output2 = $this->parseLinewise($output);
        $output3 = $this->specialsAfter($output2);
        $this->htmlOutput = $output3;
        return $output3;
    }
    
    /**
     * div. format rules to be parsed in pieces of QwikiText
     * @param string snippet to be parsed
     * @return HTML output
     */
    public function parseBytewise($cell) {
        $cell = trim($cell);
        $cell = $this->replaceSpecialChars($cell);
        // bold (**Text**)
        $cell = preg_replace('/\*\*([^\*]+)\*\*/', '<b>$1</b>', $cell);
        // italics (//Text//)
        $cell = preg_replace('/\/\/([^\/]+)\/\//', '<i>$1</i>', $cell);
        // underline (__Text__)
        $cell = preg_replace('/\_\_([^\_]+)\_\_/', '<u>$1</u>', $cell);
        // strike-through (--Text--)
        $cell = preg_replace('/\-\-([^\-]+)\-\-/', '<del>$1</del>', $cell);
        // replace eMail addresses with their mailto: link
        $cell = preg_replace('/(\S+@\S+\.\S+)/', '<a href="mailto:$1">$1</a>', $cell);
        // use monospace font (''Text'')
        $cell = preg_replace('/\'\'([^\']+)\'\'/', '<tt>$1</tt>', $cell);
        // headings (H2-H5)
        $cell = preg_replace('/\=\=\=\=\=(.+)\=\=\=\=\=/', '<h5>$1</h5>', $cell);
        $cell = preg_replace('/\=\=\=\=(.+)\=\=\=\=/', '<h4>$1</h4>', $cell);
        $cell = preg_replace('/\=\=\=(.+)\=\=\=/', '<h3>$1</h3>', $cell);
        $cell = preg_replace('/\=\=(.+)\=\=/', '<h2>$1</h2>', $cell);
        // find CamelCase words and replace them with the return value of the CamelCase function
        $cell = preg_replace_callback('/([A-Z][a-z]+){2,}/', $this->camelCaseFunction, $cell);
        // replace 6 consecutive single quotes with an empty string to enable merging CamelCase links with prefixes and suffixes
        $cell = str_replace("''''''", "", $cell);
        return $cell;
    }
    
    /**
     * replace <nowiki> blocks and URLs by their own MD5 sum and save them via WikiParser::saveNowiki()
     * @param string QwikiText
     * @return string QwikiText without <nowiki> blocks and URLs
     */
    private function specialsBefore($op) {
        // <nowiki>-Abschnitte durch ihre MD5-Summe ersetzen
        $op = preg_replace_callback('#<nowiki>(.*?)</nowiki>#s', 'WikiParser::saveNowiki', $op);
        // das gleiche mit URLs machen
        $op = preg_replace_callback('/\b(?<!a href=\")(?<!src=\")((http|ftp)+(s)?:\/\/[^<>\s]+)/ix', 'WikiParser::saveURL', $op);
        return $op;
    }
    
    /**
     * reinsert <nowiki> blocks and URLs into QwikiText
     * @param string QwikiText
     * @return string final output with reinserted <nowiki> blocks and URLs
     */
    private function specialsAfter($op) {
        // reinsert URLs
        if (!empty($this->arrURLs)) {
            foreach ($this->arrURLs as $md5 => $url) {
                $matches = array();
                // embed player for YouTube links
                if (preg_match("#(?<=v=)[a-zA-Z0-9-]+(?=&)|(?<=v\/)[^&\n]+(?=\?)|(?<=v=)[^&\n]+|(?<=youtu.be/)[^&\n]+#", $url, $matches)) {
                    $op = str_replace($md5, '<iframe width="560" height="315" src="//www.youtube.com/embed/'.$matches[0].'" frameborder="0" allowfullscreen></iframe>', $op);
                // transform image links into <img> tags
                } elseif (preg_match('!http://([a-z0-9\-\.\/\_]+\.(?:jpe?g|png|gif))!Ui', $url, $matches)) {
                    $op = str_replace($md5, '<img src="'.$url.'" alt="'.basename($url).'">', $op);
                // link normal URLs with <a>
                } else {
                    $op = str_replace($md5, '<a href="'.$url.'">'.$url.'</a>', $op);
                }
            }
        }
        // replace ISBNs by Amazon links
        $op = preg_replace('/[ISBN]{4}[ ]{0,1}([0-9]{10,13})/', '<a href="http://www.amazon.de/gp/search/?field-isbn=$1">$0</a>', $op);
        // reinsert <nowiki> blocks
        if (!empty($this->arrNowiki)) {
            foreach ($this->arrNowiki as $md5 => $nowiki) {
                $op = str_replace($md5, $nowiki, $op);
            }
        }
        return $op;
    }
    
    /**
     * save URLs and return their MD5 sum
     * @param array Array of matches as generated by preg_replace_callback()
     * @return string MD5 value
     */
    private function saveURL($match) {
        $url = $match[0];
        $md5 = md5($url);
        $this->arrURLs[$md5] = $this->replaceSpecialChars($url);
        return $md5;
    }
    
    /**
     * save <nowiki> blocks and return their MD5 sum
     * @param array Array of matches as generated by preg_replace_callback()
     * @return string MD5 value
     */
    private function saveNowiki($match) {
        $nowiki = $match[0];
        $md5 = md5($nowiki);
        $nowiki = str_replace('<nowiki>', '', $nowiki);
        $nowiki = str_replace('</nowiki>', '', $nowiki);
        $this->arrNowiki[$md5] = $this->replaceSpecialChars($nowiki);
        return $md5;
    }
    
    /**
     * replace reserved characters (<>&) by their respective HTML special characters
     * @param string QwikiText
     * @return string resulting QwikiText
     */
    public function replaceSpecialChars($op) {
        $op = str_replace('&', '&amp;', $op);
        $op = str_replace('<', '&lt;', $op);
        $op = str_replace('>', '&gt;', $op);
        return $op;
    }
    
    /**
     * parse QwikiText linewise and generate tables, lists, quotes, and codeblocks
     * @param string QwikiText
     * @return string HTML output
     */
    public function parseLinewise($op) {
        // Depth of unsorted list
        $listDepth = 0;
        // Depth of sorted list
        $olDepth = 0;
        // Depth of quote blocks
        $quoteDepth = 0;
        // Are we in a table?
        $inTable = false;
        // Are we in a codeblock?
        $inPre = false;
        // QwikiText exploded into array
        $arrSource = explode("\n", $op);
        // Array of generated output lines
        $arrOutput = array();
        foreach ($arrSource as $l) {
            $handled = false;
            $arrMatches = array();
            $o = "";
            // Unordered List
            if (preg_match('/^(\*)+ /', $l, $arrMatches)) {
                while (strlen(trim($arrMatches[0])) > $listDepth) {
                    $listDepth += 1;
                    $o .= "<ul>";
                }
                while (strlen(trim($arrMatches[0])) < $listDepth) {
                    $o .= "</ul>";
                    $listDepth -= 1;
                }
                $o .= "<li>" . $this->parseBytewise(preg_replace('/^(\*)+/', '', $l)) . "</li>";
                $handled = true;
            } else {
                while ($listDepth > 0) {
                    $o .= "</ul>";
                    $listDepth -= 1;
                }
            }
            // Ordered List
            if (preg_match('/^(\#)+ /', $l, $arrMatches)) {
                while (strlen(trim($arrMatches[0])) > $olDepth) {
                    $olDepth += 1;
                    $o .= "<ol>";
                }
                while (strlen(trim($arrMatches[0])) < $olDepth) {
                    $o .= "</ol>";
                    $olDepth -= 1;
                }
                $o .= "<li>" . $this->parseBytewise(preg_replace('/^(\#)+/', '', $l)) . "</li>";
                $handled = true;
            } else {
                while ($olDepth > 0) {
                    $o .= "</ol>";
                    $olDepth -= 1;
                }
            }
            if (preg_match('/^(\>)+/', $l, $arrMatches)) {
                while (strlen($arrMatches[0]) > $quoteDepth) {
                    $quoteDepth += 1;
                    $o .= '<div style="margin-left:10px;">';
                }
                while (strlen($arrMatches[0]) < $quoteDepth) {
                    $o .= "</div>";
                    $quoteDepth -= 1;
                }
                $o .= $this->parseBytewise(preg_replace('/^(\>)+/', '', $l));
                $handled = true;
            } else {
                while ($quoteDepth > 0) {
                    $o .= "</div>";
                    $quoteDepth -= 1;
                }
            }
            // Table (normal row)
            if (preg_match('/^\|\|/', $l)) {
                if ($inTable == false) {
                    $o .= "<table>";
                    $inTable = true;
                }
                $o .= "<tr>";
                $arrLine = explode("||", preg_replace("/^\|\|/", "", $l));
                foreach ($arrLine as $cell) {
                    $o .= "<td>" . $this->parseBytewise(preg_replace("/\|\|/", "", $cell)) . "</td>";
                }
                $o .= "</tr>";
                $handled = true;
            } else {
                if ($inTable && !preg_match('/^\^\^/', $l)) {
                    $o .= "</table>";
                    $inTable = false;
                }
            }
            // Table (head row)
            if (preg_match('/^\^\^/', $l)) {
                if ($inTable == false) {
                    $o .= "<table>";
                    $inTable = true;
                }
                $o .= "<tr>";
                $arrLine = explode("^^", preg_replace("/^\^\^/", "", $l));
                foreach ($arrLine as $cell) {
                    $o .= "<th>" . $this->parseBytewise(preg_replace("/\^\^/", "", $cell)) . "</th>";
                }
                $o .= "</tr>";
                $handled = true;
            } else {
                if ($inTable && !preg_match('/^\|\|/', $l)) {
                    $o .= "</table>";
                    $inTable = false;
                }
            }
            // Codeblock
            if (preg_match('/^( )+/', $l)) {
                if ($inPre) {
                    $o .= preg_replace('/^( )+/', '', $this->replaceSpecialChars($l));
                } else {
                    $o .= "<pre>" . preg_replace('/^( )+/', '', $this->replaceSpecialChars($l));
                    $inPre = true;
                }
                $handled = true;
            } else {
                if ($inPre) {
                    $o .= "</pre>";
                    $inPre = false;
                }
            }
            // horizontal line (----)
            if (preg_match('/^(\-){4,}((.?)+)$/', $l, $match)) {
                $o .= "<hr>\n" . $this->parseBytewise($match[2]);
                $handled = true;
            }
            if (!$handled) {
                $o .= $this->parseBytewise($l);
            }
            // save output
            $arrOutput[] = $o;
        }
        $output = implode("\n", $arrOutput);
        // insert paragraph delimiters and remove empty paragraphs
        $output = preg_replace('/(\n){2,}/', '</p><p>', $output);
        $output = str_replace("<p></p>", "", $output);
        return $output;
    }
}
?>
