<?php
namespace Application\Block\MovieListingSoon;

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
		
		var $pobj;
		
		protected $btDescription = "Coming soon movie listing block for use with Cinema Cafe and West World Media movie listings.";
		protected $btName = "Coming Soon Movie Listing";
		protected $btInterfaceWidth = "350";
		protected $btInterfaceHeight = "300";
				
		public function view(){ 
			// link any necessary javascript files
			$html = Loader::helper('html');
			$this->addFooterItem($html->javascript(DIR_REL.'/application/blocks/movie_listing_soon/js/script.js'));
		}
		
		
	}
	
?>