<!-- 	BACHELOR PROJECT	Summer semester 2013	Martin Hellwagner (0956048)--><html><head><title>Bachelor project</title><meta charset="utf-8"><script type="text/javascript" src="http://code.jquery.com/jquery-1.9.1.js"></script>  <script type="text/javascript" src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script><script type="text/javascript">var locationsNoCorrectionNeeded;// preparing datafunction getLocationsSongkick(artist) {	var key = "I4IgsNX7iuFZMgvb";	var urlPast = "http://api.songkick.com/api/3.0/artists/mbid:" + 			      artist + "/gigography.json?apikey=" + key + "&jsoncallback=?";	var urlFuture = "http://api.songkick.com/api/3.0/artists/mbid:" + 			        artist + "/calendar.json?apikey=" + key + "&jsoncallback=?";	locationsNoCorrectionNeeded = new Array();	fetchLocationsSongkickPast(urlPast, urlFuture, 0, 1);}// fetching past event locations from Songkick APIfunction fetchLocationsSongkickPast(urlPast, urlFuture, index, page) {	var today = new Date();	var minimumDate = new Date(today.getTime() - ((24 * 60 * 60 * 1000) * (365 + 1)));    minimumDate = Date.UTC(minimumDate.getFullYear(), minimumDate.getMonth(), minimumDate.getDate());	$.ajax({        url: urlPast + "&page=" + page,        async: false,        dataType: 'jsonp',    	success: function(data) {    	    		if (data.resultsPage.results.event != null) {    			for (var i = 0; i < data.resultsPage.results.event.length; i++) {    				if (data.resultsPage.results.event[i].type == "Concert") {    					var date = new Date(data.resultsPage.results.event[i].start.date);						var currentDate = Date.UTC(date.getFullYear(), date.getMonth(), date.getDate());            			if (currentDate >= minimumDate) {            				var venue = data.resultsPage.results.event[i].venue.displayName;            				var city = data.resultsPage.results.event[i].location.city.split(",");            				var coordinatesX = data.resultsPage.results.event[i].location.lat;            				var coordinatesY = data.resultsPage.results.event[i].location.lng;            				var cancelled = 0;            				locationsNoCorrectionNeeded[index] = new Array(date, venue, city[0], city[1].substring(1), coordinatesX, coordinatesY, cancelled);            				index++;            			}            		}            	}            	if (page == 1 && data.resultsPage.totalEntries > (365 + 1)) {            		var skippedPages = Math.floor((365 + 1) / 50);            		var totalPages = Math.floor(data.resultsPage.totalEntries / 50);            		page = page + (totalPages - skippedPages);            	}            	if (data.resultsPage.totalEntries > (page * 50)) {            		fetchLocationsSongkickPast(urlPast, urlFuture, index, page+1);            	} else {            		if (locationsNoCorrectionNeeded.length == 0) {            			locationsNoCorrectionNeeded[0] = new Array(-1, null, null, null, null, null, null);					}            		fetchLocationsSongkickFuture(urlFuture, index, 1);            	}            } else {            	locationsNoCorrectionNeeded[0] = new Array(-1, null, null, null, null, null, null);    			fetchLocationsSongkickFuture(urlFuture, 0, 1);    		}        }    });}// fetching future event locations from Songkick APIfunction fetchLocationsSongkickFuture(urlFuture, index, page) {	$.ajax({        url: urlFuture + "&page=" + page,        async: false,        dataType: 'jsonp',    	success: function(data) {    		if (data.resultsPage.results.event != null) {    			for (var i = 0; i < data.resultsPage.results.event.length; i++) {    				if (data.resultsPage.results.event[i].type == "Concert") {    					var date = new Date(data.resultsPage.results.event[i].start.date);            			var venue = data.resultsPage.results.event[i].venue.displayName;            			var city = data.resultsPage.results.event[i].location.city.split(",");            			var coordinatesX = data.resultsPage.results.event[i].location.lat;            			var coordinatesY = data.resultsPage.results.event[i].location.lng;            			var cancelled = 0;            			locationsNoCorrectionNeeded[index] = new Array(date, venue, city[0], city[1].substring(1), coordinatesX, coordinatesY, cancelled);            			index++;            		}            	}            	if (data.resultsPage.totalEntries > (page * 50)) {            		fetchLocationsSongkickFuture(urlFuture, index, page+1);            	} else {            		if (locationsNoCorrectionNeeded.length == 0) {            			locationsNoCorrectionNeeded[0] = new Array(-1, null, null, null, null, null, null);					}            		checkInput(locationsNoCorrectionNeeded, 5);            	}            } else {            	checkInput(locationsNoCorrectionNeeded, 5);    		}        }    });}</script></head></html>