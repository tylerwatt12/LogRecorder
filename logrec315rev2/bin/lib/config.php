<?php
	#global variables

	# MySQL must be installed, database called scaneyes315 created and table called calls created
	/**

	CREATE DATABASE scaneyes315 DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
	USE scaneyes315;
	CREATE TABLE calls (TIME varchar(20) NOT NULL, SRCID varchar(20) NOT NULL, TGTID varchar(20) NOT NULL);

	**/
	$GLOBALS['mysql_server'] = "localhost";

	$GLOBALS['username_mysql_username'] = "root";
	$GLOBALS['username_mysql_password'] = "";

	#timezone
	date_default_timezone_set('America/New_York');

	#debugging
	ini_set('display_errors', 'On');
	error_reporting(E_ALL | E_STRICT);

	//Sox settings
	$config['wad'] = 1; // no longer used is now using default recording device in windows, make sure you have the output of SDR# set as the default input device in windows
	//Program Settings
	$config['ver'] = "March-2015";
	$config['trunkloc'] = "E:/scaneyes/UniTrunker/sdrsharptrunking.log"; // location to sdrsharptrunking.log file inside the SDR# install dir.
	$config['callsavedir'] = "../../calls/"; // out of bin folder, out of logrec315rev2 folder, in calls folder
	//Email settings
	$config['globaladminemail'] = "tylerwatt12@gmail.com"; // send to this address
	$config['gmailaddr'] = "sdralert@gmail.com"; // send from this address
	$config['gmailpass'] = "abcdefg12345"; // email password for send from account



?>