<?php
// Time Zone Code
define('TIMEZONE', 'America/New_York');
date_default_timezone_set(TIMEZONE);
//----------------------
header('Content-Type: application/json');

	//Please add the host, username and password for your server
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
					SELECT AVG( small_particle_count ) -2 * STDDEV( small_particle_count ) AS MIN_RANGE, AVG( small_particle_count ) +2 * STDDEV( small_particle_count ) AS MAX_RANGE, HOUR(MAX( timestamp )) AS TIME_UNIT, DATE_FORMAT(DATE(MAX( timestamp )), '%e %b, %Y') AS DATE_UNIT
					FROM data_raw
					WHERE HOUR( timestamp ) = (SELECT HOUR(MAX( timestamp )) - 1 FROM data_raw )
					GROUP BY HOUR( timestamp )
				) AS TEMP_TABLE, (select small_particle_count, HOUR(MAX( timestamp )) AS TIME_UNIT, DATE_FORMAT(DATE(MAX( timestamp )), '%e %b, %Y') AS DATE_UNIT, sensorID 
					FROM data_raw
					WHERE HOUR( timestamp ) = (SELECT HOUR(MAX( timestamp )) - 1 FROM data_raw )
				) as new_data
				WHERE new_data.TIME_UNIT= TEMP_TABLE.TIME_UNIT
				and new_data.small_particle_count
				BETWEEN TEMP_TABLE.MIN_RANGE AND TEMP_TABLE.MAX_RANGE
				GROUP BY new_data.sensorID, new_data.TIME_UNIT";
	}
	else if ($particleSize == "large"){
		$sql = "SELECT  AVG(new_data.large_particle_count) AS READING, new_data.TIME_UNIT AS TIME_UNIT, new_data.DATE_UNIT AS DATE_UNIT, new_data.sensorID AS LOCATION
				FROM (
					SELECT AVG( large_particle_count ) -2 * STDDEV( large_particle_count ) AS MIN_RANGE, AVG( large_particle_count ) +2 * STDDEV( large_particle_count ) AS MAX_RANGE, HOUR(MAX( timestamp )) AS TIME_UNIT, DATE_FORMAT(DATE(MAX( timestamp )), '%e %b, %Y') AS DATE_UNIT
					FROM data_raw
					WHERE HOUR( timestamp ) = (SELECT HOUR(MAX( timestamp )) - 1 FROM data_raw )
					GROUP BY HOUR( timestamp )
				) AS TEMP_TABLE, (select large_particle_count, HOUR(MAX( timestamp )) AS TIME_UNIT, DATE_FORMAT(DATE(MAX( timestamp )), '%e %b, %Y') AS DATE_UNIT, sensorID 
					FROM data_raw
					WHERE HOUR( timestamp ) = (SELECT HOUR(MAX( timestamp )) - 1 FROM data_raw )
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
	$return_array = array(
		"neighborhoodJSON" => array(
			 "readings" => $readingArray,
			"time" => $resultTimeUnit,
			"date" => $resultDateUnit
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
					SELECT AVG( small_particle_count ) -2 * STDDEV( small_particle_count ) AS MIN_RANGE, AVG( small_particle_count ) +2 * STDDEV( small_particle_count ) AS MAX_RANGE, HOUR( timestamp ) +1 AS TIME_UNIT
					FROM data_raw
					WHERE YEAR( timestamp ) = YEAR( CURDATE( ) )
					AND MONTH( timestamp ) = MONTH( CURDATE( ) )
					AND DAY( timestamp ) = DAY( CURDATE( ))
					AND sensorID ='$sensor_id'
					GROUP BY HOUR( timestamp )
				) AS TEMP_TABLE, (select small_particle_count, HOUR( timestamp ) +1 AS TIME_UNIT from data_raw
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
					SELECT AVG( large_particle_count ) -2 * STDDEV( large_particle_count ) AS MIN_RANGE, AVG( large_particle_count ) +2 * STDDEV( large_particle_count ) AS MAX_RANGE, HOUR( timestamp ) +1 AS TIME_UNIT
					FROM data_raw
					WHERE YEAR( timestamp ) = YEAR( CURDATE( ) )
					AND MONTH( timestamp ) = MONTH( CURDATE( ) )
					AND DAY( timestamp ) = DAY( CURDATE( ))
					AND sensorID ='$sensor_id'
					GROUP BY HOUR( timestamp )
				) AS TEMP_TABLE, (select large_particle_count, HOUR( timestamp ) +1 AS TIME_UNIT from data_raw
					WHERE YEAR( timestamp ) = YEAR( CURDATE( ) )
					AND MONTH( timestamp ) = MONTH( CURDATE( ) )
					AND DAY( timestamp ) = DAY( CURDATE( ))
					AND sensorID ='$sensor_id'
				) as new_data
				WHERE new_data.TIME_UNIT= TEMP_TABLE.TIME_UNIT
				and new_data.large_particle_count
				BETWEEN TEMP_TABLE.MIN_RANGE AND TEMP_TABLE.MAX_RANGE
				GROUP BY new_data.TIME_UNIT";
		$smallParticleArray = array_fill(1, 24, '0');
		$largeParticleArray = array_fill(1, 24, '0');
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
				
		$smallParticleArray = array_fill(1, 7, '0');
		$largeParticleArray = array_fill(1, 7, '0');
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
				
		$smallParticleArray = array_fill(1, 31, '0');
		$largeParticleArray = array_fill(1, 31, '0');
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
	
	
	$m25 = 5.89*pow(10,-7);
	$m10 = 1.21*pow(10,-4);

    for ($i = 1; $i <= count($smallParticleArray); ++$i) {
		$smallParticleArray[$i] = round($smallParticleArray[$i]*3531.5*$m25);
		$largeParticleArray[$i] = round($largeParticleArray[$i]*3531.5*$m10);
    }
	if ($time_range == "day"){
		$atlantaSmallParticleArray = array (23, 31, 37, 24, 39, 51, 34, 23, 31, 30, 24, 45, 54, 34, 23, 31, 37, 24, 45, 57, 34, 42, 39, 40);
		$atlantaBigParticleArray = array (123, 131, 127, 124, 145, 150, 120, 123, 131, 135, 124, 140, 150, 120, 123, 131, 137, 124, 145, 150, 120, 113, 134, 142);	
	}
	else if ($time_range == "week"){
		$atlantaSmallParticleArray = array (23, 31, 37, 24, 45, 70, 34);
		$atlantaBigParticleArray = array (123, 131, 137, 124, 145, 150, 120);	
	}
	else if ($time_range == "month"){
		$atlantaSmallParticleArray = array (23, 31, 37, 24, 45, 70, 34, 23, 31, 37, 24, 45, 51, 34, 23, 31, 37, 24, 45, 54, 34, 23, 31, 37, 24, 45, 57, 34, 42, 39, 40);
		$atlantaBigParticleArray = array (123, 131, 130, 124, 145, 150, 120, 123, 131, 117, 124, 145, 150, 120, 123, 131, 137, 124, 145, 150, 120, 123, 131, 137, 124, 145, 150, 120, 113, 134, 142);	
	}

	$return_array = array(
		"neighborhoodJSON" => array(
			 "location" => $_POST['location'],
			"smallParticle" => $smallParticleArray,
			"bigParticle" => $largeParticleArray
		),
		"atlantaJSON" => array(
			"smallParticle" => $atlantaSmallParticleArray,
			"bigParticle" => $atlantaBigParticleArray
		)
	);
	echo json_encode($return_array);
}
?>
