<h1><a href="{$smarty.const.QWIKI_DOCROOT}"><img src="{$logofile}" alt="Qwiki:"></a>&nbsp;{$title}</h1>

{if isset($preview) AND $preview neq ""}
<h2>{#preview#}:</h2>
{$preview}
<hr>
{/if}

<p>{#attention_visitors_1#} <a href="{$smarty.const.QWIKI_DOCROOT}{#GoodStyle#}">{#GoodStyle#}</a> {#attention_visitors_2#} <a href="{$smarty.const.QWIKI_DOCROOT}{#WikiSandbox#}">{#WikiSandbox#}</a> {#attention_visitors_3#}</p>

<form method="post" action="{$smarty.const.QWIKI_DOCROOT}index.php">
    <textarea name="edittext" rows="25" cols="115">{$edittext}</textarea><br>
    <button type="submit" name="action" value="save">{#save#}</button>
    <button type="submit" name="action" value="preview">{#preview#}</button>
    <input type="hidden" name="page" value="{$page}">
</form>
{if $username neq ''}<div style="text-align:right;">{#username#}: {$username}</div>{/if}
<p>
<a href="{$smarty.const.QWIKI_DOCROOT}{#GoodStyle#}">{#GoodStyle#}</a> {#goodstyle_text#}<br>
<a href="{$smarty.const.QWIKI_DOCROOT}{#WikiSyntax#}">{#WikiSyntax#}</a> {#wikisyntax_text#}
</p>