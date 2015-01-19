<?php include('./includes/templates/header.php'); ?>
<h1><?php echo $logo ?><a href="fullsearch.php?term=<?php echo $page ?>"><?php echo expand_camelcase($page) ?></a></h1>
<?php echo get_page_html($page) ?>
<hr>
<?php if (file_exists($config['pages_dir'].$page.'.txt')) {
    $modified = filemtime($config['pages_dir'].$page.'.txt');
} else {
    $modified = '0';
}
echo q_('footnote', array('page' => $page, 'modified' => strftime("%c", $modified))); ?>
<?php include('./includes/templates/footer.php'); ?>
