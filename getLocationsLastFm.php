<!-- 	BACHELOR PROJECT	Summer semester 2013	Martin Hellwagner (0956048)--><html><head><title>Bachelor project</title><meta charset="utf-8"><script type="text/javascript" src="http://code.jquery.com/jquery-1.9.1.js"></script>  <script type="text/javascript" src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script><script type="text/javascript">var locationsCorrected, locationsUncorrected;// preparing datafunction getLocationsLastFm(artist) {	var key = "d24f248c65bb443e1dbe4b8178350f9d";	var urlPast = "http://ws.audioscrobbler.com/2.0/?method=artist.getpastevents&mbid=" +			        artist + "&api_key=" + key + "&autocorrect=1&format=json";	var urlFuture = "http://ws.audioscrobbler.com/2.0/?method=artist.getevents&mbid=" +			        artist + "&api_key=" + key + "&autocorrect=1&format=json";	locationsCorrected = new Array();	locationsUncorrected = new Array();	fetchLocationsLastFmPast(urlPast, urlFuture, 0, 1, 0);}// fetching past event locations from last.fm APIfunction fetchLocationsLastFmPast(urlPast, urlFuture, index, page, totalPages) {	var today = new Date();	var minimumDate = new Date(today.getTime() - ((24 * 60 * 60 * 1000) * (365 + 1)));    minimumDate = Date.UTC(minimumDate.getFullYear(), minimumDate.getMonth(), minimumDate.getDate());	$.ajax({    	url: urlPast + "&page=" + page,    	async: false,    	dataType: "json",    	success: function(data) {    	    if (data != null && data != "" && data.events.event != null && data.events.event[0] != null) {    			for (var i = 0; i < data.events.event.length; i++) {            		var date = new Date(data.events.event[i].startDate);					var currentDate = Date.UTC(date.getFullYear(), date.getMonth(), date.getDate());            		if (currentDate >= minimumDate) {            			var venue = data.events.event[i].venue.name;            			var city = data.events.event[i].venue.location.city;            			var country = data.events.event[i].venue.location.country.split(",");             			var coordinatesX = data.events.event[i].venue.location["geo:point"]["geo:lat"];            			var coordinatesY = data.events.event[i].venue.location["geo:point"]["geo:long"];            			var cancelled = data.events.event[i].cancelled;            			locationsUncorrected[index] = new Array(date, venue, city, country[0], coordinatesX, coordinatesY, cancelled);            			index++;            		}        		}            	if (page == 1) {            		totalPages = data.events["@attr"].totalPages;        			if (totalEvents = data.events["@attr"].total > (365 + 1)) {        				var skippedPages = Math.floor((365 + 1) / 50);            			page = page + (totalPages - skippedPages);            		}            	}            	if (totalPages > page) {            		fetchLocationsLastFmPast(urlPast, urlFuture, index, page+1, totalPages);            	} else {            		if (locationsUncorrected.length == 0) {            			locationsUncorrected[0] = new Array(-1, null, null, null, null, null, null);					}            		fetchLocationsLastFmFuture(urlFuture, index, 1, 0);				}            } else {                locationsUncorrected[0] = new Array(-1, null, null, null, null, null, null);            	fetchLocationsLastFmFuture(urlFuture, 0, 1, 0);            }       	        }    });}// fetching future event locations from last.fm APIfunction fetchLocationsLastFmFuture(urlFuture, index, page, totalPages) {	$.ajax({    	url: urlFuture + "&page=" + page,    	async: false,    	dataType: "json",    	success: function(data) {    		if (data != null && data != "" && data.events.event != null && data.events.event[0] != null) {    			for (var i = 0; i < data.events.event.length; i++) {            		var date = new Date(data.events.event[i].startDate);            		var venue = data.events.event[i].venue.name;            		var city = data.events.event[i].venue.location.city;            		var country = data.events.event[i].venue.location.country.split(",");             		var coordinatesX = data.events.event[i].venue.location["geo:point"]["geo:lat"];            		var coordinatesY = data.events.event[i].venue.location["geo:point"]["geo:long"];            		var cancelled = data.events.event[i].cancelled;            		locationsUncorrected[index] = new Array(date, venue, city, country[0], coordinatesX, coordinatesY, cancelled);            		index++;        		}            	if (page == 1) {        			totalPages = data.events["@attr"].totalPages;        		}        		if (totalPages > page) {            		fetchLocationsLastFmFuture(urlFuture, index, page+1, totalPages);            	} else {            		if (locationsUncorrected.length == 0) {            			locationsUncorrected[0] = new Array(-1, null, null, null, null, null, null);					}            		checkInput(locationsUncorrected, 4);            	}            } else {            	checkInput(locationsUncorrected, 4);            }        }    });}// correcting locations by removing cancelled eventsfunction correctLocations(locationsUncorrected) {	var index = 0;	for (var i = 0; i < locationsUncorrected.length; i++) {    	if (locationsUncorrected[i][0] == -1 || locationsUncorrected[i][6] == 0) {    		locationsCorrected[index] = locationsUncorrected[i];    		index++;    	}	}    checkInput(locationsCorrected, 4);}</script></head></html>