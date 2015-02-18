<h1><img src="{$logofile}" alt="Qwiki:"><a href="index.php?term={$page}&amp;action=search">{$title}</a></h1>

{$content}
<hr>
<a href="{$smarty.const.QWIKI_DOCROOT}{$page}/edit">{#EditPage#}</a> ({#LastEdit#}: <a href="{$smarty.const.QWIKI_DOCROOT}{$page}/diff">{$pagemodtime}</a>) {#or#} <a href="{$smarty.const.QWIKI_DOCROOT}{$findpagename}">{#FindPage#}</a> {#with_title_and_fulltext#}
{if $username neq ''}<div style="text-align:right;">{#username#}: {$username}</div>{/if}