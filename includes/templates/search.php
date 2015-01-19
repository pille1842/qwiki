<?php include('./includes/templates/header.php'); ?>
<h1><?php echo $logo." ".$page ?></h1>
<ol>
    <?php foreach ($index as $p) {
        echo '<li><a href="wiki.php?page='.$p.'">'.$p.'</a></li>'."\n";
    } ?>
</ol>
<hr>
<?php echo q_('pages_found_out_of', array('count' => count($index), 'total' => $total)); ?>
<?php include('./includes/templates/footer.php'); ?>
