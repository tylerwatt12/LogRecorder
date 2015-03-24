<?php
	/*
	Tylerwatt12's logrecorder
	This program reads sdrsharptrunking.log and spawns a recording program if an active call is found
	It loop reads a file for changes, and if a file change is found, sox.exe is loaded
	Settings in this file include, location for sox, WaveAudioDevice (which recording device to use)
	Sample rate, location to sdrsharptrunking.log, your timezone, where calls should be saved,
	Which year to create folders up until (the init part of this script creates a folder for every day month and year until the date you specify
	Which email account to send alerts to, alerts are sent when log recorder is restarted, or if a call hasn't been received in an hour (configured in seconds)
	Also configurable is the SMTP server, username, password, and encryption method to use.
	This file also optionally hooks into the ScanEyes master log writer so there are config options for that too.
	*/
	include 'lib/config.php'; //start session, set global vars, enable debugging
	include 'lib/dbh.php'; //call db handler
	include 'lib/functions.php'; // call functions for getting/putting data
	require 'phpmailer/PHPMailerAutoload.php';
	
	killsox();
	fill(5);
	initscreen();
	checktrunkloc();
	sleep(2);
	$cip = FALSE;
	$prev = readfil(); // set 
	fill(3);
	echo "If you need to edit configuration\n";
	echo "open bin/lib/config.php\n";
	sleep(2);
	echo date('Y-m-d H:i:s').": waiting for first call";
	while (1) {
		usleep(100000); // slow down loop so PC won't freeze up
		$curr = readfil(); // open sdrsharptrunking.log
		if ($curr['action'] == "Listen") { // if sdrsharp is currently tuned

			// tell whether or not to spawn a new recorder, check a flag to see if $cip is set
			if ($cip == FALSE) { // if cip is false, then THIS IS A NEW CALL, SPAWN RECORDER AND SET FLAG

				checkfoldercreate($curr['TGTID'],$curr['SRCID']);
				$TIME = filetime();
				startrec($TIME,$curr['TGTID'],$curr['SRCID']);
				dbaddcall($TIME,$curr['TGTID'],$curr['SRCID']);
				programdisplay($curr);

				$cip = TRUE; // call in progress!
				$prev = $curr; // copy variable value for use in next loop to compare if call has changed

			}elseif($cip == TRUE){

				if ($prev !== $curr) { // if file contents have changed while recording

					killsox(); // end old call, kill sox

					checkfoldercreate($curr['TGTID'],$curr['SRCID']);
					$TIME = filetime();
					startrec($TIME,$curr['TGTID'],$curr['SRCID']);
					dbaddcall($TIME,$curr['TGTID'],$curr['SRCID']);
					programdisplay($curr);

					$prev = $curr; // copy variable value for use in next loop to compare if call has changed
					$cip = TRUE; // call in progress!
				}else{
					// the call is the same one as before, leave this alone. (lots of loops here)
				}

			}

		}elseif ($curr['action'] == "Park") { // if sdrsharp is not tuned

			if ($cip == TRUE) { // if call was in progress(cip var) and has ended(sdrsharptrunking says parked), kill sox
				killsox(); // call may have ended, kill sox
				programdisplay($curr);// display screen with last call info
			}
			

			$cip = FALSE;
		}
		
	}
?>