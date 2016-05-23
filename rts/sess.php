<?php
// Start the session
session_start();

// store post data 
switch($_POST['method']){
	case 'set':
		$_SESSION['data'] = $_POST['data'];
		echo '{status:"saved"}';
	break;
	case 'get';
		if(is_array($_SESSION['data'])){
			$_SESSION['data'] = json_encode($_SESSION['data']);
		}
		echo $_SESSION['data'];
	break;
}