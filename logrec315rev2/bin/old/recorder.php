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
	#!!! Config settings are modified from the install script !!!#
	class configDB extends SQLite3{
	    function __construct()
	    {
	        $this->open('../database/config.sqlite');
	    }
	}
	class callsDB extends SQLite3{
	    function __construct()
	    {
	        $this->open('../database/calls.sqlite');
	    }
	}
	class logDB extends SQLite3{
	    function __construct()
	    {
	        $this->open('../database/log.sqlite');
	    }
	}
	$configHandle = new configDB();
	date_default_timezone_set($configHandle->query("SELECT * FROM SETTINGS WHERE SETTING='date_default_timezone_set'")->fetchArray()['VALUE']);
	//Sox settings
	$config['wad'] = 1;
	#$config['wad'] = $configHandle->query("SELECT * FROM SETTINGS WHERE SETTING='wad'")->fetchArray()['VALUE'];
	//Program Settings
	$config['trunkloc'] = $configHandle->query("SELECT * FROM SETTINGS WHERE SETTING='trunkloc'")->fetchArray()['VALUE'];
	$config['callsavedir'] = $configHandle->query("SELECT * FROM SETTINGS WHERE SETTING='callsavedir'")->fetchArray()['VALUE'];
	//Email settings
	$config['globaladminemail'] = $configHandle->query("SELECT * FROM SETTINGS WHERE SETTING='globaladminemail'")->fetchArray()['VALUE'];
	$config['gmailaddr'] = $configHandle->query("SELECT * FROM SETTINGS WHERE SETTING='gmailaddr'")->fetchArray()['VALUE'];
	$config['gmailpass'] = $configHandle->query("SELECT * FROM SETTINGS WHERE SETTING='gmailpass'")->fetchArray()['VALUE'];

	require 'phpmailer/PHPMailerAutoload.php';

	function writeCG($mode,$data){
		$CGlocation = 'C:\Program Files (x86)\M.Play Home Center\CG_File\logrecorder.cg';
		$listenTemplate = "[$1]
1Line=10
2Line=18
3Line=1C
4Line=1E
5Line=1C
6Line=18
7Line=10
8Line=0
[$2]
1Line=0
2Line=0
3Line=0
4Line=0
5Line=0
6Line=0
7Line=0
8Line=0
[$3]
1Line=0
2Line=0
3Line=0
4Line=0
5Line=0
6Line=0
7Line=0
8Line=0
[$4]
1Line=0
2Line=0
3Line=0
4Line=0
5Line=0
6Line=0
7Line=0
8Line=0
[$5]
1Line=0
2Line=0
3Line=0
4Line=0
5Line=0
6Line=0
7Line=0
8Line=0
[$6]
1Line=0
2Line=0
3Line=0
4Line=0
5Line=0
6Line=0
7Line=0
8Line=0
[$7]
1Line=0
2Line=0
3Line=0
4Line=0
5Line=0
6Line=0
7Line=0
8Line=0
[$8]
1Line=0
2Line=0
3Line=0
4Line=0
5Line=0
6Line=0
7Line=0
8Line=0
[Display]

";
		$parkedTemplate = "[$1]
1Line=1F
2Line=1C
3Line=18
4Line=10
5Line=10
6Line=0
7Line=0
8Line=0
[$2]
1Line=0
2Line=0
3Line=0
4Line=0
5Line=0
6Line=0
7Line=0
8Line=0
[$3]
1Line=0
2Line=0
3Line=0
4Line=0
5Line=0
6Line=0
7Line=0
8Line=0
[$4]
1Line=1F
2Line=7
3Line=3
4Line=1
5Line=1
6Line=0
7Line=0
8Line=0
[$5]
1Line=0
2Line=0
3Line=0
4Line=10
5Line=10
6Line=18
7Line=1C
8Line=1F
[$6]
1Line=0
2Line=0
3Line=0
4Line=0
5Line=0
6Line=0
7Line=0
8Line=0
[$7]
1Line=0
2Line=0
3Line=0
4Line=0
5Line=0
6Line=0
7Line=0
8Line=0
[$8]
1Line=0
2Line=0
3Line=0
4Line=1
5Line=1
6Line=3
7Line=7
8Line=1F
[Display]
1LineText=$1$2$2ScanEYES$2$2$2$2$2$2$2$2$4
2LineText=$5$2$2$2$2$2$2Inactive$2$2$2$2$8
";
		if ($mode == "Listen") {
			if (@empty(trim($data['TGName']))) {
				$TGT = substr($data['TGTID'], 0,17);
			}else{
				$TGT = substr($data['TGName'], 0,17);
			}
			if (@empty(trim($data['RName']))) {
				$SRC = substr($data['SRCID'], 0,17);
			}else{
				$SRC = substr($data['RName'], 0,17);
			}
			$cglcd = fopen($CGlocation, 'w');
			fwrite($cglcd, $listenTemplate);
			fwrite($cglcd, "1LineText=$1G:{$TGT}
2LineText=$1R:{$SRC}");
			fclose($cglcd);
			return;
			
		}else{
			$cglcd = fopen($CGlocation, 'w');
			fwrite($cglcd, $parkedTemplate);
			fclose($cglcd);
			return;
		}
		
		

		
	}
	function sendMail($title,$body){
		global $config;
		$mail = new PHPMailer(); // create a new object
		$mail->IsSMTP(); // enable SMTP
		$mail->SMTPAuth = true; // authentication enabled
		$mail->SMTPSecure = "ssl"; // secure transfer enabled REQUIRED for GMail
		$mail->Host = "smtp.gmail.com";
		$mail->Port = 465; // or 587
		$mail->IsHTML(true);
		$mail->Username = $config['gmailaddr'];
		$mail->Password = $config['gmailpass'];
		$mail->SetFrom($config['gmailaddr']);
		$mail->WordWrap = 50; 
		$mail->isHTML(true);   
		$mail->Subject = $title;
		$mail->Body = $body;
		$mail->AddAddress($config['globaladminemail']);
		#$mail->SMTPDebug = 1;
		 if(!$mail->Send()){
			#return "Mailer Error: " . $mail->ErrorInfo;
		}else{
			#return "success";
		}
	}

	function readfil(){
		global $config;
		$file = file_get_contents($config['trunkloc']); //open log file
		list($devnull,$values) = explode("\n", $file); //discard first line of un-seful data
		list($return['action'],$return['receiver'],$return['freq'],$return['TGTID'],$return['TGName'],$return['SRCID'],$return['RName']) = explode("\t", $values); //explode values into data for call recorder (TG and SRCID)
		if ($return['action'] == "Park") {
			$return['TGTID'] = "Parked";
		}
		return $return;
	}
	function killsox(){
		#hacked no wait exec()
		#kills existing sox.exe
		@pclose(@popen("taskkill /F /IM sox.exe /T","r"));
	}
	function sanitizefs($filename){
			$invalidchars = array(">","<",":",'"',"/","\\","|","?","*");
			return str_replace($invalidchars,"",$filename);
	}
	#FUNCTIONS DONE

	#INITIALIZE CODE
	killsox(); //Kill any existing instances of SOX
	echo "LOG RECORDER by Tylerwatt12. Version 4.0.5\n\n\n\n";
	if (file_exists($config['trunkloc']) == FALSE) { // If sdrsharptrunking.log can't be found
		echo"sdrsharptrunking.log not found. Please install remote.dll into your unitrunker folder and install VC++Redist. Start your debug receiver and try again.";
		$timestamp = time();
		$logHandle = new logDB(); // Get ready to write to log
		$logHandle->busyTimeout(5000);
		$logHandle->exec("INSERT INTO 'LOG' (TIMESTAMP,TYPE,IP,USER,COMMENT) VALUES ('{$timestamp}','LOGER','127.0.0.1','LOCALHOST','LogRecorderv4 couldnt be started sdrsharptrunking.log was not found')"); // Take note of logrecorder's status
		unset($logHandle);
		exit();
	}
	sendMail("LogRecorder STARTED","<b>LogRecorder was started or restarted on: ".date("F j, Y, g:i a")."</b>
				<br><b>Trunk file location: </b>{$config['trunkloc']}
				<br><b>Call save location: </b>{$config['callsavedir']}");
	// Make log entry for logrecorder start
		$date = date("Ymd"); // Set variable for daily table creation
		$logHandle = new logDB(); // Get ready to write to log
		$timestamp = time();
		$logHandle->busyTimeout(5000);
		$logHandle->exec("INSERT INTO 'LOG' (TIMESTAMP,TYPE,IP,USER,COMMENT) VALUES ('{$timestamp}','LOGRC','127.0.0.1','LOCALHOST','LogRecorderv4 was restarted')"); // Take note of logrecorder's status
		unset($logHandle);
	// End making log entry for logrecorder start
	//Create first table
		$callsHandle = new callsDB(); // call database
		$callsHandle->busyTimeout(5000);
		$callsHandle->exec("CREATE TABLE '{$date}' (CALLID INTEGER NOT NULL, TGTID INTEGER NOT NULL, SRCID INTEGER NOT NULL, LENGTH INTEGER, COMMENT VARCHAR(300), PRIMARY KEY (CALLID)); ");
		unset($callsHandle);
	// End create first table
	sleep(1);

	$staleFile = readfil();
	$inc = 1;
	while ($inc == 1) {
		#creating variable to compare to oldtargetid
		$currentFile = readfil();
		if ($currentFile['TGTID'] != $staleFile['TGTID']){
			$staleFile = $currentFile; #set old target ID for the next loop around
			if ($currentFile['TGTID'] == 'Parked'){ #IF parked
				killsox();
				writeCG("Parked","");
				echo "\nTGT: ".sanitizefs($currentFile['TGTID']);
				echo "\nTGN: ".sanitizefs($currentFile['TGName']);
				echo "\nSRC: ".sanitizefs($currentFile['SRCID']);
				echo "\nSCN: ".sanitizefs($currentFile['RName']);
				echo "\n[WAITING]:[.........]";
			}elseif ($currentFile['action'] == 'Listen' && empty($currentFile['SRCID']) == false){	#IF TG gets changed
				killsox(); #kill previous sox instances
				writeCG("Listen",$currentFile);
				echo "\nTGT: ".sanitizefs($currentFile['TGTID']);
				echo "\nTGN: ".sanitizefs($currentFile['TGName']);
				echo "\nSRC: ".sanitizefs($currentFile['SRCID']);
				echo "\nSCN: ".sanitizefs($currentFile['RName']);
				echo "\n[.......]:[RECORDING]";
				
				$fullSavePath = $config['callsavedir'].date("Y-m-d")."/";
				if(!file_exists($fullSavePath)){ //if X:\calls\2014-05-20\ doesn't exist
					mkdir($fullSavePath); //make directory for date
				}

				$frozenTime = microtime();
				$saveFilename = substr($frozenTime, -10).substr($frozenTime, 2,6); // Gets timestamps with microseconds
				
				$statement = ""; // make empty statement for SQL
				if (date("Ymd") > @$date) { // If date has changed (current date is higher than old date)
					$date = date("Ymd"); // Update date variable
					$statement .= "CREATE TABLE '{$date}' (CALLID INTEGER NOT NULL, TGTID INTEGER NOT NULL, SRCID INTEGER NOT NULL, LENGTH INTEGER, COMMENT VARCHAR(300), PRIMARY KEY (CALLID)); "; //add create new table to SQL command
				}
				
				$statement .= "INSERT INTO '{$date}' (CALLID,TGTID,SRCID) VALUES ('{$saveFilename}','{$currentFile['TGTID']}','{$currentFile['SRCID']}'); "; // Write call to DB
				pclose(popen("start /min sox.exe -t waveaudio {$config['wad']} {$fullSavePath}{$saveFilename}.mp3","r"));
				$callsHandle = new callsDB(); // call database
				$callsHandle->busyTimeout(5000);
				$callsHandle->exec($statement);
				unset($callsHandle);
				clearstatcache();	
			}
		}elseif((filemtime($config['trunkloc'])+3600) < time()){ #if file hasn't been changed in an hour
			if (($sentTime+3600) < time()){
				sendMail("LogRecorder ERROR","<b>LogRecorder ERROR, no calls in the past hour on, date: ".date("F j, Y, g:i a")."</b>");
				$logHandle = new logDB(); // Get ready to write to log
				$timestamp = time();
				$logHandle->busyTimeout(5000);
				$logHandle->exec("INSERT INTO 'LOG' (TIMESTAMP,TYPE,IP,USER,COMMENT) VALUES ('{$timestamp}','LOGTM','127.0.0.1','LOCALHOST','LogRecorderV4 has not had any calls for an hour')"); // Take note of logrecorder's status
				unset($logHandle);
				$sentTime = time();
			}
		}
		usleep(150000); #slows down poll 150ms, increase for slower CPUs
	}
?>