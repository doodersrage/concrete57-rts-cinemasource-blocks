<?php
// provides redirect facilities for RTS api
if($_GET['RedirectUrl']){
	?>
    <form action='<?php echo $_GET['RedirectUrl']; ?>' method='post' name='frm'>
	<?php
    foreach ($_GET as $a => $b) {
    echo "<input type='hidden' name='".htmlentities($a)."' value='".htmlentities($b)."'>";
    }
    ?>
    </form>
    <script language="JavaScript">
    document.frm.submit();
    </script>
    <?php
}