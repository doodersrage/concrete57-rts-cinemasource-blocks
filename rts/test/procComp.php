<?php
echo 'Post Vars <br>';
print_r($_POST);
echo '<br>Get Vars <br>';
print_r($_GET);

	// generate RTS package
	$package = '<Response>
<Version>1</Version>
<Code>-1</Code>
<Packet>
<GiftCard>
<GiftNumber>2012700000745808</GiftNumber>
<DebitRemain>1061.16</DebitRemain>
<Registered>1</Registered>
<RegisteredInfo>
<FirstName>JOHN</FirstName>
<LastName>DOE</LastName>
<Address1>4 DOE ROAD</Address1>
<Address2/>
<City>DOEVILLE</City>
<State>DO</State>
<Postal>11111</Postal>
</RegisteredInfo>
<CardCredits>
<TicketCredit>
<Expiration>None</Expiration>
<StartDate>None</StartDate>
<Amount>1</Amount>
<TitleRestriction/>
<TicketRestriction/>
</TicketCredit>
<TicketCredit>
<Expiration>20130405000000</Expiration>
<StartDate>20130405000000</StartDate>
<Amount>1</Amount>
<TitleRestriction>WORLD WAR Z</TitleRestriction>
<TicketRestriction/>
</TicketCredit>
<ItemCredit>
<Expiration>None</Expiration>
<StartDate>20130426000000</StartDate>
<Amount>1</Amount>
<Item>SM POPCORN</Item>
</ItemCredit>
</CardCredits>
</GiftCard>
</Packet>
</Response>';

	// send results to RTS
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
	//curl_setopt($ch, CURLOPT_USERPWD, 'test:test');
	curl_setopt($ch, CURLOPT_USERPWD, 'test:test');
	// send well formed request packet
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $package );
	//curl_setopt($ch, CURLOPT_VERBOSE, true);
	$data = curl_exec($ch);
	curl_close($ch);
	
	// convert rts result
	$data = json_decode(json_encode( simplexml_load_string($data)), 1);
	print_r($data);
	$sess_data['rtsResult'] = $data;
