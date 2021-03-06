<?php


// Time Zone Code
define('TIMEZONE', 'America/New_York');
date_default_timezone_set(TIMEZONE);
//----------------------
header('Content-Type: application/json');

	//please add details of your database hosting
	$host = "";
	$username="";
	$password="";
	$database="ubicomp2013";

if(isset($_POST['action']) && !empty($_POST['action'])) {
    $action = $_POST['action'];
    switch($action) {
        case 'getNeighborhoodReading' : getNeighborhoodReading($host, $username, $password, $database, $_POST['duration'],$_POST['location']);break;
        case 'getLatestReadings' : getLatestReadings($host, $username, $password, $database, $_POST['particleSize']);break;
    }
}


function getLatestReadings($host, $username, $password, $database, $particleSize){
		
	mysql_connect($host, $username, $password)or die("cannot connect"); 
	mysql_select_db($database)or die("cannot select DB");

	mysql_query("SET time_zone='America/New_York';"); //Tell MySQL to offset the time it returns
	
	if ($particleSize == "small"){
		$sql = "SELECT  AVG(new_data.small_particle_count) AS READING, new_data.TIME_UNIT AS TIME_UNIT, new_data.DATE_UNIT AS DATE_UNIT, new_data.sensorID AS LOCATION
				FROM (
					SELECT AVG( small_particle_count ) -2 * STDDEV( small_particle_count ) AS MIN_RANGE, AVG( small_particle_count ) +2 * STDDEV( small_particle_count ) AS MAX_RANGE, HOUR(MAX( timestamp )) AS TIME_UNIT, DATE_FORMAT(DATE(MAX( timestamp )), '%e %b, %Y') AS DATE_UNIT,  sensorID
					FROM data_raw
					WHERE HOUR( timestamp ) = (SELECT HOUR(MAX( timestamp )) - 1 FROM data_raw )
					GROUP BY HOUR( timestamp ), sensorID
					) AS TEMP_TABLE, (SELECT small_particle_count, HOUR( timestamp ) AS TIME_UNIT, DATE_FORMAT( DATE( timestamp ) , '%e %b, %Y' ) AS DATE_UNIT, sensorID
					FROM data_raw
					WHERE HOUR( timestamp ) = (
					SELECT HOUR( MAX( timestamp ) ) -1
					FROM data_raw ) 
					AND DATE( timestamp ) = (
					SELECT DATE( MAX( timestamp ) )
					FROM data_raw ) 
				) as new_data
				WHERE new_data.TIME_UNIT= TEMP_TABLE.TIME_UNIT
				and new_data.small_particle_count
				BETWEEN TEMP_TABLE.MIN_RANGE AND TEMP_TABLE.MAX_RANGE
				GROUP BY new_data.sensorID, new_data.TIME_UNIT";
	}
	else if ($particleSize == "large"){
		$sql = "SELECT  AVG(new_data.large_particle_count) AS READING, new_data.TIME_UNIT AS TIME_UNIT, new_data.DATE_UNIT AS DATE_UNIT, new_data.sensorID AS LOCATION
				FROM (
					SELECT AVG( large_particle_count ) -2 * STDDEV( large_particle_count ) AS MIN_RANGE, AVG( large_particle_count ) +2 * STDDEV( large_particle_count ) AS MAX_RANGE, HOUR(MAX( timestamp )) AS TIME_UNIT, DATE_FORMAT(DATE(MAX( timestamp )), '%e %b, %Y') AS DATE_UNIT,  sensorID
					FROM data_raw
					WHERE HOUR( timestamp ) = (SELECT HOUR(MAX( timestamp )) - 1 FROM data_raw )
					GROUP BY HOUR( timestamp ), sensorID
					) AS TEMP_TABLE, (SELECT large_particle_count, HOUR( timestamp ) AS TIME_UNIT, DATE_FORMAT( DATE( timestamp ) , '%e %b, %Y' ) AS DATE_UNIT, sensorID
					FROM data_raw
					WHERE HOUR( timestamp ) = (
					SELECT HOUR( MAX( timestamp ) ) -1
					FROM data_raw ) 
					AND DATE( timestamp ) = (
					SELECT DATE( MAX( timestamp ) )
					FROM data_raw ) 
				) as new_data
				WHERE new_data.TIME_UNIT= TEMP_TABLE.TIME_UNIT
				and new_data.large_particle_count
				BETWEEN TEMP_TABLE.MIN_RANGE AND TEMP_TABLE.MAX_RANGE
				GROUP BY new_data.sensorID, new_data.TIME_UNIT";
	}
	$result = mysql_query($sql);
	if (false === $result) {
		echo mysql_error();
	}
	
	$readingArray = array();
	$resultTimeUnit = 0;
	$resultDateUnit = 0;
	
	$m25 = 5.89*pow(10,-7);
	$m10 = 1.21*pow(10,-4);
	
	while($row = mysql_fetch_array($result))
	{
		if ($particleSize == "small"){
			$readingArray[$row['LOCATION']] = round($row['READING']*3531.5*$m25) ;
		}
		else if ($particleSize == "large"){
			$readingArray[$row['LOCATION']] = round($row['READING']*3531.5*$m10) ;
		}
		$resultTimeUnit = $row['TIME_UNIT'];
		$resultDateUnit = $row['DATE_UNIT'];
	}
	
	$atlantaSQL = "SELECT small_particle_count AS READING, 
				HOUR( MAX( timestamp ) ) AS TIME_UNIT, 
				DATE_FORMAT( DATE( MAX( timestamp ) ) , '%e %b, %Y' ) AS DATE_UNIT
				FROM atlanta_data_raw
				WHERE HOUR( timestamp ) = (
				SELECT HOUR( MAX( timestamp ) )
				FROM atlanta_data_raw )
				AND DATE( timestamp ) = (
				SELECT DATE( MAX( timestamp ) )
				FROM atlanta_data_raw ) ";
	$atlantaResult = mysql_query($atlantaSQL);
	$atlantaReading = 0;
	$atlantaResultTimeUnit = 0;
	$atlantaResultDateUnit = 0;
	
	while($row = mysql_fetch_array($atlantaResult))
	{
		$atlantaReading = $row['READING'];
		$atlantaResultTimeUnit =$row['TIME_UNIT'];
		$atlantaResultDateUnit = $row['DATE_UNIT'];
	}
	$return_array = array(
		"neighborhoodJSON" => array(
			 "readings" => $readingArray,
			"time" => $resultTimeUnit,
			"date" => $resultDateUnit
		),
		"atlantaJSON" => array(
			 "readings" => $atlantaReading,
			"time" => $atlantaResultTimeUnit,
			"date" => $atlantaResultDateUnit
		)		
	);
	echo json_encode($return_array);
}

function getNeighborhoodReading($host, $username, $password, $database, $time_range, $sensor_id){
	
	mysql_connect($host, $username, $password)or die("cannot connect"); 
	mysql_select_db($database)or die("cannot select DB");

	mysql_query("SET time_zone='America/New_York';"); //Tell MySQL to offset the time it returns
	
	if ($time_range == "day"){
		$sqlSmallParticle = " SELECT  AVG(new_data.small_particle_count) AS SMALL_PARTICLE, new_data.TIME_UNIT
				FROM (
					SELECT AVG( small_particle_count ) -2 * STDDEV( small_particle_count ) AS MIN_RANGE, AVG( small_particle_count ) +2 * STDDEV( small_particle_count ) AS MAX_RANGE, HOUR( timestamp ) AS TIME_UNIT
					FROM data_raw
					WHERE YEAR( timestamp ) = YEAR( CURDATE( ) )
					AND MONTH( timestamp ) = MONTH( CURDATE( ) )
					AND DAY( timestamp ) = DAY( CURDATE( ))
					AND sensorID ='$sensor_id'
					GROUP BY HOUR( timestamp )
				) AS TEMP_TABLE, (select small_particle_count, HOUR( timestamp ) AS TIME_UNIT from data_raw
					WHERE YEAR( timestamp ) = YEAR( CURDATE( ) )
					AND MONTH( timestamp ) = MONTH( CURDATE( ) )
					AND DAY( timestamp ) = DAY( CURDATE( ))
					AND sensorID ='$sensor_id'
				) as new_data
				WHERE new_data.TIME_UNIT= TEMP_TABLE.TIME_UNIT
				and new_data.small_particle_count
				BETWEEN TEMP_TABLE.MIN_RANGE AND TEMP_TABLE.MAX_RANGE
				GROUP BY new_data.TIME_UNIT";

		$sqlLargeParticle = " SELECT  AVG(new_data.large_particle_count) AS LARGE_PARTICLE, new_data.TIME_UNIT
				FROM (
					SELECT AVG( large_particle_count ) -2 * STDDEV( large_particle_count ) AS MIN_RANGE, AVG( large_particle_count ) +2 * STDDEV( large_particle_count ) AS MAX_RANGE, HOUR( timestamp ) AS TIME_UNIT
					FROM data_raw
					WHERE YEAR( timestamp ) = YEAR( CURDATE( ) )
					AND MONTH( timestamp ) = MONTH( CURDATE( ) )
					AND DAY( timestamp ) = DAY( CURDATE( ))
					AND sensorID ='$sensor_id'
					GROUP BY HOUR( timestamp )
				) AS TEMP_TABLE, (select large_particle_count, HOUR( timestamp ) AS TIME_UNIT from data_raw
					WHERE YEAR( timestamp ) = YEAR( CURDATE( ) )
					AND MONTH( timestamp ) = MONTH( CURDATE( ) )
					AND DAY( timestamp ) = DAY( CURDATE( ))
					AND sensorID ='$sensor_id'
				) as new_data
				WHERE new_data.TIME_UNIT= TEMP_TABLE.TIME_UNIT
				and new_data.large_particle_count
				BETWEEN TEMP_TABLE.MIN_RANGE AND TEMP_TABLE.MAX_RANGE
				GROUP BY new_data.TIME_UNIT";
				
		$atlantaSQL = "SELECT small_particle_count AS READING, HOUR( timestamp ) + 1 AS TIME_UNIT
					FROM `atlanta_data_raw`
					WHERE YEAR( timestamp ) = YEAR( CURDATE( ) )
					AND MONTH( timestamp ) = MONTH( CURDATE( ) )
					AND DAY( timestamp ) = DAY( CURDATE( ) )";
		
		$smallParticleArray = array_fill(0, 24, 0);
		$largeParticleArray = array_fill(0, 24, 0);
		$atlantaParticleArray = array_fill(0, 24, 0);
	}
	else if ($time_range == "week"){
		$sqlSmallParticle = " SELECT  AVG(new_data.small_particle_count) AS SMALL_PARTICLE, new_data.TIME_UNIT
				FROM (
					SELECT AVG( small_particle_count ) -2 * STDDEV( small_particle_count ) AS MIN_RANGE, AVG( small_particle_count ) +2 * STDDEV( small_particle_count ) AS MAX_RANGE,  DAYOFWEEK(timestamp) AS TIME_UNIT
					FROM data_raw
					WHERE YEAR( timestamp ) = YEAR( CURDATE( ) )
					AND WEEK( timestamp ) = WEEK( CURDATE( ) )
					AND sensorID = '$sensor_id'
					GROUP BY DATE( timestamp )
				) AS TEMP_TABLE, (select small_particle_count, DAYOFWEEK(timestamp) AS TIME_UNIT
					FROM data_raw
					WHERE YEAR( timestamp ) = YEAR( CURDATE( ) )
					AND WEEK( timestamp ) = WEEK( CURDATE( ) )
					AND sensorID = '$sensor_id'
				) as new_data
				WHERE new_data.TIME_UNIT= TEMP_TABLE.TIME_UNIT
				and new_data.small_particle_count
				BETWEEN TEMP_TABLE.MIN_RANGE AND TEMP_TABLE.MAX_RANGE
				GROUP BY new_data.TIME_UNIT";
	
		$sqlLargeParticle = " SELECT  AVG(new_data.large_particle_count) AS LARGE_PARTICLE, new_data.TIME_UNIT
				FROM (
					SELECT AVG( large_particle_count ) -2 * STDDEV( large_particle_count ) AS MIN_RANGE, AVG( large_particle_count ) +2 * STDDEV( large_particle_count ) AS MAX_RANGE,  DAYOFWEEK(timestamp) AS TIME_UNIT
					FROM data_raw
					WHERE YEAR( timestamp ) = YEAR( CURDATE( ) )
					AND WEEK( timestamp ) = WEEK( CURDATE( ) )
					AND sensorID = '$sensor_id'
					GROUP BY DATE( timestamp )
				) AS TEMP_TABLE, (select large_particle_count, DAYOFWEEK(timestamp) AS TIME_UNIT
					FROM data_raw
					WHERE YEAR( timestamp ) = YEAR( CURDATE( ) )
					AND WEEK( timestamp ) = WEEK( CURDATE( ) )
					AND sensorID = '$sensor_id'
				) as new_data
				WHERE new_data.TIME_UNIT= TEMP_TABLE.TIME_UNIT
				and new_data.large_particle_count
				BETWEEN TEMP_TABLE.MIN_RANGE AND TEMP_TABLE.MAX_RANGE
				GROUP BY new_data.TIME_UNIT";

		$atlantaSQL = "SELECT AVG( small_particle_count ) AS READING, DAYOFWEEK( timestamp ) AS TIME_UNIT
				FROM `atlanta_data_raw`
				WHERE YEAR( timestamp ) = YEAR( CURDATE( ) )
				AND WEEK( timestamp ) = WEEK( CURDATE( ) )
				GROUP BY DAYOFWEEK( timestamp )";
			
		$smallParticleArray = array_fill(1, 7, 0);
		$largeParticleArray = array_fill(1, 7, 0);
		$atlantaParticleArray = array_fill(1, 7, 0);
	}
	else if ($time_range == "month"){
		$sqlSmallParticle = "SELECT  AVG(new_data.small_particle_count) AS SMALL_PARTICLE, new_data.TIME_UNIT
				FROM (
					SELECT AVG( small_particle_count ) -2 * STDDEV( small_particle_count ) AS MIN_RANGE, AVG( small_particle_count ) +2 * STDDEV( small_particle_count ) AS MAX_RANGE,  DAY(timestamp) AS TIME_UNIT
					FROM data_raw
					WHERE YEAR(timestamp) = YEAR(CURDATE()) 
					AND MONTH(timestamp) = MONTH(CURDATE())
					AND sensorID = '$sensor_id'
					GROUP BY DATE( timestamp )
				) AS TEMP_TABLE, (select small_particle_count, DAY(timestamp) AS TIME_UNIT
					FROM data_raw
					WHERE YEAR(timestamp) = YEAR(CURDATE()) 
					AND MONTH(timestamp) = MONTH(CURDATE())
					AND sensorID = '$sensor_id'
				) as new_data
				WHERE new_data.TIME_UNIT= TEMP_TABLE.TIME_UNIT
				and new_data.small_particle_count
				BETWEEN TEMP_TABLE.MIN_RANGE AND TEMP_TABLE.MAX_RANGE
				GROUP BY new_data.TIME_UNIT";

		$sqlLargeParticle = "SELECT  AVG(new_data.large_particle_count) AS LARGE_PARTICLE, new_data.TIME_UNIT
				FROM (
					SELECT AVG( large_particle_count ) -2 * STDDEV( large_particle_count ) AS MIN_RANGE, AVG( large_particle_count ) +2 * STDDEV( large_particle_count ) AS MAX_RANGE,  DAY(timestamp) AS TIME_UNIT
					FROM data_raw
					WHERE YEAR(timestamp) = YEAR(CURDATE()) 
					AND MONTH(timestamp) = MONTH(CURDATE())
					AND sensorID = '$sensor_id'
					GROUP BY DATE( timestamp )
				) AS TEMP_TABLE, (select large_particle_count, DAY(timestamp) AS TIME_UNIT
					FROM data_raw
					WHERE YEAR(timestamp) = YEAR(CURDATE()) 
					AND MONTH(timestamp) = MONTH(CURDATE())
					AND sensorID = '$sensor_id'
				) as new_data
				WHERE new_data.TIME_UNIT= TEMP_TABLE.TIME_UNIT
				and new_data.large_particle_count
				BETWEEN TEMP_TABLE.MIN_RANGE AND TEMP_TABLE.MAX_RANGE
				GROUP BY new_data.TIME_UNIT";
		
		$atlantaSQL = "SELECT AVG( small_particle_count ) AS READING, DAY(timestamp) AS TIME_UNIT
					FROM `atlanta_data_raw`
					WHERE YEAR(timestamp) = YEAR(CURDATE()) 
					AND MONTH(timestamp) = MONTH(CURDATE())
					GROUP BY DAY(timestamp)";
		
		$smallParticleArray = array_fill(1, 31, 0);
		$largeParticleArray = array_fill(1, 31, 0);
		$atlantaParticleArray = array_fill(1, 31, 0);
	}
	$smallParticleResult = mysql_query($sqlSmallParticle);
	if (false === $smallParticleResult) {
		echo mysql_error();
	}
	while($row = mysql_fetch_array($smallParticleResult))
	{
	$smallParticleArray[intval($row['TIME_UNIT'])] = $row['SMALL_PARTICLE'];
	}

	$largeParticleResult = mysql_query($sqlLargeParticle);
	if (false === $largeParticleResult) {
		echo mysql_error();
	}
	while($row = mysql_fetch_array($largeParticleResult))
	{
	$largeParticleArray[$row['TIME_UNIT']] = $row['LARGE_PARTICLE'];
	}
	
	$m25 = 3531.5*5.89*pow(10,-7);
	$m10 = 3531.5*1.21*pow(10,-4);

	$newSmallParticleArray = array();	
	foreach ($smallParticleArray as $value) {
		$newSmallParticleArray[] = $value*$m25;
	}

	$newLargeParticleArray = array();	
	foreach ($largeParticleArray as $value) {
		$newLargeParticleArray[] = $value *$m10;
	}

	$atlantaResult = mysql_query($atlantaSQL);
	while($row = mysql_fetch_array($atlantaResult))
	{
	$atlantaParticleArray[$row['TIME_UNIT']] = intval($row['READING']);
	}

	$return_array = array(
		"neighborhoodJSON" => array(
			 "location" => $_POST['location'],
			"smallParticle" => $newSmallParticleArray,
			"bigParticle" => $newLargeParticleArray
		),
		"atlantaJSON" => array(
			"reading" => $atlantaParticleArray
		)
	);
	echo json_encode($return_array);
}
?>
