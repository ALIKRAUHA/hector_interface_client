<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<link rel="stylesheet" href="menu.css">
    <link rel="stylesheet" media="screen and (max-width: 1050px)"
          href="smallscreen.css" type="text/css" />
	<title>Hector - Kidslab</title>
</head>
<body>
	
<section class="petit">
<?php

include_once('parts/language.php');

$languageid = $_GET['language'] ?? null;
if($languageid === null) {
	include_once('parts/languagePage.php');
} else {
	$language = $languages[$languageid];
	if(isset($_GET['saved'])) {
		?>
		<p><?=$language['saved']?> <br> <br> <?=$language['kidslab']?> </p>
		<a href="index.php"><?=$language['home']?></a>
		<?php
	} else {
		include_once('parts/viewPage.php');
	}
}

?>

</section>

</body>
<footer>
    <img src="Logo/LogosHector.png" alt="Logo des sponsors" />
</footer>
</html>