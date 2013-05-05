<?php
	// Time Zone Code
	define('TIMEZONE', 'America/New_York');
	date_default_timezone_set(TIMEZONE);
	
	// Set your return content type
	header('Content-type: application/json');
	
	$host = "localhost";
	$username="root";
	$password="password";
	$database="ubicomp2013";
		
	// Website url to open
	$url = 'http://ws1.airnowgateway.org/GatewayWebServiceREST/Gateway.svc/pm25mid24hraqi';
	$_GET["airscode"] = "131210055";
	$_GET["key"] = "FF632A46-BC2C-4B1E-BC47-89F3EDFCBB87";
	$_GET["format"] = "json";
	$_GET["date"] = date('Y-m-d', time() - 60 * 60 * 24);

	$querystring = '?';
	foreach($_GET as $k=>$v) {
	    $querystring .= $k.'='.$v.'&';
	}
	$url .= substr($querystring, 0, -1); // removes the &
	
	// Get that website's content
	$handle = fopen($url, "r");

	$json = "";
	// If there is something, read and return
	if ($handle) {
		while (!feof($handle)) {
			$buffer = fgets($handle, 4096);
			$json .= $buffer;
		}
		fclose($handle);
		
		$root = json_decode( $json, true );

		mysql_connect($host, $username, $password)or die("cannot connect"); 
		mysql_select_db($database)or die("cannot select DB");

		mysql_query("SET time_zone='America/New_York';"); //Tell MySQL to offset the time it returns - INTERVAL 4 HOUR

		$successfulInserts = 0;
		// Logging class initialization
		$log = new Logging();
		// set path and name of log file (optional)
		$log->lfile('atlantaReadingsLog.txt');
		
		foreach( $root['pm25Mid24hrAQI'] as $pm25Mid24hrAQI ) {
				$dateTime = datetime::createfromformat('d-F-Y H',$pm25Mid24hrAQI[DateTimeGMT]);
				$dateTime = $dateTime->format('Y-m-d H:i:s');
				$sql="INSERT INTO atlanta_data_raw(small_particle_count,large_particle_count, timestamp)VALUES($pm25Mid24hrAQI[PM25Mid24HrUgM3], NULL, '$dateTime')";
				$result=mysql_query($sql);
				if ($result){
					$successfulInserts++;
					$log->lwrite($successfulInserts.": Successful entry for ".$dateTime);
				}
				else{
					$log->lwrite('Failed to write to database');
				}
		}
		echo $successfulInserts;
		mysql_close();
				 
		// close log file
		$log->lclose();
	}
	
		/**
	 * Logging class:
	 * - contains lfile, lwrite and lclose public methods
	 * - lfile sets path and name of log file
	 * - lwrite writes message to the log file (and implicitly opens log file)
	 * - lclose closes log file
	 * - first call of lwrite method will open log file implicitly
	 * - message is written with the following format: [d/M/Y:H:i:s] (script name) message
	 */
	class Logging {
		// declare log file and file pointer as private properties
		private $log_file, $fp;
		// set log file (path and name)
		public function lfile($path) {
			$this->log_file = $path;
		}
		// write message to the log file
		public function lwrite($message) {
			// if file pointer doesn't exist, then open log file
			if (!is_resource($this->fp)) {
				$this->lopen();
			}
			// define script name
			$script_name = pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME);
			// define current time and suppress E_WARNING if using the system TZ settings
			// (don't forget to set the INI setting date.timezone)
			$time = @date('[d/M/Y:H:i:s]');
			// write current time, script name and message to the log file
			fwrite($this->fp, "$time ($script_name) $message" . PHP_EOL);
		}
		// close log file (it's always a good idea to close a file when you're done with it)
		public function lclose() {
			fclose($this->fp);
		}
		// open log file (private method)
		private function lopen() {
			// in case of Windows set default log file
			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
				$log_file_default = 'c:/php/logfile.txt';
			}
			// set default log file for Linux and other systems
			else {
				$log_file_default = '/tmp/logfile.txt';
			}
			// define log file from lfile method or use previously set default
			$lfile = $this->log_file ? $this->log_file : $log_file_default;
			// open log file for writing only and place file pointer at the end of the file
			// (if the file does not exist, try to create it)
			$this->fp = fopen($lfile, 'a') or exit("Can't open $lfile!");
		}
	}
?>