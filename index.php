<!-- 
	BACHELOR PROJECT
	Summer semester 2013
	Martin Hellwagner (0956048)
-->

<html>

<head>
<title>Bachelor project</title>
<meta charset="utf-8">
<?php
include "getArtistInfo.php";
include "getLocationsLastFm.php";
include "getLocationsSongkick.php";
$twitterMode = $_GET["twitterMode"];
if ($twitterMode == null || $twitterMode == "") {
	$twitterMode = false;
}
?>
<link rel="stylesheet" href="css/style.css"></link>
<link rel="stylesheet" href="css/buttons.css"></link>
<link rel="stylesheet" href="css/slider.css"></link>
<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css"></link>
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=true&libraries=geometry"></script>
<script type="text/javascript" src="http://code.jquery.com/jquery-1.9.1.js"></script>  
<script type="text/javascript" src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
<script type="text/javascript" src="scripts/template.js"></script>
<script type="text/javascript" src="scripts/dependClass.js"></script>
<script type="text/javascript" src="scripts/draggable.js"></script>
<script type="text/javascript" src="scripts/slider.js"></script>
<script type="text/javascript">
var twitterMode, sliderUnlocked, noTourStopsForArtist, numberPastTourStops, maxNumberOfTweets;
var start, startMarker, markersTweets, markersLocations, map, mapOptions, infoWindow, finished, output;
var artistInfo, tweets, tweetsCoordinates, tweetsMarkers, locations, locationsLastFm, locationsSongkick, locationsMarkers;

// checking for browser's geolocation functionality
function checkGeoloaction() {
	if (navigator.geolocation) {
		$("#slider").slider({
			callback: function() {
    			if (sliderUnlocked) {
    				filterLocations();
    			}
  			}
  		});
  		$("#slider").slider("value", 0, 365);
  		$("#checkboxTourStops").click(function() {
    		if (document.getElementById("submit").disabled == true) {
    			if ($(this).is(":checked")) {
    	    		toggleMarkersAndInformationLocations(markersLocations, true, numberPastTourStops);
    			} else {
    	   			toggleMarkersAndInformationLocations(markersLocations, false, numberPastTourStops);
    			}
    		}
		});
		$("#checkboxTweets").click(function() {
    		if (document.getElementById("submit").disabled == true) {
    			if ($(this).is(":checked")) {
    	    		toggleMarkersAndInformationTweets(markersTweets, true);
    			} else {
    	   			toggleMarkersAndInformationTweets(markersTweets, false);
    			}
    		}
		});
		twitterMode = <?php echo($twitterMode) ?>;
		navigator.geolocation.getCurrentPosition(initialize);
	} else {
		alert("Geolocation is not supported! Please upgrade your browser.");
	}
}

// setting up map and adding marker for user's current position
function initialize(position, reset) {
	markersTweets = new Array();
	markersLocations = new Array();
	artistInfo = new Array();
	tweets = new Array();
	tweetsCoordinates = new Array();
	tweetsMarkers = new Array();
	locations = new Array();
	locationsLastFm = new Array();
	locationsSongkick = new Array();
	locationsMarkers = new Array();
	numberPastTourStops = 0;
	maxNumberOfTweets = 50;
	noTourStopsForArtist = false;
	if (twitterMode != true) {
		document.getElementById("checkboxTweets").disabled = true;
	}
	$("#slider").slider("value", 0, 365);
	sliderUnlocked = false;
	Draggable.prototype.isDefault.drag = true;
	if (reset != 1) {
		start = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
	}
	mapOptions = {
   		zoom: 7,
   	    mapTypeId: google.maps.MapTypeId.ROADMAP,
   	    center: start
   	};
	map = new google.maps.Map(document.getElementById("map"), mapOptions);
	infoWindow = new google.maps.InfoWindow();
	startMarker = new google.maps.Marker({
   		position: start,
   		map: map,
   		icon: "http://maps.google.com/mapfiles/ms/icons/yellow-dot.png"
 	});
 	google.maps.event.addListener(startMarker, "mouseover", (function(startMarker) {
  		return function() {
    		infoWindow.setContent("Your current location");
        	infoWindow.open(map, startMarker);
        }
    })(startMarker));
    google.maps.event.addListener(startMarker, "mouseout", (function(startMarker) {
    	return function() {
        	infoWindow.close(map, startMarker);
        }
    })(startMarker));
}

// removing markers for tweets and event locations as well as information about tour stops
function reset() {
	initialize(null, 1);
	document.getElementById("submit").disabled = false;
	document.getElementById("reset").disabled = true;
	document.getElementById("checkboxTourStops").checked = true;
	document.getElementById("check").innerHTML = "";
	document.getElementById("artistName").disabled = false;
	document.getElementById("artistName").value = "";
	document.getElementById("selectedArtist").innerHTML = "";
	document.getElementById("nextAndPreviousTourStop").innerHTML = "";
	document.getElementById("nearestTourStop").innerHTML = "";
	document.getElementById("twitterActivity").innerHTML = "";
	$("#slider").slider("value", 0, 365);
}

// checking if input is correct
function checkInput(result, state) {
	if (state == 0) {
		document.getElementById("check").innerHTML = "";
		document.getElementById("loading").innerHTML = "<img src='images/loading.gif' height='20' width='20'></img>";
		getArtistInfo(document.getElementById("artistName").value);
	} else if (state == 1) {
    	document.getElementById("submit").disabled = true;
    	document.getElementById("artistName").disabled = true;
    	document.getElementById("checkboxTourStops").disabled = true;
    	document.getElementById("checkboxTweets").disabled = true;
    	Draggable.prototype.isDefault.drag = false;
		artistInfo = result;
		if (artistInfo[0][0] != -1) {
			if (twitterMode == true) {
				coordinateTwitterOutput(artistInfo[0][0]);
			} else {
				checkInput(null, 2);
			}
		} else {
			document.getElementById("submit").disabled = false;
			document.getElementById("artistName").disabled = false;
			document.getElementById("checkboxTourStops").disabled = false;
    		if (twitterMode == true) {
    			document.getElementById("checkboxTweets").disabled = false;
    		}
			document.getElementById("loading").innerHTML = "";
			document.getElementById("check").innerHTML = "<img src='images/invalid.png' height='22' width='22'></img>";
    		sliderUnlocked = false;
    		Draggable.prototype.isDefault.drag = true;
    	}
	} else if (state == 2) {
		if (twitterMode == true && tweets.length > 0) {
			computeMissingDataTweets(0, false);
		} else {
			checkInput(null, 3);
		}
	} else if (state == 3) {
		if (twitterMode == true && tweets.length > 0) {
			var j = 0;
			for (var i = 0; i < maxNumberOfTweets; i++) {
				if ((tweets[i][4] != 0 || tweets[i][4] != null || tweets[i][4] != "") &&
				    (tweets[i][5] != 0 || tweets[i][5] != null || tweets[i][5] != "")) {
					tweetsCoordinates[j] = new Array();
					tweetsCoordinates[j][0] = tweets[i][0];
					tweetsCoordinates[j][1] = tweets[i][3];
					tweetsCoordinates[j][2] = tweets[i][4];
					tweetsCoordinates[j][3] = tweets[i][5];
					j++;
				}
			}
		}
		getLocationsLastFm(artistInfo[0][1]);
    } else if (state == 4 && artistInfo[0][0] != -1) {
		for (var j = 0; j < result.length; j++) {
			locationsLastFm[j] = result[j];
		}
		getLocationsSongkick(artistInfo[0][1]);
	} else if (state == 5 && artistInfo[0][0] != -1) {
		for (var k = 0; k < result.length; k++) {
			locationsSongkick[k] = result[k];
		}
		if (locationsLastFm[0][0] != -1 || locationsSongkick[0][0] != -1) {
			consolidateLocations();
		} else {
			checkInput(null, 6);
		}
	} else if (state == 6) {
		computeMissingDataLocations(0, false);
	} else if (state == 7) {
		document.getElementById("reset").disabled = false;
		document.getElementById("checkboxTourStops").disabled = false;
    	if (twitterMode == true) {
    		document.getElementById("checkboxTweets").disabled = false;
    	}
    	document.getElementById("artistName").value = artistInfo[0][0];
    	document.getElementById("artistName").disabled = true;
		document.getElementById("loading").innerHTML = "";
		document.getElementById("check").innerHTML = "<img src='images/valid.png' height='20' width='20'></img>";
		sliderUnlocked = true;
		Draggable.prototype.isDefault.drag = true;
		if (twitterMode == true) {
    		addMarkersTweets();
    	} else {
    		filterLocations();
    	}
    }
}

// coordinating Twitter API output
function coordinateTwitterOutput(artist) {
	var ajaxRequest = new XMLHttpRequest();
	var url = "./getTwitterActivity.php?artist=" + artist;
	ajaxRequest.onreadystatechange = function() {
    	if (ajaxRequest.readyState == 4) {
			var index = 0;
			var twitterActivity = ajaxRequest.responseText.split("<br>");
			if (twitterActivity.length == 0 || twitterActivity[1] == "Failure") {
				tweets = new Array();
			} else {
				if (twitterActivity.length > (maxNumberOfTweets * 2)) {
					var randomBase = twitterActivity.length;
					var randomNumberMax = Math.floor(Math.random() * randomBase);
					var randomNumberMin = randomNumberMax - (maxNumberOfTweets * 2);
				} else {
					var randomNumberMax = maxNumberOfTweets * 2;
					var randomNumberMin = 0;
				}
				for (var i = randomNumberMin; i < randomNumberMax; i++) {
					if (twitterActivity[i] != null && twitterActivity[i] != "") {
						tweets[index] = twitterActivity[i].split(" ----- ");
						index++;
					}
				}
			}
			checkInput(null, 2);
      	}
  	}
  	ajaxRequest.open("GET", url, true);
  	ajaxRequest.send(null);
}

// consolidating location arrays of both APIs
function consolidateLocations() {
	if (locationsSongkick[0][0] == -1) {
		locations.length = locationsLastFm.length;
		for (var i = 0; i < locationsLastFm.length; i++) {
			handleExceptions(locationsLastFm[i], i, 0);
		}
		for (var j = 0; j < locationsLastFm.length; j++) {
			for (var k = j+1; k < locationsLastFm.length; k++) {
				if (locationsLastFm[j][0].getDate() == locationsLastFm[k][0].getDate() &&
					locationsLastFm[j][0].getMonth() == locationsLastFm[k][0].getMonth() &&
					locationsLastFm[j][0].getFullYear() == locationsLastFm[k][0].getFullYear() &&
					(locationsLastFm[j][2].split(",")[0] == locationsLastFm[k][2].split(",")[0] ||
					(Math.abs(locationsLastFm[j][4] - locationsLastFm[k][4]) < 1 &&
					 Math.abs(locationsLastFm[j][5] - locationsLastFm[k][5]) < 1))) {
					locationsLastFm[k][6] = "duplicate";
				}
			}
		}
		var index = 0;
		for (var l = 0; l < locationsLastFm.length; l++) {
			if (locationsLastFm[l][6] != "duplicate") {
				locations[index] = locationsLastFm[l];
				index++;
			}
		}
		locations.length = index;
	} else {
		locations.length = locationsSongkick.length;
		for (var i = 0; i < locationsSongkick.length; i++) {
			handleExceptions(locationsSongkick[i], i, 1);
		}
		for (var j = 0; j < locationsSongkick.length; j++) {
			for (var k = j+1; k < locationsSongkick.length; k++) {
				if (locationsSongkick[j][0].getDate() == locationsSongkick[k][0].getDate() &&
					locationsSongkick[j][0].getMonth() == locationsSongkick[k][0].getMonth() &&
					locationsSongkick[j][0].getFullYear() == locationsSongkick[k][0].getFullYear() &&
					(locationsSongkick[j][2].split(",")[0] == locationsSongkick[k][2].split(",")[0] ||
					(Math.abs(locationsSongkick[j][4] - locationsSongkick[k][4]) < 1 &&
					 Math.abs(locationsSongkick[j][5] - locationsSongkick[k][5]) < 1))) {
					locationsSongkick[k][6] = "duplicate";
				}
			}
		}
		var index = 0;
		for (var l = 0; l < locationsSongkick.length; l++) {
			if (locationsSongkick[l][6] != "duplicate") {
				locations[index] = locationsSongkick[l];
				index++;
			}
		}
		locations.length = index;
		for (var i = 0; i < locationsLastFm.length; i++) {
			handleExceptions(locationsLastFm[i], i, 0);
		}
		for (var j = 0; j < locationsLastFm.length; j++) {
			for (var k = 0; k < locations.length; k++) {
				if (locationsLastFm[j][0].getDate() == locations[k][0].getDate() &&
					locationsLastFm[j][0].getMonth() == locations[k][0].getMonth() &&
					locationsLastFm[j][0].getFullYear() == locations[k][0].getFullYear() &&
					(locationsLastFm[j][2].split(",")[0] == locations[k][2].split(",")[0] ||
					(Math.abs(locationsLastFm[j][4] - locations[k][4]) < 1 &&
					 Math.abs(locationsLastFm[j][5] - locations[k][5]) < 1))) {
					if (locations[k][1] == "Unknown venue") {
						locations[k][1] = locationsLastFm[j][1];
					}
					if ((locationsLastFm[j][4] != "" && locationsLastFm[j][4] != 0 && locationsLastFm[j][4] != null) &&
						(locations[k][4] == "" || locations[k][4] == 0 || locations[k][4] == null)) {
						locations[k][4] = locationsLastFm[j][4];
					}
					if ((locationsLastFm[j][5] != "" && locationsLastFm[j][5] != 0 && locationsLastFm[j][5] != null) &&
						(locations[k][5] == "" || locations[k][5] == 0 || locations[k][5] == null)) {
						locations[k][5] = locationsLastFm[j][5];
					}
					locationsLastFm[j][6] = "duplicate";
				}
			}
		}
		index = locations.length;
		for (var l = 0; l < locationsLastFm.length; l++) {
			if (locationsLastFm[l][6] != "duplicate") {
				locations[index] = locationsLastFm[l];
				index++;
			}
		}
	}
	locations.sort(function(x, y) {
  		if (x[0] > y[0]) {
  			return 1;
  		}
  		if (x[0] < y[0]) {
  			return -1;
  		}
  		return 0;
	});
	for (var i = 0; i < locations.length; i++) {
		for (var j = 0; j < locations.length; j++) {
			if (locations[i][1] == locations[j][1] &&
				locations[i][2] == locations[j][2] &&
				locations[i][3] == locations[j][3]) {
				locations[j][4] = locations[i][4];
				locations[j][5] = locations[i][5];
				locations[j][6] = locations[i][6];
			}
		}
	}
	checkInput(null, 6);
}

// handling deviating venue, city and country names
function handleExceptions(locationsDeviating, index, service) {
	if (locationsDeviating[1] == "Verizon Center Washington Dc") {
		locationsDeviating[1] = "Verizon Center";
	} else if (locationsDeviating[1] == "Brixton Accademy" || locationsDeviating[1] == "Brixton Academy") {
		locationsDeviating[1] = "O2 Academy Brixton";
	} else if (locationsDeviating[1] == "Arena Leipzig") {
		locationsDeviating[1] = "Arena";
	} else if (locationsDeviating[1] == "Festhalle Frankfurt") {
		locationsDeviating[1] = "Festhalle";
	} else if (locationsDeviating[1] == "Wiener Stadthalle") {
		locationsDeviating[1] = "Stadthalle";
	} else if (locationsDeviating[1] == "LIFEPARK") {
		locationsDeviating[1] = "LifePark";
	} else if (locationsDeviating[1] == "Impact Arena, Muang Thong Thani") {
		locationsDeviating[1] = "Impact Arena";
	} else if (locationsDeviating[1] == "Arena Moscow" && locationsDeviating[3] == "Russian Federation") {
		locationsDeviating[1] = "Arena";
    	locationsDeviating[2] = "Moscow";
	} else if (locationsDeviating[1] == "LANXESS Arena") {
		locationsDeviating[1] = "Lanxess Arena";
	} else if (locationsDeviating[1] == "CONSOL Energy Center") {
		locationsDeviating[1] = "Consol Energy Center";
	} else if (locationsDeviating[1] == "Ottakringer Arena Wiesen") {
		locationsDeviating[1] = "Ottakringer Arena";
	} else if (locationsDeviating[1].substring(0, 2) == "Ar" && locationsDeviating[2] == "Riga") {
		locationsDeviating[1] = "Arena";
	} else if (locationsDeviating[1] == "Zénith de Nantes") {
		locationsDeviating[1] = "Zénith";
	} else if ((locationsDeviating[1] == "Flugplatz (Neuhausen ob Eck)" || locationsDeviating[1] == "Unknown Venue") &&
			   (locationsDeviating[2] == "Neuhausen ob Eck" || locationsDeviating[2] == "Neuhausen Ob Eck")) {
		locationsDeviating[1] = "Flugplatz";
		locationsDeviating[2] = "Neuhausen ob Eck";
	} else if (locationsDeviating[1] == "Park Orman") {
		locationsDeviating[1] = "Parkorman";
	} else if (locationsDeviating[1] == "les voix du gaou") {
		locationsDeviating[1] = "Les Voix du Gaou";
	} else if (locationsDeviating[1] == "L Album De La Semaine") {
		locationsDeviating[1] = "L'Album De La Semaine";
	} else if (locationsDeviating[1] == "Gardens by the Bay") {
		locationsDeviating[1] = "Gardens By The Bay";
	} else if (locationsDeviating[1] == "Zenith, Die Kulturhalle") {
		locationsDeviating[1] = "Zenith";
	} else if (locationsDeviating[1] == "Saitama Super Arena (さいたまスーパーアリーナ)") {
		locationsDeviating[1] = "Super Arena";
	} else if (locationsDeviating[1] == "Jisan Valley Ski Resort") {
		locationsDeviating[1] = "Jisan Forest Resort";
	} else if (locationsDeviating[1] == "Schützenmatte (bei Bahnhof Sbb)") {
		locationsDeviating[1] = "Schützenmatte";
	} else if (locationsDeviating[1] == "Arènes de Nîmes") {
		locationsDeviating[1] = "Arènes";
	} else if (locationsDeviating[1] == "Palais Omnisports de Paris Bercy") {
		locationsDeviating[1] = "Palais Omnisports";
	} else if (locationsDeviating[1] == "Papp Laszlo Budapest Sportarena") {
		locationsDeviating[1] = "Papp Laszlo Sportarena";
	} else if (locationsDeviating[1] == "EXPO Plaza") {
		locationsDeviating[1] = "Expo Plaza";
	} else if (locationsDeviating[1] == "Randall's Island") {
		locationsDeviating[1] = "Randall's Island Park";
	} else if (locationsDeviating[1] == "Sydney Entertainment Centre") {
		locationsDeviating[1] = "Entertainment Centre";
	} else if (locationsDeviating[1] == "Wellington Town Hall") {
		locationsDeviating[1] = "Town Hall";
	} else if (locationsDeviating[1] == "Gold Coast Convention and Exhibition Centre") {
		locationsDeviating[1] = "Convention and Exhibition Centre";
	} else if (locationsDeviating[1] == "Dungog Showgrounds") {
		locationsDeviating[1] = "Showgrounds";
	} else if (locationsDeviating[1] == "Townsville Entertainment & Convention Centre") {
		locationsDeviating[1] = "Entertainment & Convention Centre";
	} else if (locationsDeviating[1] == "Carroponte c/o Spazio MIL, Via Granelli") {
		locationsDeviating[1] = "Carroponte";
	} else if (locationsDeviating[1] == "ZAAL DE ZWERVER") {
		locationsDeviating[1] = "Zaal De Zwerver";
	} else if (locationsDeviating[1] == "Eden Arena (former Synot Tip Arena)") { 
		locationsDeviating[1] = "Eden Arena";
	} else if (locationsDeviating[1] == "Terrain de 4X4 Route de la Glande") { 
		locationsDeviating[1] = "Route de la Glande";
	} else if (locationsDeviating[1] == "fiere bologna") {
		locationsDeviating[1] = "Fiere";
	} else if (locationsDeviating[1] == "areál bývalých kasáren") {
		locationsDeviating[1] = "Areál Bývalých Kasáren";
	} else if (locationsDeviating[1] == "Gruenspan") {
		locationsDeviating[1] = "Grünspan";
	}
	if (locationsDeviating[2].split(",")[0] == "Meierhofwiese") {
		locationsDeviating[2] = "Klam";
    } else if (locationsDeviating[2] == "Washington" || locationsDeviating[2] == "Washington DC") {	
		locationsDeviating[2] = "Washington, D.C.";
	} else if (locationsDeviating[2] == "New York") {
		locationsDeviating[2] = "New York City";
	} else if (locationsDeviating[2] == "Oshkosh WI") {
		locationsDeviating[2] = "Oshkosh";
	} else if (locationsDeviating[2] == "Saint Cloud") {
		locationsDeviating[2] = "Saint-Cloud";
	} else if (locationsDeviating[2].split(" ")[0] == "Manila" || locationsDeviating[2].split(" ")[0] == "Makati" ||
			   locationsDeviating[2].split(" ")[0] == "Quezon" || locationsDeviating[2].split(" ")[0] == "Pasay") {
		locationsDeviating[2] = "Manila";
    } else if (locationsDeviating[2] == "10" && locationsDeviating[3] == "Korea") {
    	locationsDeviating[2] = "Seoul";
    } else if (locationsDeviating[2] == "Praha") {
		locationsDeviating[2] = "Prague";
	} else if (locationsDeviating[2] == "Weston-under-Lizard" && locationsDeviating[1] == "Weston Park") {
    	locationsDeviating[2] = "Stafford";
    	locationsDeviating[3] = "United Kingdom";
    } else if (locationsDeviating[2] == "Snowmass Village") {
    	locationsDeviating[2] = "Aspen";
	} else if (locationsDeviating[2] == "six fours les plages") {
		locationsDeviating[2] = "Six Fours Les Plages";
	} else if (locationsDeviating[2] == "İstanbul") {
		locationsDeviating[2] = "Istanbul";
	} else if (locationsDeviating[2] == "Newcastle / Gateshead" || locationsDeviating[2] == "Newcastle Upon Tyne") {
		locationsDeviating[2] = "Newcastle upon Tyne";
	} else if (locationsDeviating[2] == "Chuncheon-si") {
		locationsDeviating[2] = "Gangwon-do";
	} else if (locationsDeviating[2] == "Zürich") {
		locationsDeviating[2] = "Zurich";
	} else if (locationsDeviating[2] == "München") {
		locationsDeviating[2] = "Munich";
	} else if (locationsDeviating[2] == "Shibuya") {
		locationsDeviating[2] = "Tokyo";
	} else if (locationsDeviating[2] == "Санкт-Петербург") {
		locationsDeviating[2] = "St. Petersburg";
	} else if (locationsDeviating[2] == "Киев") {
		locationsDeviating[2] = "Kiev";
	} else if (locationsDeviating[2] == "Göteborg och Stockholm") {
		locationsDeviating[2] = "Göteborg";
	} else if (locationsDeviating[2] == "30521 HANNOVER") {
		locationsDeviating[2] = "Hanover";
	} else if (locationsDeviating[1] == "Amnesia" && locationsDeviating[3] == "Spain") {
		locationsDeviating[2] = "Ibiza";
	} else if (locationsDeviating[2] == "Hindmarsh" && locationsDeviating[3] == "Australia") {
		locationsDeviating[1] = "Entertainment Centre";
		locationsDeviating[2] = "Adelaide";
	} else if (locationsDeviating[2] == "Sesto San Giovanni (MI)") {
		locationsDeviating[2] = "Milan";
	} else if (locationsDeviating[2] == "LEFFINGE") {
		locationsDeviating[2] = "Leffinge";
	} else if (locationsDeviating[2] == "Архангельск") {
		locationsDeviating[2] = "Arkhangelsk";
	} else if (locationsDeviating[2] == "69250 Poleymieux au Mont d’Or") {
		locationsDeviating[2] = "Poleymieux";
	} else if (locationsDeviating[2] == "bologna") {
		locationsDeviating[2] = "Bologna";
	} else if (locationsDeviating[2] == "Wien") {
		locationsDeviating[2] = "Vienna";
	} else if (locationsDeviating[2] == "são paulo") {
		locationsDeviating[2] = "São Paulo";
	} else {
    	locationsDeviating[2] = locationsDeviating[2].split(",")[0];
    }
	if (locationsDeviating[3] == "Russian Federation") {
		locationsDeviating[3] = "Russia";
	} else if (locationsDeviating[3] == "UK") {
		locationsDeviating[3] = "United Kingdom";
	} else if (locationsDeviating[3] == "NSW" || locationsDeviating[3] == "QLD" || locationsDeviating[3] == "SA" ||
		locationsDeviating[3] == "TAS" || locationsDeviating[3] == "VIC" || locationsDeviating[3] == "ACT" ||
		(locationsDeviating[3] == "WA"  && locationsDeviating[4] < 0) ||
		(locationsDeviating[3] == "NT" && locationsDeviating[4] < 0)) {
		locationsDeviating[3] = "Australia";
	} else if (locationsDeviating[3] == "AB" || locationsDeviating[3] == "BC" || locationsDeviating[3] == "MB" ||
		locationsDeviating[3] == "NB" || locationsDeviating[3] == "NL" || locationsDeviating[3] == "NS" ||
		locationsDeviating[3] == "NT" || locationsDeviating[3] == "NU" || locationsDeviating[3] == "ON" ||
		locationsDeviating[3] == "PE" || locationsDeviating[3] == "QC" || locationsDeviating[3] == "SK" ||
		locationsDeviating[3] == "YK") {
		locationsDeviating[3] = "Canada";
	} else if (locationsDeviating[3].length == 2) {
		locationsDeviating[3] = "United States";
	}
	if (service == 0) {
		locationsLastFm[index] = locationsDeviating;
	} else if (service == 1) {
		locationsSongkick[index] = locationsDeviating;
	} else {
		locations[index] = locationsDeviating;
	}
}

// computing missing coordinates for tweets
function computeMissingDataTweets(i, firstDone) {
	if (firstDone == false) {
		if (i == tweets.length || i == maxNumberOfTweets) {
			computeMissingDataTweets(0, true);
		} else {
			if (typeof tweets[i] == "undefined" || tweets[i][1] == -1) {
				tweets[i] = new Array(tweets[i][0], tweets[i][1], tweets[i][2], tweets[i][3], 0, 0);
    			i++;
    			computeMissingDataTweets(i, false);
    		} else {
    			getCoordinatesFromCity(i, tweets[i][1], true, function(result) {
    				var index = result[0];
    				var positionX = result[1];
    				var positionY = result[2];
    				if (positionX == -1 && positionY == -1) {
    					setTimeout(function() {
    						computeMissingDataTweets(index, false);
    					}, 100);
    				} else {				
    					tweets[index] = new Array(tweets[index][0], tweets[index][1], tweets[index][2], tweets[index][3], positionX, positionY);
    					index++;
    					computeMissingDataTweets(index, false);
    				}
    			});
    		}
    	}	
	} else if (firstDone == true) {
		if (i == tweets.length || i == maxNumberOfTweets) {
			checkInput(null, 3);
		} else {
			if (typeof tweets[i] == "undefined" || (tweets[i][4] == 0 && tweets[i][5] == 0)) {
				if (tweets[i][2] == -1 || tweets[i][2] == "Greenland" || tweets[i][2] == "Eastern Time (US & Canada)" ||
					tweets[i][2] == "Central Time (US & Canada)" || tweets[i][2] == "Pacific Time (US & Canada)") {
					tweets[i] = new Array(tweets[i][0], tweets[i][1], tweets[i][2], tweets[i][3], 0, 0);
    				i++;
    				computeMissingDataTweets(i, true);
    			} else {
    				getCoordinatesFromCity(i, tweets[i][2], true, function(result) {
    					var index = result[0];
    					var positionX = result[1];
    					var positionY = result[2];
    					if (positionX == -1 && positionY == -1) {
    						setTimeout(function() {
    							computeMissingDataTweets(index, true);
    						}, 100);
    					} else {   				
    						tweets[index] = new Array(tweets[index][0], tweets[index][1], tweets[index][2], tweets[index][3], positionX, positionY);
    						index++;
    						computeMissingDataTweets(index, true);
    					}
    				});
    			}
    		} else {
    			i++;
    			computeMissingDataTweets(i, true);
    		}
    	}	
	}
}

// computing missing coordinates or city names for event locations
function computeMissingDataLocations(i, firstDone) {
	if (i == locations.length || locations.length == 0) {
		checkInput(null, 7);
	} else {
		handleExceptions(locations[i], i, 2);
    	if (firstDone == false && locations[i][2] == "" && (locations[i][4] != "" && locations[i][5] != "")) {
    	    var locationTemp = new google.maps.LatLng(locations[i][4], locations[i][5]);
    	    getCityFromCoordinates(i, locationTemp, function(result) {
    	    	var index = result[0];
    	    	locations[index][2] = result[1];
    	    	computeMissingDataLocations(index, true);
    		});
    	}
    	firstDone = true;
    	if (firstDone == true && (locations[i][2] != "" && locations[i][3] != "") &&
    	    (locations[i][4] == "" || locations[i][4] == 0 || locations[i][4] == null ||
    	   	 locations[i][5] == "" || locations[i][5] == 0 || locations[i][5] == null)) {
    	   	var locationTemp = locations[i][2] + ", " + locations[i][3];
    	    getCoordinatesFromCity(i, locationTemp, false, function(result) {
    	    	var index = result[0];
    	    	var positionX = result[1];
    			var positionY = result[2];
    	    	if (positionX != -1 && positionY != -1) {  				
    				if (locations[index][4] == "" || locations[index][4] == 0 || locations[index][4] == null) {
    	    			locations[index][4] = positionX;
    	    		}
    	    		if (locations[index][5] == "" || locations[index][5] == 0 || locations[index][5] == null) {
    	    			locations[index][5] = positionY;
    	    		}
    	    		index++;
    				computeMissingDataLocations(index, false);
    			}
    		});
    	}
    	i++;
    	computeMissingDataLocations(i, false);
    }
}

// getting city name from coordinates
function getCityFromCoordinates(index, position, callback) {
    var city, geocoder = new google.maps.Geocoder();
    geocoder.geocode({"latLng": position}, function(result, status) {
    	if (status == google.maps.GeocoderStatus.OK) {
    		city = result[1].formatted_address.split(",");
      	} else {
        	city = new Array("");
      	}
      	callback(new Array(index, city[0]));
    });
}

// getting coordinates from city name
function getCoordinatesFromCity(index, city, overQuery, callback) {
    var positionX, positionY, geocoder = new google.maps.Geocoder();
    geocoder.geocode({"address": city}, function(result, status) {
    	if (status == google.maps.GeocoderStatus.OK) {
    		positionX = result[0].geometry.location.lat();
    		positionY = result[0].geometry.location.lng();
        } else if (status == "OVER_QUERY_LIMIT") {
        	positionX = -1;
        	positionY = -1;
        } else {
        	positionX = 0;
        	positionY = 0;   	
      	}
      	callback(new Array(index, positionX, positionY));
    });
}

// adding markers for tweets and creating associated info windows
function addMarkersTweets() {	
	var date, dateString, today, yesterday;
    var location, marker, bounds = new google.maps.LatLngBounds();
	document.getElementById("reset").disabled = false;
	document.getElementById("check").innerHTML = "<img src='images/valid.png' height='20' width='20'></img>";
    tweetsCoordinates.sort(function(x, y) {
  		if (x[0] > y[0]) {
  			return 1;
  		}
  		if (x[0] < y[0]) {
  			return -1;
  		}
  		return 0;
	});
	while (markersTweets.length > 0) {
    	markersTweets.pop().setMap(null);
    }
    markersTweets = new Array();
    markersTweets.length = 0;
    tweetsMarkers = new Array();
    tweetsMarkers.length = 0;
    index = 0;
    today = new Date();
    yesterday = new Date(today.getTime() - (24 * 60 * 60 * 1000));
    for (var i = 0; i < tweetsCoordinates.length; i++) {
    	if (tweetsCoordinates[i][2] != -1 && tweetsCoordinates[i][3] != -1) {
        	var date = new Date(tweetsCoordinates[i][0]);
        	if (date.getDate() == today.getDate() && date.getMonth() == today.getMonth() &&
        		date.getFullYear() == today.getFullYear()) {
            	dateString = "<br><i>Today</i>";
			} else if (date.getDate() == yesterday.getDate() && date.getMonth() == yesterday.getMonth() &&
					   date.getFullYear() == yesterday.getFullYear()) {
				dateString = "<br><i>Yesterday</i>";
			} else {
    			dateString = "<br><i>" + $.datepicker.formatDate('MM', date) + " " + 
    			       	     $.datepicker.formatDate('d', date) + ", " + 
    			             $.datepicker.formatDate('yy', date) + "</i>";
    		}
    		if (tweetsCoordinates[i][1] == null || tweetsCoordinates[i][1] == 0 || tweetsCoordinates[i][1] == "0" ||
    			tweetsCoordinates[i][1] == "undefined" || tweetsCoordinates[i][1] == "NaN" || tweetsCoordinates[i][1] == "") {
    			tweetsCoordinates[i][1] = "Unknown tweet content";
    		}
    		tweetsMarkers[index] = new Array(tweetsCoordinates[i][0], tweetsCoordinates[i][1], tweetsCoordinates[i][2], tweetsCoordinates[i][3], 1, dateString);
    		for (var j = i+1; j < tweetsCoordinates.length; j++) {
    			if (tweetsCoordinates[i][2] == tweetsCoordinates[j][2] && tweetsCoordinates[i][3] == tweetsCoordinates[j][3]) {
    			    tweetsMarkers[index][4] = tweetsMarkers[index][4] + 1;
    			    date = new Date(tweetsCoordinates[j][0]);
        			if (date.getDate() == today.getDate() && date.getMonth() == today.getMonth() &&
        				date.getFullYear() == today.getFullYear()) {
            			dateString = "<br><i>Today</i>";
					} else if (date.getDate() == yesterday.getDate() && date.getMonth() == yesterday.getMonth() &&
						date.getFullYear() == yesterday.getFullYear()) {
						dateString = "<br><i>Yesterday</i>";
					} else {
    					dateString = "<br><i>" + $.datepicker.formatDate('MM', date) + " " + 
    			       	             $.datepicker.formatDate('d', date) + ", " + 
    			           	         $.datepicker.formatDate('yy', date) + "</i>";
    				}
    			    if (tweetsMarkers[index][5] != dateString) {
    			    	tweetsMarkers[index][5] += dateString;
    			    }
    			    if (tweetsMarkers[index][1] != "<br>Unknown tweet content" &&
    			       (tweetsCoordinates[j][1] == null || tweetsCoordinates[j][1] == 0 || tweetsCoordinates[j][1] == "0" ||
    			   		tweetsCoordinates[j][1] == "undefined" || tweetsCoordinates[j][1] == "NaN" || tweetsCoordinates[j][1] == "")) {
    			   		tweetsMarkers[index][1] += "<br>Unknown tweet content";
    			   	} else {
    			    	tweetsMarkers[index][1] += "<br>" + tweetsCoordinates[j][1];
    			    }
    				tweetsCoordinates[j][2] = -1;
    				tweetsCoordinates[j][3] = -1;
    			}
    		}
    		index++;
    	}
    }
    for (var k = 0; k < tweetsMarkers.length; k++) {
    	if (k == 0) {
    		bounds.extend(startMarker.position);
    		map.fitBounds(bounds);
    		if (map.getZoom() < 2) {
    			map.setZoom(2);
    		}
   	 	}
   	 	if (tweetsMarkers[k][2] != 0 && tweetsMarkers[k][3] != 0) {
    		location = new google.maps.LatLng(tweetsMarkers[k][2], tweetsMarkers[k][3]);
    	}
    	marker = new google.maps.Marker({
    		position: location,
    		map: map,
    		icon: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png"
    	});
    	markersTweets.push(marker);
    	bounds.extend(marker.position);
    	map.fitBounds(bounds);
    	if (map.getZoom() < 2) {
    		map.setZoom(2);
    	}
   		google.maps.event.addListener(marker, "mouseover", (function(k, marker) {
    		return function() {
    			if (tweetsMarkers[k][4] > 1) {
    				infoWindow.setContent("<b>" + tweetsMarkers[k][4] + " Tweets</b><br>" + tweetsMarkers[k][1] + "<i>" + tweetsMarkers[k][5] + "</i>");
    			} else {
    				infoWindow.setContent("<b>" + tweetsMarkers[k][4] + " Tweet</b><br>" + tweetsMarkers[k][1] + "<i>" + tweetsMarkers[k][5] + "</i>");
    			}
    	   		infoWindow.open(map, marker);
    	   	}
    	})(k, marker));
    	google.maps.event.addListener(marker, "mouseout", (function(marker) {
    		return function() {
    	   		infoWindow.close(map, marker);
    	   	}
    	})(marker));
    }
    toggleMarkersAndInformationTweets(markersTweets, document.getElementById("checkboxTweets").checked);
	filterLocations();
}

// filter locations of event dates according to slider
function filterLocations() {
	var index, sliderValues;
	var minimumDate, maximumDate;
	var locationsFiltered = new Array();
	locationsFiltered.length = 0;
	index = 0, sliderValues = $("#slider").slider("value").split(";");
	today = new Date();
	minimumDate = new Date(today.getTime() + ((24 * 60 * 60 * 1000) * sliderValues[0]));
	minimumDate = Date.UTC(minimumDate.getFullYear(), minimumDate.getMonth(), minimumDate.getDate());
	maximumDate = new Date(today.getTime() + ((24 * 60 * 60 * 1000) * sliderValues[1]));
	maximumDate = Date.UTC(maximumDate.getFullYear(), maximumDate.getMonth(), maximumDate.getDate()); 
	for (var i = 0; i < locations.length; i++) { 
		var currentDate = Date.UTC(locations[i][0].getFullYear(), locations[i][0].getMonth(), locations[i][0].getDate());
		if (currentDate >= minimumDate && currentDate <= maximumDate) {
			locationsFiltered[index] = new Array(null, null, null, null, null, null, 0);
			locationsFiltered[index][0] = locations[i][0];
			locationsFiltered[index][1] = locations[i][1];
			locationsFiltered[index][2] = locations[i][2];
			locationsFiltered[index][3] = locations[i][3];
			locationsFiltered[index][4] = locations[i][4];
			locationsFiltered[index][5] = locations[i][5];
			index++;
		}
	}
	addMarkersLocations(locationsFiltered, sliderValues);
}

// adding markers for event locations and creating associated info windows
function addMarkersLocations(locationsFiltered, sliderValues) {
    var index, today, todayDate, tomorrow, yesterday, currentDate, pastDate, futureDate;
    var location, marker, markerNumber, bounds = new google.maps.LatLngBounds();
	document.getElementById("reset").disabled = false;
	document.getElementById("check").innerHTML = "<img src='images/valid.png' height='20' width='20'></img>";
    if (locationsFiltered.length == 0) {
   		var currentZoom = map.getZoom();
   		map.setCenter(startMarker.position);
   		map.setZoom(7);
   		if (twitterMode == true && map.getZoom() > currentZoom) {
    		map.setZoom(currentZoom);
    	}
   	}
   	today = new Date();
    tomorrow = new Date(today.getTime() + (24 * 60 * 60 * 1000));
    yesterday = new Date(today.getTime() - (24 * 60 * 60 * 1000));
    todayDate = Date.UTC(today.getFullYear(), today.getMonth(), today.getDate());
	numberPastTourStops = 0;
	for (var i = 0; i < locationsFiltered.length; i++) {
		currentDate = Date.UTC(locationsFiltered[i][0].getFullYear(), locationsFiltered[i][0].getMonth(), locationsFiltered[i][0].getDate()); 
        if (todayDate > currentDate) {
        	numberPastTourStops++;
        }
        if (locationsFiltered[i][6] != "moreTourStops" &&
        	locationsFiltered[i][0].getDate() == today.getDate() &&
        	locationsFiltered[i][0].getMonth() == today.getMonth() &&
            locationsFiltered[i][0].getFullYear() == today.getFullYear()) {
            locationsFiltered[i][6] = "<br><i>Today</i>";
        } else if (locationsFiltered[i][6] != "moreTourStops" &&
        	locationsFiltered[i][0].getDate() == tomorrow.getDate() &&
        	locationsFiltered[i][0].getMonth() == tomorrow.getMonth() &&
        	locationsFiltered[i][0].getFullYear() == tomorrow.getFullYear()) {
            locationsFiltered[i][6] = "<br><i>Tomorrow</i>";
        } else if (locationsFiltered[i][6] != "moreTourStops" &&
        	locationsFiltered[i][0].getDate() == yesterday.getDate() &&
        	locationsFiltered[i][0].getMonth() == yesterday.getMonth() &&
        	locationsFiltered[i][0].getFullYear() == yesterday.getFullYear()) {
            locationsFiltered[i][6] = "<br><i>Yesterday</i>";
        } else if (locationsFiltered[i][6] != "moreTourStops") {
        	locationsFiltered[i][6] = "<br><i>" + $.datepicker.formatDate('MM', locationsFiltered[i][0]) + " " + 
    		    	   	  	          $.datepicker.formatDate('d', locationsFiltered[i][0]) + ", " + 
    		    	      	          $.datepicker.formatDate('yy', locationsFiltered[i][0]) + "</i>";
    	}
     	for (var j = i+1; j < locationsFiltered.length; j++) {
     		if (locationsFiltered[j][6] != "moreTourStops" &&
     			locationsFiltered[i][1] == locationsFiltered[j][1] &&
     			locationsFiltered[i][2] == locationsFiltered[j][2] &&
     			locationsFiltered[i][3] == locationsFiltered[j][3] &&
     			locationsFiltered[i][4] == locationsFiltered[j][4] &&
     			locationsFiltered[i][5] == locationsFiltered[j][5]) {
    			if (locationsFiltered[i][0] != locationsFiltered[j][0]) {
					locationsFiltered[i][6] += "<br><i>" + $.datepicker.formatDate('MM', locationsFiltered[j][0]) + " " + 
    		    	   	  	                   $.datepicker.formatDate('d', locationsFiltered[j][0]) + ", " + 
    		    	      	                   $.datepicker.formatDate('yy', locationsFiltered[j][0]) + "</i>";
    		    }
     			locationsFiltered[j][6] = "moreTourStops";
     		}
    	}
    }
    locationsMarkers = new Array();
    locationsMarkers.length = 0;
    index = 0;
    for (var i = 0; i < locationsFiltered.length; i++) {
    	if (numberPastTourStops == i) {
    		numberPastTourStops = index;
    	}
    	if (locationsFiltered[i][6] != "moreTourStops") {
    		locationsMarkers[index] = new Array(null, null, null, null, null, null, 0);
			locationsMarkers[index][0] = locationsFiltered[i][6];
			locationsMarkers[index][1] = locationsFiltered[i][1];
			locationsMarkers[index][2] = locationsFiltered[i][2];
			locationsMarkers[index][3] = locationsFiltered[i][3];
			locationsMarkers[index][4] = locationsFiltered[i][4];
			locationsMarkers[index][5] = locationsFiltered[i][5]; 
     		index++;
     	}
	}
	while (markersLocations.length > 0) {
    	markersLocations.pop().setMap(null);
    }
    markersLocations = new Array();
    markersLocations.length = 0;
	for (var i = 0; i < locationsMarkers.length; i++) {
    	if (i == 0) {
    		var currentZoom = map.getZoom();
    		bounds.extend(startMarker.position);
    		map.fitBounds(bounds);
    		if (twitterMode == true && map.getZoom() > currentZoom) {
    			map.setZoom(currentZoom);
    		}
    		if (map.getZoom() < 2) {
    			map.setZoom(2);
    		}
   	 	}
   	 	location = new google.maps.LatLng(locationsMarkers[i][4], locationsMarkers[i][5]);
		if (i < numberPastTourStops) {
   	 		markerNumber = i+1;
     		marker = new google.maps.Marker({
    			position: location,
    			map: map,
    			icon: "http://www.googlemapsmarkers.com/v1/" + markerNumber + "/00E64D"
    		});
    	} else {
    		markerNumber = i+1-numberPastTourStops;
     		marker = new google.maps.Marker({
    			position: location,
    			map: map,
    			icon: "http://www.googlemapsmarkers.com/v1/" + markerNumber + "/FD7567"
    		});
    	}
    	var currentZoom = map.getZoom();
    	markersLocations.push(marker);
    	bounds.extend(marker.position);
    	map.fitBounds(bounds);
    	if (twitterMode == true && map.getZoom() > currentZoom) {
    		map.setZoom(currentZoom);
    	}
    	if (map.getZoom() < 2) {
    		map.setZoom(2);
    	}
   	 	google.maps.event.addListener(marker, "mouseover", (function(i, marker) {
    		return function() {
    			infoWindow.setContent("<b>" + locationsMarkers[i][2] + ", " + locationsMarkers[i][3] +
    							      "</b><br>" + locationsMarkers[i][1] + locationsMarkers[i][0]);
        		infoWindow.open(map, marker);
        	}
    	})(i, marker));
    	google.maps.event.addListener(marker, "mouseout", (function(marker) {
    		return function() {
        		infoWindow.close(map, marker);
        	}
    	})(marker));
	}
	toggleMarkersAndInformationLocations(markersLocations, document.getElementById("checkboxTourStops").checked, numberPastTourStops);
}

// displaying next and nearest tour stop on the right side   
function addInformation(mode, numberPastTourStops) {
    var index, distanceOld, distanceNew, location, sliderValues;
    var nextAndPreviousTourStop, nearestTourStop;
    var dateNextTourStop, datePreviousTourStop, dateNearestTourStop;
    document.getElementById("selectedArtist").innerHTML = "<p><u>Selected artist</u>:</p>" + "<b>" +
    													  artistInfo[0][0] + "</b><br>" + artistInfo[0][2];
	if (mode == 0) {
		index = 0, distanceOld = 0, distanceNew = 0;
		sliderValues = $("#slider").slider("value").split(";");
		for (var i = 0; i < locationsMarkers.length; i++) {
			location = new google.maps.LatLng(locationsMarkers[i][4], locationsMarkers[i][5]);
			distanceNew = google.maps.geometry.spherical.computeDistanceBetween(start, location);
			if (i == 0 || (i != 0 && distanceNew < distanceOld)) {
    			distanceOld = distanceNew;
    			index = i;
    		}
    	}
    	if ((sliderValues[0] >= 0 && sliderValues[1] >= 0) || numberPastTourStops == 0) {
    		dateNextTourStop = locationsMarkers[0][0].split("<br>");
    		nextTourStop = "<b>" + locationsMarkers[0][2] + ", " +
	    		       	   locationsMarkers[0][3] + "</b><br>" +
	    		           locationsMarkers[0][1] + "<br>" + dateNextTourStop[1];
    		document.getElementById("nextAndPreviousTourStop").innerHTML = "<p><u>Next tour stop</u>:</p>" + nextTourStop;
    	} else if ((sliderValues[0] < 0 && sliderValues[1] < 0) || numberPastTourStops >= locationsMarkers.length) {
    		datePreviousTourStop = locationsMarkers[locationsMarkers.length-1][0].split("<br>");
    		previousTourStop = "<b>" + locationsMarkers[locationsMarkers.length-1][2] + ", " +
	    		               locationsMarkers[locationsMarkers.length-1][3] + "</b><br>" +
	    		               locationsMarkers[locationsMarkers.length-1][1] + "<br>" + datePreviousTourStop[datePreviousTourStop.length-1];
    		document.getElementById("nextAndPreviousTourStop").innerHTML = "<p><u>Previous tour stop</u>:</p>" + previousTourStop;
    	} else if (sliderValues[0] < 0 && sliderValues[1] >= 0 && numberPastTourStops < locationsMarkers.length) {
    		dateNextTourStop = locationsMarkers[numberPastTourStops][0].split("<br>");
    		nextTourStop = "<b>" + locationsMarkers[numberPastTourStops][2] + ", " +
	    		       	   locationsMarkers[numberPastTourStops][3] + "</b><br>" +
	    		           locationsMarkers[numberPastTourStops][1] + "<br>" + dateNextTourStop[1];
    		datePreviousTourStop = locationsMarkers[numberPastTourStops-1][0].split("<br>");
    		previousTourStop = "<b>" + locationsMarkers[numberPastTourStops-1][2] + ", " +
	    		               locationsMarkers[numberPastTourStops-1][3] + "</b><br>" +
	    		               locationsMarkers[numberPastTourStops-1][1] + "<br>" + datePreviousTourStop[datePreviousTourStop.length-1];
    		document.getElementById("nextAndPreviousTourStop").innerHTML = "<p><u>Next tour stop</u>:</p>" + nextTourStop + "<br><br>" +
    		                                                               "<p><u>Previous tour stop</u>:</p>" + previousTourStop;
    	}
    	dateNearestTourStop = locationsMarkers[index][0].split("<br>"); 
		nearestTourStop = "<b>" + locationsMarkers[index][2] + ", " +
					      locationsMarkers[index][3] + "</b><br>" +
					      locationsMarkers[index][1] + "<br>" + dateNearestTourStop[1];
		document.getElementById("nearestTourStop").innerHTML = "<p><u>Nearest tour stop</u>:</p>" + nearestTourStop;
    } else if (mode == 1) {
		document.getElementById("nextAndPreviousTourStop").innerHTML = "<p><i>No tour stops planned for this artist.</i></p>";
		document.getElementById("nearestTourStop").innerHTML = "";
	} else if (mode == 2) {
		document.getElementById("nextAndPreviousTourStop").innerHTML = "<p><i>No tour stops planned for this time slot.</i></p>";
		document.getElementById("nearestTourStop").innerHTML = "";
	} else if (mode == 3) {
		document.getElementById("nextAndPreviousTourStop").innerHTML = "";
    	document.getElementById("nearestTourStop").innerHTML = "";
	} else if (mode == 4) {
		document.getElementById("twitterActivity").innerHTML = "<i>No Tweets found for this artist.</i>";
	} else if (mode == 5) {
    	document.getElementById("twitterActivity").innerHTML = "";
	}
}

// hide and subsequently show tweet markers
function toggleMarkersAndInformationTweets(markersTweets, checked) {
	for (var i = 0; i < markersTweets.length; i++) {
    	markersTweets[i].setVisible(checked);
    }
    if (checked == true) {
    	if (markersTweets.length == 0) {
    		addInformation(4, null);	
    	}
    } else {
    	addInformation(5, null);
    }
}

// hide and subsequently show tour stop markers
function toggleMarkersAndInformationLocations(markersLocations, checked, numberPastTourStops) {
	for (var i = 0; i < markersLocations.length; i++) {
    	markersLocations[i].setVisible(checked);
    }
    if (checked == true) {
    	if (locationsMarkers.length != 0) {
    		addInformation(0, numberPastTourStops);
    	} else {
    		if (noTourStopsForArtist == true) {
    			addInformation(1, null);
    		} else {
    			addInformation(2, null);
    		}
    	}
    } else {
    	addInformation(3, null);
    }
}
</script>
</head>

<body onload="checkGeoloaction()">
<table><tr colspan=3>
<td width="25px"></td>
<td align="left">
	<table><tr valign="middle">
	<td width="100px">Artist name:</td>
	<td width="200px"><input type="text" id="artistName" style="width:100%;" onKeydown="if (event.keyCode == 13) checkInput(null, 0)"></input></td>
	<td width="35px" align="right"><div id="check"></div><div id="loading"></div></td>
	<td width="35px"></td>
	<td width="120px"><input type="checkbox" id="checkboxTourStops" checked>&nbsp;&nbsp;Tour stops</input></td>
	<td width="275px"><input type="checkbox" id="checkboxTweets">&nbsp;&nbsp;Twitter activity</input></td>
	<td></td>
	</tr><tr height="20px"</tr><tr>
	<td>Time slot:</td>
	<td><input type="slider" id="slider" value="-365;365"></input></td>
	<td colspan="2"></td>
	<td><input type="button" id="submit" value="Submit" class="button green" onclick="checkInput(null, 0)"></input></td>
	<td><input type="button" id="reset" value="Reset" class="button red" onclick="reset()" disabled></input></td>
	<td></td>
	</tr></table><br><br>
</td>
</tr>
<tr valign="top">
<td width="25px"></td>
<td>
	<div id="map" style="width: 1000px; height: 600px;"></div>
</td>
<td width="35px"></td>
<td>
	<div id="selectedArtist" align="left"></div><br>
	<div id="nextAndPreviousTourStop" align="left"></div><br>
	<div id="nearestTourStop" align="left"></div>
	<div id="twitterActivity" align="left"></div>
</td>
</tr>
</table>
</body>

</html>