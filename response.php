<?php
header('Content-Type: application/json');

if ($_POST['duration'] == "day"){
	$return_array = array(
		"neighborhoodJSON" => array(
			 "location" => $_POST['location'],
			"smallParticle" => array(33, 44, 52, 26, 51, 77, 44, 26, 31, 42, 53, 29, 51, 72, 45, 26,32, 49, 50, 21, 53, 76, 43, 27),
			"bigParticle" => array(133, 144, 152, 126, 151, 177, 144, 126, 131, 142, 153, 129, 151, 172, 145, 126, 132, 149, 150, 121, 153, 176, 143, 127)
		),
		"atlantaJSON" => array(
			"smallParticle" => array(23, 31, 37, 24, 45, 70, 34, 20, 35, 38, 43, 19, 45, 67, 45, 20,22, 40, 45, 17, 45, 66, 37, 20),
			"bigParticle" => array(123, 131, 137, 124, 145, 170, 134, 120, 135, 138, 143, 119, 145, 167, 145, 120,122, 140, 145, 117, 145, 166, 137, 120)
		)
	);
}
else if ($_POST['duration'] == "week"){
	$return_array = array(
		"neighborhoodJSON" => array(
			 "location" => $_POST['location'],
			"smallParticle" => array(33, 44, 52, 26, 51, 77, 44),
			"bigParticle" => array(133, 144, 152, 126, 151, 177, 144)
		),
		"atlantaJSON" => array(
			"smallParticle" => array(23, 31, 37, 24, 45, 70, 34),
			"bigParticle" => array(123, 131, 137, 124, 145, 170, 134)
		)
	);
}
else if ($_POST['duration'] == "month"){
	$return_array = array(
		"neighborhoodJSON" => array(
			 "location" => $_POST['location'],
			"smallParticle" => array(33, 44, 52, 26, 51, 77, 44, 26, 31, 42, 53, 29, 51, 72, 45, 26,32, 49, 50, 21, 53, 76, 43, 27, 31, 42, 53, 29, 51, 72, 45),
			"bigParticle" => array(133, 144, 152, 126, 151, 177, 144, 126, 131, 142, 153, 129, 151, 172, 145, 126,132, 149, 150, 121, 153, 176, 143, 127,131, 142, 153, 129, 151, 172, 145)
		),
		"atlantaJSON" => array(
			"smallParticle" => array(23, 31, 37, 24, 45, 70, 34, 20, 35, 38, 43, 19, 45, 67, 45, 20,22, 40, 45, 17, 45, 66, 37, 20, 20, 35, 38, 43, 19, 45, 67),
			"bigParticle" => array(123, 131, 137, 124, 145, 170, 134, 120, 135, 138, 143, 119, 145, 167, 145, 120,122, 140, 145, 117, 145, 166, 137, 120,134, 120, 135, 138, 143, 119, 145)
		)
	);
}

echo json_encode($return_array);

?>
