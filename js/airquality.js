$(document).ready(function(){
				
	
	/*code for the 2nd part of the page starts*/
	var currLocation = '2';
	var currDuration = 'day';
	getReadings(currLocation, currDuration);	
	getLatestReadings("small");
	
	$(".duration").bind('click', function(){
		$(".duration").each(function() {
			$(this).removeClass("active");
		});
		$(this).addClass("active");
		if(this.id == "duration-day"){
			currDuration = "day";
		}
		else if(this.id == "duration-week"){
			currDuration = "week";
		}
		else if(this.id == "duration-month"){
			currDuration = "month";
		}
		getReadings(currLocation, currDuration);
	});
	
	$(".location").bind('click', function(){
		$(".location").each(function() {
			$(this).removeClass("active");
		});
		$(this).addClass("active");
		if(this.id == "location1"){
			currLocation = "1";
		}
		else if(this.id == "location2"){
			currLocation = "2";
		}
		else if(this.id == "location3"){
			currLocation = "3";
		}
		getReadings(currLocation, currDuration);
	});
});

function getLatestReadings(particleSize){
	var readingSum = 0;
	$.ajax({
    type: 'POST',
    url: "response.php",
    data: {action: "getLatestReadings", particleSize: particleSize},
    dataType:"json",
	async : false
    }).done(function(data) 
		{
		console.log(data);
		$('.readings').css("background-color", "#82D694");
		$('.numeric-value').css("color", "#82D694");
		if (typeof data.neighborhoodJSON.readings["1"] !== "undefined"){
			console.log("1");
			$("#community").show().html(data.neighborhoodJSON.readings["1"]);
			readingSum += parseInt(data.neighborhoodJSON.readings["1"]);
		}
		if (typeof data.neighborhoodJSON.readings["2"] !== "undefined"){
			console.log("2");
			$("#boulevard").show().html(data.neighborhoodJSON.readings["2"]);
			readingSum += parseInt(data.neighborhoodJSON.readings["2"]);
		}
		if (typeof data.neighborhoodJSON.readings["3"] !== "undefined"){
			console.log("3");
			$("#railyard").show().html(data.neighborhoodJSON.readings["3"]);
			readingSum += parseInt(data.neighborhoodJSON.readings["3"]);
		}
	
		$("#cabbagetown").html(readingSum / Object.keys(data.neighborhoodJSON.readings).length );

		$( ".readings" ).each(function( index ) {
			if ((parseInt($(this).html()) >=35 && particleSize == "small") || (parseInt($(this).html()) >=150 && particleSize == "large"))
				$(this).css("background-color", "#FC858D");
		});
		$( ".numeric-value" ).each(function( index ) {
			if ((parseInt($(this).html()) >=35 && particleSize == "small") || (parseInt($(this).html()) >=150 && particleSize == "large"))
				$(this).css("color", "#FC858D");
		});
		$("#cabbagetown-current-time").html(data.neighborhoodJSON.time+":00 on "+data.neighborhoodJSON.date);
		}
	);
	$(".toggleRange").removeClass("active");
	if(particleSize == "small")
		$("#pm25switch").addClass("active");
	else
		$("#pm10switch").addClass("active");
}

function getReadings(location, duration){
	var JSONData = {};
	$.ajax({
    type: 'POST',
    url: "response.php",
    data: {action: "getNeighborhoodReading", location: location, duration: duration},
    dataType:"json",
    async: false
  }).done(function(data) {
	JSONData = data;
  });
  
  if(duration == "day"){
	JSONData.smallStandardVal = [35,35,35,35,35,35,35,35,35,35,35,35,35,35,35,35,35,35,35,35,35,35,35,35];
	JSONData.bigStandardVal = [150,150,150,150,150,150,150,150,150,150,150,150,150,150,150,150,150,150,150,150,150,150,150,150];
	JSONData.xAxisScale = ['1am', '2am', '3am', '4am', '5am', '6am', '7am', '8am', '9am', '10am', '11am', '12pm', '1pm', '2pm', '3pm', '4pm', '5pm', '6pm', '7pm', '8pm', '9pm', '10pm', '11pm', '12am'];  
	createChart(JSONData, location, "Today");
  }
  else if (duration == "week"){
	JSONData.smallStandardVal = [35,35,35,35,35,35,35];
	JSONData.bigStandardVal = [150,150,150,150,150,150,150];
	JSONData.xAxisScale = ['Sunday','Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']; 
	createChart(JSONData, location, "This Week");
  }
  else if (duration == "month"){
	JSONData.smallStandardVal = [35,35,35,35,35,35,35,35,35,35,35,35,35,35,35,35,35,35,35,35,35,35,35,35,35,35,35,35,35,35,35];
	JSONData.bigStandardVal = [150,150,150,150,150,150,150,150,150,150,150,150,150,150,150,150,150,150,150,150,150,150,150,150,150,150,150,150,150,150,150];
	JSONData.xAxisScale = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31'];  
	createChart(JSONData, location, "This Month");
  }
  
}

function createChart(allData, location, duration){
	var locationName;
	switch(location)
	{
	case "1":
	  locationName = "Community Center";
	  break;
	case "2":
	  locationName = "Boulevard & Carol St";
	  break;
	case "3":
	  locationName = "Railyard";
	  break;
	}
	var smallParticleTitle = 'PM2.5 Readings for '+duration+' at the '+locationName;
	var largeParticleTitle = 'PM10 Readings for '+duration+' at the '+locationName;
	var smallOptions = {
		title: {
			text: smallParticleTitle,
			style: {
				"font-weight": "bold"
			}
		},
		xAxis: {
			categories: allData.xAxisScale
		},
		yAxis: {
			title: {
				text: 'PM readings (in \u00B5g/m'+"3".sup()+')'
			}
		},
		series: []
	};
	var bigOptions = {
		title: {
			text: largeParticleTitle,
			style: {
				"font-weight": "bold"
			}
		},
		xAxis: {
			categories: allData.xAxisScale
		},
		yAxis: {
			title: {
				text: 'PM readings (in \u00B5g/m'+"3".sup()+')'
			}
		},
		series: []
	};

	var neighborhoodSmallSeries = {
		type: 'column',
		name: locationName,
		data: []
	};
	$.each(allData.neighborhoodJSON.smallParticle, function(readingNo, reading) {
		var data = {};
		var currReading = parseFloat(reading);
		data.y = currReading;
		if (currReading <= 35) {
			data.color = '#82D694';
		}
		else {
			data.color = '#FC858D';
		}
		neighborhoodSmallSeries.data.push(data);
	});
	smallOptions.series.push(neighborhoodSmallSeries);
	
	var atlantaSmallSeries = {
		type: 'spline',
		name: 'Atlanta Readings',
		data: allData.atlantaJSON.smallParticle,
		marker: {
			lineWidth: 1,
			lineColor: '#8894c5',
			fillColor: 'white'
		}
	}; 
	smallOptions.series.push(atlantaSmallSeries);

	var standardSmallSeries = {
		type: 'spline',
		name: 'PM2.5 Standard Value',
		data: allData.smallStandardVal,
		marker: {
			lineWidth: 1,
			lineColor: '#4f8c1f',
			fillColor: 'white'
		}
	}; 
	smallOptions.series.push(standardSmallSeries);	

	var neighborhoodBigSeries = {
		type: 'column',
		name: locationName,
		data: []
	};
	$.each(allData.neighborhoodJSON.bigParticle, function(readingNo, reading) {
		var data = {};
		var currReading = parseFloat(reading);
		data.y = currReading;
		if (currReading <= 150) {
			data.color = '#82D694';
		}
		else {
			data.color = '#FC858D';
		}
		neighborhoodBigSeries.data.push(data);
	});
	bigOptions.series.push(neighborhoodBigSeries);
	
	var atlantaBigSeries = {
		type: 'spline',
		name: 'Atlanta Readings',
		data: allData.atlantaJSON.bigParticle,
		marker: {
			lineWidth: 1,
			lineColor: '#8894c5',
			fillColor: 'white'
		}
	}; 
	bigOptions.series.push(atlantaBigSeries);

	var standardBigSeries = {
		type: 'spline',
		name: 'PM10 Standard Value',
		data: allData.bigStandardVal,
		marker: {
			lineWidth: 1,
			lineColor: '#4f8c1f',
			fillColor: 'white'
		}
	}; 
	bigOptions.series.push(standardBigSeries);	
	
	var smallChart = $('#small-pm-chart').highcharts(smallOptions);
	var bigChart = $('#big-pm-chart').highcharts(bigOptions);	
}
