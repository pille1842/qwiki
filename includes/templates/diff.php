<?php include('./includes/templates/header.php'); ?>
<h1><?php echo $logo; echo " ".q_('quickdiff_of', array('page' => $page)); ?></h1>
<?php echo $diff; ?>
<hr>
<?php include('./includes/templates/footer.php'); ?>
