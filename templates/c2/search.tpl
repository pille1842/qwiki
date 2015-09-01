<h1><a href="{$smarty.const.QWIKI_DOCROOT}"><img src="{$logofile}" alt="Qwiki:"></a>&nbsp;<a href="{$smarty.const.QWIKI_DOCROOT}{$smarty.const.QWIKI_FINDPAGE}">{$title}</a></h1>
<ol>
{foreach from=$results key="pagename" item="snippet"}
    <li><a href="{$smarty.const.QWIKI_DOCROOT}{$pagename}">{$pagename}</a><br>{$snippet}</li>
{foreachelse}
    <li>{#no_results#}</li>
{/foreach}
</ol>
<hr>
{#pages_found#}: {$results|@count}