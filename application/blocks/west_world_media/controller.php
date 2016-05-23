<?php
namespace Application\Block\WestWorldMedia;

use Illuminate\Filesystem\Filesystem;

use \Concrete\Core\Block\BlockController;
use Package;
use Core;
use View;
use Page;
use URL;
use Loader;
use BlockType;
use FilePermissions;
use FileImporter;

class Controller extends BlockController{
	
	var $pobj, $movie_listing;
	var $year_walk = 0;
	
	protected $btDescription = "Gathers West World Media listing feed then converts it to JSON for use in scripting.";
	protected $btName = "West World Media";
	protected $btInterfaceWidth = "350";
	protected $btInterfaceHeight = "300";

	private function get_data($url) {
	  $ch = curl_init();
	  $timeout = 10;
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
	
	// gather movie data from WWM
	private function getMovieDataWWM($movie_id){
		
		// gather movie data
		$movie_data = json_decode(json_encode((array) simplexml_load_string($this->get_data('http://webservice.cinema-source.com/3.8/?apikey=THBMB&query=movie&stars=yes&photos=all&movie_id='.trim($movie_id)))), 1);

		return $movie_data['movie'];
	}
	
	// pull location listing data
	private function getWWMListingData(){
		
		// configure show dates
		$startDate = Date('Ymd');
		$endDate = Date('Ymd', strtotime('+4 months'));
		
		// gather movie data
		$movie_data = $this->get_data('http://webservice.cinema-source.com/3.8/?apikey=YOUAPIKEY&query=theater&schedule=yes&house_id=34510&sd=yes&showdate='.$startDate.'&enddate='.$endDate);

		return $movie_data;					
	}
	
	// pull location listing data
	private function getRTSListing(){
				
		// gather movie data
		$movie_data = json_decode(json_encode((array) simplexml_load_string($this->get_data('http://72352.formovietickets.com:2235/showtimes.xml'))), 1);

		return $movie_data;					
	}

	// get data from RTS
	private function getRTSData($packet=''){
		
		// generate XML request packet
		if($packet == 'ShowTimeXml'){
			$xml = new \SimpleXMLElement('<Request/>');
			$xml->addChild('Version', 1);
			$xml->addChild('Command', 'ShowTimeXml');
			$xml->addChild('ShowAvalTickets', 1);
			$xml->addChild('ShowSales', 1);
			$xml->addChild('ShowSaleLinks', 1);
			$packet = $xml->asXML();
		}
		
		//die($xml->asXML());
		
		$ch = curl_init();
		$timeout = 10;
		curl_setopt($ch, CURLOPT_SSLVERSION, 1);
		curl_setopt($ch, CURLOPT_URL, 'https://5.formovietickets.com/Data.ASP');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_PORT, 2235);
		curl_setopt($ch, CURLOPT_POST, true );
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		// authenticate user
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_USERPWD, 'test:test');
		// send well formed request packet
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $packet );
		//curl_setopt($ch, CURLOPT_VERBOSE, true);
		$data = curl_exec($ch);
		curl_close($ch);

		return $data;
	}
		
	public function view(){ 
		// build listing data from West World Media provided XML feed
		$this->buildListing();
	}
	
	// look up available ticketing options
	private function ticketLookup($rtsListing, $ticketID){
		foreach($rtsListing['ShowSchedule']['Tickets']['Ticket'] as $ticket){
			if($ticket['Code'] == $ticketID){
				return $ticket;
			}
		}
	}
	
	// generate movie listing data
	private function buildListing(){
		// link any necessary javascript files
		$html = Loader::helper('html');
		$f = Loader::helper('concrete/file');
		$expensiveCache = \Core::make('cache/expensive');

		// listing cache file
		$listCacheFle = DIR_FILES_UPLOADED_STANDARD.'/listingcache.js';
		
		// init vars		
		$movie_data_arr = array();
		$sel_dates_arr = array();
		$soon_dates_arr = array();
		
		//die($this->get_data('http://webservice.cinema-source.com/3.8/?apikey=THBMB&query=movie&movie_id=211358'));
							
		// retrieve cached values
		$updated_listing = $expensiveCache->getItem('movieFeed');
		//$updated_listing->clear();
		$listCheck = $updated_listing;
		$updated_listing = $updated_listing->get();
		
		//die($this->get_data('http://webservice.cinema-source.com/3.8/?apikey=THBMB&query=movie&search=AL13222'));
		
		// reload listing if cache is found to be empty
		if($listCheck->isMiss()){

			// gather theater movie listing
			$rtsListing = json_decode(json_encode((array) simplexml_load_string($this->getRTSData('ShowTimeXml'))), 1);
			$listing = $this->getWWMListingData();
			//$rtsListing = $this->getRTSListing();
			
			// 11-29-2013 force reload if listing data if blank
	//		if(empty($listing)){
//				$this->buildListing();
//			}
			
			// convert to PHP array
			$listing = json_decode(json_encode((array) simplexml_load_string($listing)), 1);

			// store movie data
			$movie_listing = $listing['house']['schedule']['movie'];
			$updated_listing = $movie_listing;

			// walk through all movies per location
			foreach($movie_listing as $movie){
				
				// check for movie tickets film code
				if(!empty($movie['movie_id'])){
					// gather movie data
					$movie_data = $expensiveCache->getItem('movieData'.$movie['movie_id']);
					// store in cache if not found
					if ($movie_data->isMiss()) {
						$movie_data->lock();
						$movie_data_new = $this->getMovieDataWWM($movie['movie_id']);
						$movie_data->set($movie_data_new,86400);
					}
					$movie_data = $movie_data->get();
					
					// add to movie data array
					$movie_data_arr[$movie['movie_id']] = $movie_data;

					// generate selectable date hash
					// check for show within assigned RTS showtimes
					foreach($rtsListing['ShowSchedule']['Films']['Film'] as $film){
						
						// check cinema source film code vs stored RTS cinema source film code
						if($film['CSCode'] == $movie['movie_id']){
							
							// check for single or multiple shows
							$shows = array();
							if(empty($film['Shows']['Show']['DT'])){
								$shows = $film['Shows']['Show'];
							} else {
								$shows[0] = $film['Shows']['Show'];
							}
					
							// add date/time values to selectable date hash
							foreach($shows as $curShow){
								// check for ticket internet availability
								$tickets = array();
								if(empty($curShow['TIs']['TI']['C'])){
									$tickets = $curShow['TIs']['TI'];
								} else {
									$tickets[0] = $curShow['TIs']['TI'];
								}
								foreach($tickets as $TI){
									$ticket = $this->ticketLookup($rtsListing, $TI['C']);
									// store current showtimes
									// convert date/time
									$showDate = substr($curShow['DT'], 0, 8);
									if(!empty($ticket) && empty($ticket['HideOnInternet'])){
																				
										if($movie['showtimes']['@attributes']){
											$stDate = explode('/',$movie['showtimes']['@attributes']['date']);
											$stDate = $stDate[2] . sprintf("%02d", $stDate[0]) . $stDate[1];
											if($stDate == $showDate){
												$sel_dates_arr[$movie['showtimes']['@attributes']['date']] = strtotime($movie['showtimes']['@attributes']['date']);
											}
										} else {
											foreach($movie['showtimes'] as $curShowTime){
												$stDate = explode('/',$curShowTime['@attributes']['date']);
												$stDate = $stDate[2] . sprintf("%02d", $stDate[0]) . $stDate[1];
												if($stDate == $showDate){
													$sel_dates_arr[$curShowTime['@attributes']['date']] = strtotime($curShowTime['@attributes']['date']);
												}
											}
										}
										
									// store upcoming showtimes
									} elseif (count($tickets) == 1 && !empty($ticket) && $ticket['HideOnInternet'] == 1 && $ticket['Name'] == 'rSupersvr') {
																				
										if($movie['showtimes']['@attributes']){
											$stDate = explode('/',$movie['showtimes']['@attributes']['date']);
											$stDate = $stDate[2] . sprintf("%02d", $stDate[0]) . $stDate[1];
											if($stDate == $showDate){
												$soon_dates_arr[$movie['showtimes']['@attributes']['date']] = strtotime($movie['showtimes']['@attributes']['date']);
											};
										}

									}
								}
							}
						
						}
					
					}
	
					//print_r($movie_data);
					
					// walk through movie data and save images to media library where needed
					if($movie_data['photos']['photo'] || $movie_data['hiphotos']['photo']){
						
						// download image save to cache then delete
						$myFile = ereg_replace("[^A-Za-z0-9-]", "_", trim($movie_data['name'])).'.jpg';
						$myFile = preg_replace('/[_]+/', '_', $myFile);
						
						// check for existing poster file
						$db = Loader::db();
						$det = $db->GetRow('SELECT * FROM FileVersions WHERE fvIsApproved = 1 AND fvFilename = ? LIMIT 1', array($myFile));
						if (!$det) {
							
							// pull remote file
							if(count($movie_data['hiphotos']['photo']) > 1){
								$imgLnk = $movie_data['hiphotos']['photo'][0];
							} elseif($movie_data['hiphotos']['photo']){
								$imgLnk = $movie_data['hiphotos']['photo'];
							} elseif(count($movie_data['photos']['photo']) > 0) {
								$imgLnk = $movie_data['photos']['photo'][0];
							} elseif($movie_data['photos']['photo']) {
								$imgLnk = $movie_data['photos']['photo'];
							}
							$new_image = $this->get_data($imgLnk);
							
							$myFileLocation = DIR_FILES_UPLOADED_STANDARD.'/cache/'.$myFile;
							if(!file_exists($myFileLocation)){
								$fh = fopen($myFileLocation, 'w');
								$stringData = $new_image;
								fwrite($fh, $stringData);
								fclose($fh);
							}
														
							if(!file_exists($myFileLocation) || filesize($myFileLocation) > 0){
								$error = \Concrete\Core\File\Importer::E_PHP_FILE_ERROR_DEFAULT;
								$fi = new \Concrete\Core\File\Importer();
								$newFile = $fi->import($myFileLocation,$myFile);
								if(!is_object($newFile)) {
									$this->set('errorMessage', \Concrete\Core\File\Importer::getErrorMessage($error));  
								}
								
								// delete cached file
								unlink($myFileLocation);
							}
								
							//echo $myFileLocation;
//							echo $myFile;
//							print_r($movie_data);
//							die();
						
							$det = $db->GetRow('SELECT * FROM FileVersions WHERE fvIsApproved = 1 AND fvFilename = ? LIMIT 1', array($myFile));
							$f = \File::getByID($det[fID]);
							$fv = $f->getApprovedVersion();
							$path = $fv->getRelativePath();
						} else {
							$f = \File::getByID($det[fID]);
							$fv = $f->getApprovedVersion();
							$path = $fv->getRelativePath();
						}
						
						// update poster art to use local relative path
						$movie_data_arr[$movie['movie_id']]['photos']['photo'] = $path;
					} else {
						$path = 'null';
					}
				}
				
			}
			
			// store queried show dates
			$movieFeed = $expensiveCache->getItem('movieFeed');
			// store in cache if not found
			if ($movieFeed->isMiss()) {
				$movieFeed->lock();
				$movie_feed_new = $updated_listing;
				$movieFeed->set($movie_feed_new,7200);
			}
			$movieFeed = $movieFeed->get();
										
			// convert to JSON
			asort($sel_dates_arr);
			$sel_dates_arr = json_encode($sel_dates_arr);
			asort($soon_dates_arr);
			$soon_dates_arr = json_encode($soon_dates_arr);
			$updated_listing = json_encode($updated_listing);
			$rtsListing = json_encode($rtsListing);
			$movie_data_arr = json_encode($movie_data_arr);
			
			$final_listing = 'var dateOpts = ' . $sel_dates_arr . ';'."\n\n";
			$final_listing .= 'var soonDateOpts = ' . $soon_dates_arr . ';'."\n\n";
			$final_listing .= 'var listingData = ' . $updated_listing . ';'."\n\n";
			$final_listing .= 'var rtsListingData = ' . $rtsListing . ';'."\n\n";
			$final_listing .= 'var movieData = ' . $movie_data_arr . ';';
			
			// save as static file
			touch($listCacheFle);
			$fh = fopen($listCacheFle, 'w');
			fwrite($fh, $final_listing);
			fclose($fh);

		}
		
		// add json variables to footer
		$this->addFooterItem($html->javascript(DIR_REL.'/application/files/listingcache.js'));
		
		// transfer usable variables
		//$this->set('jsAPI', $updated_listing);
	}
	
}