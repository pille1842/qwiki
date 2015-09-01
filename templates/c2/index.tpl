{config_load file=$conffile}
<!DOCTYPE html>
<html lang="{$smarty.const.QWIKI_LANGUAGE}">
<head>
<meta charset="UTF-8">
<title>{$title}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<meta name="generator" content="Qwiki/{$smarty.const.QWIKI_VERSION}">
<link href="{$smarty.const.QWIKI_URL_TEMPLATE}css/main.css" rel="stylesheet" type="text/css">
</head>
<body>
{include file=$template}
</body>
</html>
