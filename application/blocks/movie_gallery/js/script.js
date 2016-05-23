var movieListing = {};
// show full synopsis
var fullSynop = function(movie_id){
	$('.movie-syn-'+movie_id).html(movieData[movie_id].synopsis);
};
$(function() {
	'use strict';
	
	// reset carousel and current selected image values
	$('#body-static-inner .selected-image').html("");
	$('#body-static-inner .carousel').html("");

	$("#body-static-inner .carousel").smoothDivScroll({
		autoScrollingMode: "onStart",
		autoScrollingInterval: 50
	});

	movieListing = {
		itmCnt: 0,
		listing: {},
		// generate listing item show times
		genBuyLnk: function(showData){
			var buyLink = '',
				showTime = '',
				showTime12 = '',
				showDateTime = '';
			
			// compare date strings
			if(moment(showData.selDate).format("YYYYMMDD") == showData.showDate.substring(0,8)){
				
				if(moment(showData.showDate,["YYYYMMDDHHmm"]) > moment()){ 
					// convert from 24 hour to 12 hour time
					showTime = showData.showDate.substr(-4);
					showTime12 = moment(showTime,["HHmm"]).format("h:mm A");
					showDateTime = moment(showData.showDate,["YYYYMMDDHHmm"]).format("dddd, MMMM D, YYYY h:mm A");
					
					buyLink = '<a href="javascript:void(0)" onclick="orderSys.pickTickets('+showData.show.ID+','+showData.movie.movie_id+',\''+showDateTime+'\')">' + showTime12 + '</a>';
					
					return buyLink;
				}
			}
		},
		// generate listing item show times
		getShowTimes: function(movie){
			var m = 0,
				s = 0,
				showLimit = 0,
				shows = [],
				showDate = '',
				selDate = $('#listing-date').val(),
				films = rtsListingData.ShowSchedule.Films.Film,
				limit = films.length,
				buyLink = '',
				results = [],
				showDateTime = '';
				
			// walk through found movie showtimes
			for(m; m < limit; ++m){
				// check cc film code match
				if(films[m].CSCode === movie.movie_id){
					showLimit = films[m].Shows.Show.length;
					shows = films[m].Shows.Show;
					
					if(typeof showLimit === 'undefined'){
						showDate = String(shows.DT);
						buyLink = movieListing.genBuyLnk({selDate:selDate, showDate: showDate, movie:movie, show:shows});
						if(buyLink){
							results.push(buyLink);
						}
					} else {
						// compare event dates
						for(s = 0; s < showLimit; ++s){
							showDate = String(shows[s].DT);
							
							buyLink = movieListing.genBuyLnk({selDate:selDate, showDate: showDate, movie:movie, show:shows[s]});
							if(buyLink){
								results.push(buyLink);
							}
						}
					}
				}
			}
			
			// return found results
			return '<div class="buy-tickets"><p>Select a movie time to buy tickets online now</p><p>' + results.join(' ') + '</p></div>';
		},
		// combine array values
		combineArr: function(arr){
			if( Object.prototype.toString.call( arr ) === '[object Array]' ) {
				return arr.join(', ');
			} else {
				return arr
			}
		},
		printMovieData: function(id){
			$('#movie-data-op').html(this.listing[id]);
		},
		// generate listing item
		createItem: function(movie){
			// gather extended movie data
			var movieInfo = movieData[movie.movie_id];
			// assemble movie listing object
			var itm  = '<div class="row">';
				itm += '<div class="col-md-2">'+'<a target="_blank" href="http://media.westworldmedia.com/thbmb/mp4/'+movie.movie_id+'_high.mp4"><img src="'+movieInfo.photos.photo+'"></a>'+'</div>';
				itm += '<div class="col-md-4">';
				itm += '<h4>' + movie.movie_name + '</h4>';
				itm += '<p>Rated: ' + movie.movie_rating + '<br>';
				itm += 'Runtime: ' + movieInfo.runtime + ' minutes';
				//if(movieInfo.website.length > 0) itm += '<br>Website: <a href="'+movieInfo.website+'" target="_blank">' + movieInfo.website + '</a>';
				itm += '</p>';
				itm += '<p>Genre: ' + this.combineArr(movieInfo.genres.genre) + '</p>';
				itm += '<p>Staring: ' + this.combineArr(movieInfo.actors.actor) + '</p>';
				itm += '<p>Director(s): ' + this.combineArr(movieInfo.directors.director) + '</p>';
				itm += '</div>';
				itm += '<div class="col-md-4">';
					
				// print showtimes for selected date
				itm += this.getShowTimes(movie);
				
				itm += '</div>';
				itm += '<div class="col-md-12">';
				itm += '<p class="movie-syn-'+movie.movie_id+'">'+movieInfo.synopsis.substring(0,120)+'...'+'<a href="javascript:fullSynop('+movie.movie_id+')">Show More</a></p>';
				itm += '</div>';
				itm += '</div>';
				
			// store listing item
			this.listing[movie.movie_id] = itm;
			
			// gen carousel image
			$('#body-static-inner .carousel .scrollableArea').append('<img style="width:135px;height:auto" class="'+movie.movie_id+'" src="'+movieInfo.photos.photo+'">');

			// enable image click action
			$('#body-static-inner .carousel img.'+movie.movie_id).bind('click', function(){
				var id = $(this).attr('class');
				movieListing.printMovieData(id);
			});
			
			// update list cnt
			this.itmCnt += 1;
				
		},
		// generate block listing based on selected date
		genListing: function(){
			var i = 0,
				j = 0,
				selDate = $('#listing-date').val(),
				limit = listingData.length;
				
			// walk though found movies
			for(i; i < limit; ++i){

				// determine showtimes layout
				if(listingData[i].showtimes['@attributes']){
					if(listingData[i].showtimes['@attributes'].date == selDate){
						this.createItem(listingData[i]);
					}
				} else {
					var limitShows = listingData[i].showtimes.length;
					
					for(j = 0; j < limitShows; ++j){
						if(listingData[i].showtimes[j]['@attributes'].date == selDate){
							this.createItem(listingData[i]);
						}
					}
				}
			}
			
			// add listing to block output
			//$('.listing-output').html(this.listing);
		}
	};
	
	// add found filter dates
	$.each(dateOpts, function (index, value) {
		$('#listing-date').append($('<option/>', { 
			value: index,
			text : moment(index).format("dddd, MMMM D, YYYY")
		}));
	});  
	
	// load initial movie listing
	// reset listing var
	movieListing.listing = {};
	movieListing.itmCnt = 0;
	movieListing.genListing();
	movieListing.genListing();
		
	// bind change movie time selection
	$('#listing-date').bind('change', function(){
		movieListing.listing = {};
		movieListing.itmCnt = 0;
		$('#body-static-inner .carousel .scrollableArea').html('');
		movieListing.genListing();
		movieListing.genListing();
		
	});
});


//'use strict';
//if (window['defLocation'] != undefined)  var defLocation = 0;
//var obj = '';
//var movieobj = '';
//
//$(function() {
//
//		// parse listing data
//		obj = listingData;
//		// parse movie data
//		movieobj = movieData;
//		
//		// gather selected date value
//		selDate = $('#now-playing-form #showDate').val();
//		
//		// reset carousel and current selected image values
//		$('#body-static-inner .selected-image').html("");
//		$('#body-static-inner .carousel').html("");
//				
//		// set default listing data
//		printSlides(defLocation);
//		
//		// update listing on selection
//		$('#now-playing-form #location').change(function(){
//			
//			var curID = $(this).val();
//			
//			$('#body-static-inner .selected-image').html("");
//			$('#body-static-inner .carousel .scrollableArea').html("");
//	
//			printSlides(curID);		
//		});		
//		
//		// update listing on selection
//		$('#now-playing-form #showDate').change(function(){
//			
//			selDate = $(this).val();
//			
//			$('#body-static-inner .selected-image').html("");
//			$('#body-static-inner .carousel .scrollableArea').html("");
//	
//			printSlides(defLocation);		
//		});		
//
//	}
//
//});
//
//var getSlideData = function(id){
//	var movieCnt = 0;
//	
//	for (var i = 0; i < obj[id].schedule.movie.length; i++) {
//		var foundMovie = 0;
//		
//		// add banners for movies with multiple showtimes
//		// find movie date if equal to selected date
//		for (var ii = 0; ii < obj[id].schedule.movie[i].showtimes.length; ii++) {
//
//			if(obj[id].schedule.movie[i].showtimes[ii].attributes.date == selDate){
//								
//					var movie_data_str = movieobj[obj[id].schedule.movie[i].movie_id];
//					
//					if(movie_data_str.photos.photo != 'null'){
//							if(movie_data_str.photos.photo != '/files/'){
//								foundMovie = 1;
//								movieCnt++;
//								$('#body-static-inner .carousel .scrollableArea').append('<img style="width:120px;height:auto" class="'+i+'" src="'+movie_data_str.photos.photo+'">');
//								if(movieCnt == 1) {
//									printMovieData(i);
//									//setLargeImage(i);
//								}
//						}
//				}
//			}
//		}
//		
//		if(foundMovie == 0){
//			if(obj[id].schedule.movie[i].showtimes.length){
//				if(obj[id].schedule.movie[i].showtimes[0].attributes.date == selDate){
//					var movie_data_str = movieobj[obj[id].schedule.movie[i].movie_id];
//					
//					if(movie_data_str.photos.photo != 'null'){
//							if(movie_data_str.photos.photo != '/files/'){
//									foundMovie = 1;
//									movieCnt++;
//									$('#body-static-inner .carousel .scrollableArea').append('<img style="width:120px;height:auto" class="'+i+'" src="'+movie_data_str.photos.photo+'">');
//									if(movieCnt == 1) {
//										printMovieData(i);
//										//setLargeImage(i);
//									}
//						}
//					}
//				}
//			} else {
//				if(obj[id].schedule.movie[i].showtimes.attributes.date == selDate){
//					var movie_data_str = movieobj[obj[id].schedule.movie[i].movie_id];
//					
//					if(movie_data_str.photos.photo != 'null'){
//							if(movie_data_str.photos.photo != '/files/'){
//									foundMovie = 1;
//									movieCnt++;
//									$('#body-static-inner .carousel .scrollableArea').append('<img style="width:120px;height:auto" class="'+i+'" src="'+movie_data_str.photos.photo+'">');
//									if(movieCnt == 1) {
//										printMovieData(i);
//										//setLargeImage(i);
//									}
//								}
//					}
//				}
//			}
//		}
//		
//		// add banners for movies with a single showtime
//	}
//}
//
//var printSlides = function(id){
//
//	// start carousel
//	$("#body-static-inner .carousel").smoothDivScroll({
//		autoScrollingMode: "onStart",
//		autoScrollingInterval: 50
//	});
//	// get slides twice to ensure carousel is enabled
//	getSlideData(id);
//	getSlideData(id);
//	
//	// enable image click action
//	$('#body-static-inner .carousel img').click(function(){
//		var idx = $(this).attr('class');
//		printMovieData(idx);
//		//setLargeImage(idx);
//	});
//	
//}
//
//var printMovieData = function(id){
//	
//	var movie_data_str = movieobj[obj[defLocation].schedule.movie[id].movie_id];
//	
//	var movie_data = '<div class="clearfix"><a target="_blank" href="http://trailers.movie-previews.com/film.aspx?m='+obj[defLocation].schedule.movie[id].movie_id+'"><img src="'+movie_data_str.photos.photo+'" style="width:120px;height:auto"></a><div style="width:507px;float:right;"><h2><a style="text-decoration:none;color:#000;" target="_blank" href="http://trailers.movie-previews.com/film.aspx?m='+obj[defLocation].schedule.movie[id].movie_id+'">'+obj[defLocation].schedule.movie[id].movie_name+'</a></h2><p>Runtime: '+movie_data_str.runtime+' minutes<br>Released: '+movie_data_str.release_dates.release+'</p><p>'+movie_data_str.synopsis+'</p></div></div>';
//	
//	$('#movie-data-op').html(movie_data);
//	
//}
//
//var setLargeImage = function(id){
//
//	var movie_data_str = movieobj[obj[defLocation].schedule.movie[id].movie_id];
//	
//	var movie_data = '<img src="'+movie_data_str.photos.photo+'" style="width:220px;height:auto">';
//	
//	$('#body-static-inner .selected-image').html(movie_data);
//}
