<?php include('./includes/templates/header.php'); ?>
<h1><?php echo $logo." ".$page ?></h1>
<?php echo q_('attention_visitors'); ?>
<?php if (isset($preview) && $preview != '') {
    echo "<h2>Vorschau</h2>\n";
    echo $preview."\n";
    echo "<hr>\n";
} ?>
<form method="POST" action="edit.php">
    <textarea name="edittext" rows="25" cols="115"><?php echo $text ?></textarea><br>
    <input type="submit" name="save" value="<?php echo q_('save'); ?>">
    <input type="submit" name="preview" value="<?php echo q_('preview'); ?>">
    <input type="hidden" name="page" value="<?php echo $page ?>">
</form>

<p>
<?php echo q_('goodstyle_link'); ?><br>
<?php echo q_('wikisyntax_link'); ?>
</p>
<?php include('./includes/templates/footer.php'); ?>
