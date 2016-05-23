<?php
// generate XML request packet
$xml = new SimpleXMLElement('<Request/>');
$xml->addChild('Version', 1);
$xml->addChild('Command', 'ShowTimeXml');
$xml->addChild('ShowAvalTickets', 1);
$xml->addChild('ShowSales', 1);
$xml->addChild('ShowSaleLinks', 1);
$packet = $xml->asXML();

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

echo $data;