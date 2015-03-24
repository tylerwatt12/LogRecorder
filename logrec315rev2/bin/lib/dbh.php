<?php
	$methodScaneyes315=mysqli_connect($GLOBALS['mysql_server'],$GLOBALS['username_mysql_username'],$GLOBALS['username_mysql_password'],"scaneyes315");
	if (mysqli_connect_errno()) {
	  echo "Failed to connect to MySQL: " . mysqli_connect_error();
	  exit();
	}
?>