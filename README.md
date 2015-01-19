# qwiki
Quick and Dirty Wiki system written in PHP. All pages are stored on harddisk. A SQLite3 database is used for full-text indexing.

## Requirements

Qwiki needs PHP5 with an active SQLite3 extension with built-in FTS3 support. On Ubuntu, installing `sqlite3` and `php5-sqlite` should suffice. If you don't want to install this, just remove the full-text search engine from your FindPage and delete `fullsearch.php` and `update_index.php`. The rest of the wiki works without SQLite3.

## Installation

1. Clone the repository or download the ZIP file into any directory beneath your document root.
2. Create a directory named `pages` and copy the files for your locale from `pages_dist/$yourlocale` into the newly created directory.
3. Give the webserver write access to the `pages` directory and all files in it.
4. Rename the config-dist.$yourlocale.ini in the `includes/` directory to `config.ini` and make any changes you deem necessary.
5. Run `update_index.php` once from your terminal or browser to build the initial full-text index SQLite database. You may want to incorporate a regular call to this file into your crontab.

Congratulations! Qwiki is set up. Navigate your browser to the directory you placed it in. You should see the HomePage.

## Initial set of pages

Initially, the Qwiki distribution comes with several pages:
* HomePage, which is just what it says.
* PageNotFound, which is displayed when you try to view a page that doesn't exist (Error 404).
* FindPage, which contains the search forms for title and full-text search and some explanation for their use.
* WikiSyntax, which explains the syntax for writing wiki pages.
* RecentChanges, which is edited by the system when any changes are made to wiki pages.
* GoodStyle, which may contain any advisory for future editors you want to incorporate.
* WikiSandbox, which is for trying out the formatting rules and such.
You may edit any of those pages to your liking. Have a look at their source code to see what makes wiki work internally.

## Security advisory

At this point, Qwiki contains no spam protection or edit policy whatsoever, so use on a publicly accessable server is not advised. You may however use Qwiki as your personal notebook, a repository of links you like, a personal information database or whatever comes to your mind.

## Templates

The layout of Qwiki is heavily influenced by the first-ever wiki, [WikiWikiWeb](http://c2.com/cgi/wiki). If you don't like the way it looks, feel free to change it. All page templates lie in `includes/templates`. The site templates always include `header.php` and `footer.php`, so you may incorporate any additional CSS files, JavaScript or else in those two files. The template system is pure, naked PHP.

## License

Qwiki is licensed under GNU General Public License v3 or later. Have a look at LICENSE for a copy of GPLv3.
