
function getReadings(location, duration){

	var JSONData = {};
	$.ajax({
    type: 'POST',
    url: "response.php",
    data: {location: location, duration: duration},
    dataType:"json",
    async: false
  }).done(function(data) {
	console.log(data);
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
	JSONData.xAxisScale = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']; 
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
	var smallParticleTitle = 'PM2.5 Readings for '+duration+' at the '+location;
	var largeParticleTitle = 'PM10 Readings for '+duration+' at the '+location;
	var smallOptions = {
		title: {
			text: smallParticleTitle
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
			text: largeParticleTitle
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
		name: allData.neighborhoodJSON.location,
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
		name: allData.neighborhoodJSON.location,
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
$(document).ready(function(){
	
	var currLocation = 'Community Center';
	var currDuration = 'day';
	getReadings(currLocation, currDuration);	
});