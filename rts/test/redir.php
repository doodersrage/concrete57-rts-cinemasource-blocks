<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

$hcp = '<Request>';
$hcp .= '<Version>1</Version>';
$hcp .= '<Command>CreatePayment</Command>';
$hcp .= '<Data>';
$hcp .= '<Packet>';
$hcp .= '<ChargeAmount>1.00</ChargeAmount>';
$hcp .= '<ProcessCompleteUrl>'.'http://bmb.studiocenter.com/rts/test/procComp.php'.'</ProcessCompleteUrl>';
$hcp .= '<ReturnUrl>'.'http://bmb.studiocenter.com/showtimes'.'</ReturnUrl>';
$hcp .= '</Packet>';
$hcp .= '</Data>';
$hcp .= '</Request>';

// generate request xml
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
curl_setopt($ch, CURLOPT_POSTFIELDS, $hcp );
//curl_setopt($ch, CURLOPT_VERBOSE, true);
$data = curl_exec($ch);
curl_close($ch);

// convert return values
$dataArr = json_decode(json_encode( simplexml_load_string($data)), 1);
print_r($dataArr);
// provides redirect facilities for RTS api
if($dataArr['Packet']['CreatePayment']['RedirectUrl']){
	?>
    <form action='<?php echo urldecode($dataArr['Packet']['CreatePayment']['RedirectUrl']); ?>' method='post' name='frm'>
	<?php
    foreach ($dataArr['Packet']['CreatePayment'] as $a => $b) {
	$a = ($a =='PostData' ? 'paymentID' : $a);
    echo "<input type='hidden' name='".htmlentities($a)."' value='".htmlentities($b)."'>";
    }
    ?>
    </form>
    <script language="JavaScript">
    document.frm.submit();
    </script>
    <?php
}