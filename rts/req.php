<?php
// this document just processes requests to RTS and does absolytely nothing else
if($_POST['req']){
	
	$ch = curl_init();
	$timeout = 10;
	curl_setopt($ch, CURLOPT_SSLVERSION, 1);
	curl_setopt($ch, CURLOPT_URL, 'https://72352.formovietickets.com/Data.ASP');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_PORT, 2235);
	curl_setopt($ch, CURLOPT_POST, true );
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	// authenticate user
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	//curl_setopt($ch, CURLOPT_USERPWD, 'test:test');
	curl_setopt($ch, CURLOPT_USERPWD, 'OI:z8BaDusT');
	// send well formed request packet
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST['req'] );
	//curl_setopt($ch, CURLOPT_VERBOSE, true);
	$data = curl_exec($ch);
	curl_close($ch);
	
	echo json_encode((array) simplexml_load_string($data));
	
}
