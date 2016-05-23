<?php
// Start the session
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// convert session store value if needed
if(is_array($_SESSION['data'])){
	$_SESSION['data'] = json_encode($_SESSION['data']);
}

// retrieve session values and append payment results to it
if(is_string($_SESSION['data'])){
	// convert saved data to PHP array
	$sess_data = json_decode($_SESSION['data'],1);
	// add payment results
	$sess_data['paymentRes'] = array(
		'PaymentID' => $_POST['PaymentID'],
		'ReturnCode' => $_POST['ReturnCode'],
		'ReturnMessage' => $_POST['ReturnMessage'],
	);	
	
	// order vars
	$performanceID = $sess_data['performanceId'];
	$ticketSum = 0;
	
	// generate RTS package
	$package = '<Request>
					<Version>1</Version>
					<Command>Buy</Command>
					<Data>
						<Packet>
							<PurchaseTitles>
								<PurchaseTitle>
									<PerformanceID>'.$performanceID.'</PerformanceID>
									<Tickets>';
	foreach($sess_data['selTicketsQty'] as $ticket){
		// add tickets to packet
		$package .= '<Ticket>
						<Amount>'.$ticket['qty'].'</Amount>
						<TypeCode>'.$ticket['code'].'</TypeCode>
					</Ticket>';
					
		$ticketSum += $ticket['qty'];
	}
	// continue purchase packet gen
	$package .= '</Tickets>
								</PurchaseTitle>
							</PurchaseTitles>
							<Fees>
								<TicketFee>'.number_format(($ticketSum * 1.35),2).'</TicketFee>
								<TransactionFee>0</TransactionFee>
								<Adjust>0</Adjust>
							</Fees>
							<Payments>
								<Payment>
									<Type>CreditCard</Type>
									<TransactionId>'.$sess_data['hostCheckout']['Packet']['CreatePayment']['TransactionId'].'</TransactionId>
									<ProcessCompletePostData>PaymentID='.$sess_data['paymentRes']['PaymentID'].'&ReturnCode='.$sess_data['paymentRes']['ReturnCode'].'&ReturnMessage='.urlencode($sess_data['paymentRes']['ReturnMessage']).'</ProcessComplet
									ePostData>
									<ChargeAmount>'.number_format($sess_data['orderSum'],2).'</ChargeAmount>
								</Payment>
							</Payments>
							<CustomerInfo>
								<EmailAddress>'.$sess_data['email'].'</EmailAddress>
							</CustomerInfo>
						</Packet>
					</Data>
				</Request>';

	// send results to RTS
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
	curl_setopt($ch, CURLOPT_POSTFIELDS, $package );
	//curl_setopt($ch, CURLOPT_VERBOSE, true);
	$data = curl_exec($ch);
	curl_close($ch);
	
	// convert rts result
	$data = json_decode(json_encode( simplexml_load_string($data)), 1);
	$sess_data['rtsResult'] = $data;
	
	// generate and send HTML email if order succesful
	//print_r($sess_data['rtsResult']);die($sess_data['rtsResult']['Packet']['Response']['Code']);
	if($sess_data['rtsResult']['Packet']['Response']['ResponseText'] == 'OK'){
		
		$to = $sess_data['email'];
	
		$subject = 'Beach Movie Bistro ' . $sess_data['movieData']['title'] . '  ' . $sess_data['selTime'] . ' Ticket Purchase';
		
		$headers = "From: tickets@beachmoviebistro.com\r\n";
		$headers .= "Reply-To: tickets@beachmoviebistro.com\r\n";
		$headers .= "CC: tickets@beachmoviebistro.com\r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
		
		$message = '<html><body>';
		$message .= '<img src="https://bmb.studiocenter.com/application/files/7114/6169/3563/beach-movie-bistro-logo.png" alt="#" class="ccm-image-block img-responsive bID-169">';
		$message .= '<p style="text-align: center;">Please print or have your email available on mobile device upon arrival.</p>';
		$message .= '<p style="text-align: center;"><span style="color:#ca0012"><strong>Make sure to Arrive 30 minutes prior to the film to enjoy our menu.</span></strong><br> Last call is when the previews begin. Additional items can be ordered from the lobby bar. You must be 21 to order/consume alcohol.</p>';
		$message .= '<h1>'.$sess_data['movieData']['title'].'</h1>';
		$message .= '<p><img src="https://bmb.studiocenter.com/'.$sess_data['movieData']['photos']['photo'].'"></p>';
		$message .= '<p>Date/Time: '.$sess_data['selTime'].'<br>
					Total: $' . number_format($sess_data['orderSum'],2) . '</p>';
		$message .= '<p>Present this barcode on arrival:</p>';
		// add barcodes
		foreach($sess_data['rtsResult']['Packet']['Response']['Pickups']['Pickup']['BarCodes']['BarCode'] as $barcode){
			if($barcode['CodeType'] == 'UPC'){
				$message .= '<p><img alt="ticket barcode" src="https://bmb.studiocenter.com/rts/barcode.php?text='.$barcode['BarCodeData'].'&size=40" /></p>';
				$message .= '<p>Confirmation Number: '.$barcode['BarCodeData'].'</p>';
			}
		}
		$message .= '</body></html>';
		
		// send email
		mail($to, $subject, $message, $headers);
		
	};
	
	// store mutated array as json string for easy retrieval
	$_SESSION['data'] = json_encode($sess_data);

	// redirect user to visual result page
	header('Location: /?paymentRes=1');
} else {
	echo 'no session data found';
}