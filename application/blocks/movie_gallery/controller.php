<?php
namespace Application\Block\MovieGallery;

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
	
	protected $btDescription = "Movie gallery block for use with Cinema Cafe and West World Media movie listings.";
	protected $btName = "Movie Gallery";
	protected $btInterfaceWidth = "350";
	protected $btInterfaceHeight = "300";
	
	public function on_start(){
		
			// link any necessary javascript files
			$html = Loader::helper('html');
			$this->addHeaderItem($html->css(DIR_REL.'/application/blocks/movie_gallery/js/Smooth-Div-Scroll/css/smoothDivScroll.css'));
			$this->addFooterItem($html->javascript(DIR_REL.'/application/blocks/movie_gallery/js/Smooth-Div-Scroll/js/jquery.kinetic.js'));
			$this->addFooterItem($html->javascript(DIR_REL.'/application/blocks/movie_gallery/js/Smooth-Div-Scroll/js/jquery-ui-1.8.23.custom.min.js'));
			$this->addFooterItem($html->javascript(DIR_REL.'/application/blocks/movie_gallery/js/Smooth-Div-Scroll/js/jquery.mousewheel.min.js'));
			$this->addFooterItem($html->javascript(DIR_REL.'/application/blocks/movie_gallery/js/Smooth-Div-Scroll/js/jquery.smoothdivscroll-1.3-min.js'));
			$this->addFooterItem($html->javascript(DIR_REL.'/application/blocks/movie_gallery/js/script.js'));
		
	}
			
	public function view(){ 
	}
	
	
}
