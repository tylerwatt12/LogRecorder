<?php
/**
##################################
# 	LOW LEVEL FUNCTIONS
##################################
**/
	function sendMail($title,$body){
		# this function allows email communication to the administrator of the server about logrecorder status

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
	function sanitizefs($filename){
		# this function removes any illegal characters that windows doesn't accept

		$invalidchars = array(">","<",":",'"',"/","\\","|","?","*");
		return str_replace($invalidchars,"",$filename);
	}
	function filetime(){
		// gets unix timestamp with microcseconds

		$frozenTime = microtime();
		$output = substr($frozenTime, -10).substr($frozenTime, 2,6); // Gets timestamps with microseconds
		return $output;
	}
	function readfil(){
		# this function opens the sdrsharptrunking.log file to read the current radio channel

		global $config;
		$file = file_get_contents($config['trunkloc']); //open log file
		list($devnull,$values) = explode("\n", $file); //discard first line of un-seful data
		@list($return['action'],$return['receiver'],$return['freq'],$return['TGTID'],$return['TGTNAME'],$return['SRCID'],$return['SRCNAME']) = explode("\t", $values); //explode values into data for call recorder (TG and SRCID)
		
		unset($return['freq']); // drop unrelevant data
		unset($return['receiver']); // drop unrelevant data

		return $return;
	}
	function dbaddcall($TIME,$TGTID,$SRCID){
		global $methodScaneyes315;
		global $config;
		# this function writes the call to the database
		$query = "INSERT INTO calls (TIME,SRCID,TGTID) VALUES ('{$TIME}','{$SRCID}','{$TGTID}');";
		# execute the query.
		$result = $methodScaneyes315->query($query) or die("Error in the consult.." . mysqli_error($methodScaneyes315));

	}
	function startrec($TIME,$TGTID,$SRCID){
		global $config;
		# this function starts the sox recording process

		# Note: {$config['wad']} was replaced with the default windows audio recorder, this keeps things simple, configure in UI vs CLI
		$fullSavePath = $config['callsavedir'].$TGTID."/".$SRCID."/".date("Y-m-d")."/";
		pclose(popen("start /min sox.exe -t waveaudio default {$fullSavePath}{$TIME}.mp3","r"));
	}
	function killsox(){
		# this function kills any instance of sox to end recording of mp3

		#hacked no wait exec()
		@pclose(@popen("taskkill /F /IM sox.exe /T","r"));
		fill(5); // Clear screen in case there are no instances of sox and cmd throws error.
	}
	function checkfoldercreate($TGTID,$SRCID){
		# this function checks if the proper folders exist before sox starts recording to them

		global $config;

		$formatDate = date("Y-m-d");

		if (is_dir($config['callsavedir'].$TGTID."/") == FALSE) { // if target dir isn't there, make target
			mkdir($basedir2.$TGTID."/");
		}
		if (is_dir($config['callsavedir'].$TGTID."/".$SRCID."/") == FALSE) {
				mkdir($config['callsavedir'].$TGTID."/".$SRCID."/");	
		}
		if (is_dir($config['callsavedir'].$TGTID."/".$SRCID."/".$formatDate."/") == FALSE) {
					mkdir($config['callsavedir'].$TGTID."/".$SRCID."/".$formatDate."/");
		}

	}
/**
##################################
# 	HIGH and UI LEVEL FUNCTIONS
##################################
**/
	function fill($int){
		# this function makes blank lines
		$i = 0;
		while ($i < $int) {
			echo "\n";
			$i++;
		}	
	}
	function initscreen(){
		global $config;
		# startup screen action
			echo "LOG RECORDER by Tylerwatt12. Version ".$config['ver'];	
			fill(4);
	}
	function checktrunkloc(){
		global $config;

		if (file_exists($config['trunkloc']) == FALSE) { // If sdrsharptrunking.log can't be found
			echo"sdrsharptrunking.log not found. Please install remote.dll into your unitrunker folder and install VC++Redist. Start your debug receiver and try again.";
			exit();
		}
	}
	function programdisplay($curr){
		if ($curr["action"] == "Park") {
			echo "\nLast call was at";
			echo "\nDAT: ".date('Y-m-d H:i:s');
			echo "\n";
			echo "\n";
			echo "\n[WAITING]:[.........]";
		}elseif ($curr["action"] == "Listen") {
			echo "\nTGT: ".sanitizefs($curr['TGTID']);
			echo "\nTGN: ".sanitizefs($curr['TGTNAME']);
			echo "\nSRC: ".sanitizefs($curr['SRCID']);
			echo "\nSCN: ".sanitizefs($curr['SRCNAME']);
			echo "\n[.......]:[RECORDING]";
		}
	}
	

?>