<?php
	class WestWorldMediaBlockController extends BlockController {
		
		var $pobj;
		var $year_walk = 0;
		
		protected $btDescription = "Gathers West World Media listing feed then converts it to JSON for use in scripting.";
		protected $btName = "West World Media";
		protected $btTable = 'btWestWorldMedia';
		protected $btInterfaceWidth = "350";
		protected $btInterfaceHeight = "300";
		
		function get_data($url) {
		  $ch = curl_init();
		  $timeout = 5;
		  //curl_setopt($ch, CURLOPT_INTERFACE, "208.109.184.101");
		  //curl_setopt( $ch, CURLOPT_HTTPHEADER, array("REMOTE_ADDR: 208.109.186.158", "HTTP_X_FORWARDED_FOR: 208.109.186.158"));
		  //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Host: 208.109.186.158'));
		  curl_setopt($ch, CURLOPT_URL, $url);
		  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		  //curl_setopt($ch, CURLOPT_VERBOSE, true);
		  $data = curl_exec($ch);
		  curl_close($ch);
		  return $data;
		}
		
		private function getMovieDataWWM($movie_id){
			
			// gather movie data
			$movie_data = $this->get_data('http://webservice.cinema-source.com/3.0/?apikey=THCAF&query=movie&movie_id=70671'.trim($movie_id));
						
		return $movie_data;
		}
		
		// deprecated - no longer needed with the use of WWM movie data feed
		private function getMovieData($movie_name,$year = ''){
			
			// if year is set set query string
			if(!empty($year)){
				$year_qry = '&y='.$year;
			} else {
				$year_qry = '';
			}
			
			// gather movie data
			$movie_data = $this->get_data('http://www.omdbapi.com/?t='.urlencode($movie_name).'&plot=full'.$year_qry);

			$movie = json_decode($movie_data);
			
			// if movie is not found with year setting step down the year value
			if($movie->Response == 'False' && $this->year_walk < 6 && !empty($year)){
				$year--;
				$this->year_walk++;
				$this->getMovieData($movie_name,$year);
			}
			
			// check for movie exact name match
			
			return $movie_data;
		}
				
		public function view(){ 
			// link any necessary javascript files
			$html = Loader::helper('html');
			$cf = Loader::helper('file');
			$f = Loader::helper('concrete/file');
			// link any necessary javascript files
			//$this->addHeaderItem($html->javascript('/blocks/west_world_media/js/jquery.js'), 'CORE');
			// listing cache file
			$listCacheFle = $_SERVER['DOCUMENT_ROOT'].'/files/listingcache.js';
			
			//echo $listCacheFle;
						
			// gather listing data
			$link = 'http://webservice.cinema-source.com/2.9/?apikey=THCAF&query=radius&postalcode=23452&distance=35&schedule=yes&movies=yes&the=yes&showdate='.date('Ymd').'&enddate='.date('Ymd',strtotime('+7 days'));
			if(!$updated_listing = Cache::get('movieFeed',$link)){
				
				// gather theater movie listing
				$listing = $this->get_data($link);
				
				// convert to PHP array
				$listing = json_decode(json_encode((array) simplexml_load_string($listing)), 1);
				
				// walk through listing data and only keep cinema cafe data
				$updated_listing = array();
				//print_r($listing->theaters->house);
				foreach($listing[theaters][house] as $cur_loc){
					//print_r($cur_loc);
					if(strstr($cur_loc[name],'Cinema Cafe')){
						$updated_listing[] = $cur_loc;
					}
				}
				
				// update time data and gather movie synopsys and image data
				$id = 0;
				// walk through all locations
				foreach($updated_listing as $location){
					$mid = 0;
					// walk through all movies per location
					foreach($location[schedule][movie] as $movie){
						
						// gather movie data
						if(!$movie_data = Cache::get('movieData',$movie[movie_id])){
							
							$movie_data = $this->getMovieDataWWM($movie[movie_id]);
							
							// convert to PHP array
							$movie_data = json_decode(json_encode((array) simplexml_load_string($movie_data)), 1);
							
							Cache::set('movieData', $movie[movie_id], $movie_data, 86400);
						}
						
						// walk through movie data and save images to media library where needed
						if(($movie_data[movies][movie][photos][photo] != 'N/A') && $movie_data->Title != ''){
							
							// download image save to cache then delete
							$myFile = ereg_replace("[^A-Za-z0-9-]", "_", trim($movie_data->Title)).'.jpg';
							$myFile = preg_replace('/[_]+/', '_', $myFile);
							
							// check for existing file
							$db = Loader::db();
							$sql = 'SELECT * FROM FileVersions WHERE fvIsApproved = 1 AND fvFilename = "'.$myFile.'" LIMIT 1';
							if (!$det = $db->GetRow($sql)) {

								// pull remote file
								$new_image = $this->get_data($movie_data->Poster);
								
								$myFileLocation = $_SERVER['DOCUMENT_ROOT'].'/files/cache/'.$myFile;
								if(!file_exists($myFileLocation)){
									$fh = fopen($myFileLocation, 'w');
									$stringData = $new_image;
									fwrite($fh, $stringData);
									fclose($fh);
								}
								
								if(!file_exists($myFileLocation) || filesize($myFileLocation) > 0){
									Loader::library('file/importer');
									$fi = new FileImporter();
									$newFile = $fi->import($myFileLocation,$myFile);
									if(!is_object($newFile)) {
									  echo $fi->getErrorMessage($newFile);
									}
									
									// delete cached file
									unlink($myFileLocation);
								}
								$sql = 'SELECT * FROM FileVersions WHERE fvIsApproved = 1 AND fvFilename = "'.$myFile.'" LIMIT 1';
								$det = $db->GetRow($sql);
								$f = File::getByID($det[fID]);
							    $fv = $f->getApprovedVersion();
							    $path = $fv->getRelativePath();
							} else {
								$f = File::getByID($det[fID]);
								$fv = $f->getApprovedVersion();
								$path = $fv->getRelativePath();
							}
						} else {
							$path = 'null';
						}
						
						$movie_data->Poster = $path;
						
						// convert movie data object to array
						$new_movie_data = array();
						foreach($movie_data as $mdid => $mddata){
							$new_movie_data[strtolower($mdid)] = $mddata;
						}
						
						// save movie data to array
						$updated_listing[$id][schedule][movie][$mid][movie_data] = $new_movie_data;

						// convert show times from 24 hour to 12 hour times
						if(is_array($movie[showtimes])){
							foreach($movie[showtimes] as $sgid => $showgroup){
								// clean attribute name
								if($updated_listing[$id][schedule][movie][$mid][showtimes][$sgid]['@attributes']){
									$updated_listing[$id][schedule][movie][$mid][showtimes][$sgid][attributes] = $updated_listing[$id][schedule][movie][$mid][showtimes][$sgid]['@attributes'];
									//print_r($updated_listing[$id][schedule][movie][$mid][showtimes][$sgid][attributes]);
									if(is_array($updated_listing[$id][schedule][movie][$mid][showtimes][$sgid][attributes])){
										$show_date = $updated_listing[$id][schedule][movie][$mid][showtimes][$sgid][attributes]['date'];
										$updated_listing[$id][schedule][movie][$mid][showtimes][$sgid][perfd] = date('mdY',strtotime($show_date));
									}
									//unset($updated_listing[$id][schedule][movie][$mid][showtimes][$sgid]['@attributes']);
									// clean time values
									if(is_array($movie[showtimes][$sgid][showtime])){
										foreach($movie[showtimes][$sgid][showtime] as $sid => $showtime){
											$updated_listing[$id][schedule][movie][$mid][showtimes][$sgid][showtime_24][$sid] = trim($showtime);
											$updated_listing[$id][schedule][movie][$mid][showtimes][$sgid][showtime][$sid] = date("g:ia", strtotime(trim($showtime)));
										}
									} else {
										$updated_listing[$id][schedule][movie][$mid][showtimes][$sgid][showtime_24] = trim($updated_listing[$id][schedule][movie][$mid][showtimes][$sgid][showtime]);
										$updated_listing[$id][schedule][movie][$mid][showtimes][$sgid][showtime] = date("g:ia", strtotime(trim($updated_listing[$id][schedule][movie][$mid][showtimes][$sgid][showtime])));
									}
								} else {
									$updated_listing[$id][schedule][movie][$mid][showtimes][attributes] = $updated_listing[$id][schedule][movie][$mid][showtimes]['@attributes'];
									//print_r($updated_listing[$id][schedule][movie][$mid][showtimes][$sgid][attributes]);
									$show_date = $updated_listing[$id][schedule][movie][$mid][showtimes][attributes]['date'];
									$updated_listing[$id][schedule][movie][$mid][showtimes][perfd] = date('mdY',strtotime($show_date));
									
									//unset($updated_listing[$id][schedule][movie][$mid][showtimes][$sgid]['@attributes']);
									// clean time values
									$updated_listing[$id][schedule][movie][$mid][showtimes][showtime_24] = trim($updated_listing[$id][schedule][movie][$mid][showtimes][showtime]);
									$updated_listing[$id][schedule][movie][$mid][showtimes][showtime] = date("g:ia", strtotime(trim($updated_listing[$id][schedule][movie][$mid][showtimes][showtime])));
									break;
								}
							}
						}
						$mid++;
					}
					
					$id++;
				}
				
				//print_r($updated_listing);
								
				// convert to JSON
				$updated_listing = json_encode($updated_listing);
				
				Cache::set('movieFeed', $link, $updated_listing,120);
				
				$updated_listing = 'var jsonval = \'' . str_replace("'","\'",$updated_listing) . '\';';
				
				// save as static file
				touch($listCacheFle);
				$fh = fopen($listCacheFle, 'w');
				fwrite($fh, $updated_listing);
				fclose($fh);

			}
			
			// add json variable to footer
			$this->addFooterItem($html->javascript('/files/listingcache.js'));
			
			// transfer usable variables
			//$this->set('jsAPI', $updated_listing);
		}		
		
	}
	
?>