<?php
# Author: German Cheung

include_once('axl.class.php');
include_once('ris.class.php');

/*
* First get a list of devices from AXL API
*/

// Ask a few things
$server = readline('server to connect to (AXL must be running): ');
$username = readline('username with AXL permissions: ');
echo "password: ";
$password = preg_replace('/\r?\n$/', '', `stty -echo; head -n1`);
echo "\n";

// The query
$DEVICES_SQL = '
	SELECT device.name AS devicename, typemodel.name AS model FROM device
	JOIN typemodel ON device.tkmodel=typemodel.enum
	WHERE typemodel.name LIKE "Cisco TelePresence MX200%"
';

// Get AXL client and run the sql query
$_axl = new AXL($username, $password, $server);
$axl_result = $_axl->doRequest('ExecuteSQLQuery', array('sql' => $DEVICES_SQL));
print_r($axl_result);

/*
* Get IP Address of the devices from the RIS API
*/

$_ris = new RIS($username, $password, $server);
$ris_result = $_ris->get_IP($axl_result->return->row);
print_r($ris_result);

/*
* Now contact each device via web interface and check for errors
*/

// Ask a few things
$username = readline('username with admin permissions on devices: ');
echo "password: ";
$password = preg_replace('/\r?\n$/', '', `stty -echo; head -n1`);
echo "\n";

$issues = array();
foreach($axl_result->return->row as $device) {
    print($device->devicename . "\n");

    $ip = $ris_result[$device->devicename];

    $_ch = curl_init();
    curl_setopt($_ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($_ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($_ch, CURLOPT_TIMEOUT, 10);

    curl_setopt($_ch, CURLOPT_URL, "https://".$ip."/putxml");
    curl_setopt($_ch, CURLOPT_POST, 1);
    curl_setopt($_ch, CURLOPT_POSTFIELDS, "<Command><Peripherals><List><Type>TouchPanel</Type><Connected>True</Connected></List></Peripherals></Command>");

    $_token = base64_encode($username.':'.$password);
    $_header = array(
    	'Content-Type: text/xml',
    	'Authorization: Basic '.$_token,
    	);
    curl_setopt($_ch, CURLOPT_HTTPHEADER, $_header);
    curl_setopt($_ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($_ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($_ch, CURLOPT_HEADER, 0);
    curl_setopt($_ch, CURLOPT_VERBOSE, 0);

    // Todo: parse the below to just find out if there's an issue or not
    $_rep = curl_exec($_ch);
    var_dump($_rep);
    if( ! isset($_ch) )
        curl_close($_ch);
}

// Create ticket for issues
// Not sure how far we can go here, obviously we won't create anything
// Todo: add some pseudo code?  Or go with the SNow code we have?

?>



