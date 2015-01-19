<?php include('./includes/templates/header.php'); ?>
<h1><?php echo $logo ?><?php echo $page; ?></h1>
<?php if (empty($index) && isset($error)) {
    echo '<p style="color:red;">'.$error.'</p>';
} else { ?>
<ol>
    <?php foreach ($index as $p => $sn) {
        echo '<li><a href="wiki.php?page='.$p.'">'.$p.'</a><br><tt>'.$sn.'</tt></li>'."\n";
    } ?>
</ol>
<?php } ?>
<hr>
<?php echo q_('pages_found', array('count' => count($index))); ?>
<?php include('./includes/templates/footer.php'); ?>
