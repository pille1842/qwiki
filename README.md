# qwiki
Quick and Dirty Wiki system written in PHP. All pages are stored on harddisk. A 
SQLite3 database is used for full-text indexing.

## Requirements

Qwiki needs PHP5 with an active SQLite3 extension with built-in FTS3 support. On
Ubuntu, installing `sqlite3` and `php5-sqlite` should suffice.

## Installation

1. Clone the repository or download the ZIP file into any directory beneath your
   document root.
2. Copy the files for your locale from `pages_dist/$yourlocale` into the `pages`
   directory.
3. Give the webserver write access to the following directories and all files in
   them: `pages`, `templates_c`, `cache`.
4. Rename the configuration.$yourlocale.php file in the `includes/` directory to
   `configuration.php` and make any changes necessary.
5. Change `.htaccess` in the qwiki root folder so that the `RewriteBase` 
   directive points to the location of your Qwiki installation.

Congratulations! Qwiki is set up. Navigate your browser to the directory you 
placed it in. You should see the FrontPage. The first load may take a while as 
the fulltext index is generated.

## Initial set of pages

Initially, the Qwiki distribution comes with several pages:
* HomePage, which is just what it says.
* PageNotFound, which is displayed when you try to view a page that doesn't
  exist (Error 404).
* FindPage, which contains the search forms for title and full-text search and
  some explanation for their use.
* WikiSyntax, which explains the syntax for writing wiki pages.
* RecentChanges, which is edited by the system when any changes are made to wiki
  pages.
* GoodStyle, which may contain any advisory for future editors you want to
  incorporate.
* WikiSandbox, which is for trying out the formatting rules and such.

You may edit any of those pages to your liking. Have a look at their source code
to see what makes wiki work internally.

## Security advisory

As anybody can edit pages in Qwiki without any restrictions whatsoever, using it
in a publicly accessable area is not advised at this time. You may, however, use
Qwiki as your personal notebook, a collection of links to websites you like, or
as a collaboration tool for small groups.

## Templates

The layout of Qwiki is heavily influenced by the first-ever wiki,
[WikiWikiWeb](http://c2.com/cgi/wiki). If you don't like the way it looks, feel
free to change it. All page templates lie in `templates`. The templates for
various pages of Qwiki are dynamically included into `index.tpl`. The template
engined used is [Smarty](http://www.smarty.net/).

## License

Qwiki is licensed under GNU General Public License v3 or later. Have a look at
LICENSE for a copy of GPLv3.
