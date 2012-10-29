<?php

ob_start();

// project=1898&mode_type=108&amount=55&source=1898&nickname=00bmpbqes00w4k4cswcs&order_id=00cdz29qa01co000sowc&paymentCurrency=RUB

require_once('output.php');

session_start();

$iniDirectory = '../../../../../src/conf/';

$iniDirectoryRealpath = realpath($iniDirectory);

$iniFilename = $iniDirectoryRealpath . '/dengionline-stub.ini';

if ( ! file_exists($iniFilename)) {
	dieWithErrorOutput('File "dengionline-stub.ini" not found in directory "' . $iniDirectoryRealpath . '".');
}

$whatDo = isset($_REQUEST['do']) ? $_REQUEST['do'] : 'dengi';

switch ($whatDo) {

	case 'dengi': {

			if (empty($_REQUEST['project'])) {

				dieWithErrorOutput('Bad request, eh?');
			} else {

				$_SESSION['last_request'] = $_REQUEST;

				generateDengiPaymentId();

				dieWithDefaultOutput();
			}
		} break;

	case 'success': {

			sendSuccessNotification();

			sleep(2);

			dieWithSuccessReturn();
		} break;

	case 'failure': {

			dieWithFailureReturn();
		} break;


	case 'notify': {

			$result = sendSuccessNotification();

			dieWithDefaultOutput('Notification response', htmlspecialchars($result));
		} break;

	case 'verify': {

			$result = verifyUser();

			dieWithDefaultOutput('User verification response', htmlspecialchars($result));
		} break;

	default: {
			
		}
}

function generateDengiPaymentId()
{
	if ( ! isset($_SESSION['last_request']['paymentid'])) {
		$_SESSION['last_request']['paymentid'] = rand(90000000, 99999999);
		$_SESSION['last_request']['orderid'] = $_SESSION['last_request']['order_id'];
		$_SESSION['last_request']['userid'] = $_SESSION['last_request']['nickname'];
	}
}

function getClient()
{
	global $iniFilename;

	$clients = parse_ini_file($iniFilename, true);

	$projectId = $_SESSION['last_request']['project'];

	if ( ! isset($clients[$projectId])) {
		dieWithErrorOutput('Client "' . $projectId . '" is not found. Is "' . $iniFilename . '" properly set up?');
	}

	$client = $clients[$projectId];

	return $client;
}

function dieWithSuccessReturn()
{
	dieWithReturn('success_url');
}

function dieWithFailureReturn()
{
	dieWithReturn('failure_url');
}

function sendSuccessNotification()
{
	$client = getClient();

	$notificationData = $_SESSION['last_request'];

	$notificationData['key'] = md5($notificationData['amount'] . $notificationData['userid'] . $notificationData['paymentid'] . $client['secret']);

	$response = postNotification($notificationData);

	return $response;
}

function verifyUser()
{
	$client = getClient();

	$notificationData = $_SESSION['last_request'];

	$notificationData['key'] = md5('0' . $notificationData['userid'] . '0' . $client['secret']);

	$response = postNotification($notificationData);

	return $response;
}

function postNotification($notificationData)
{
	$client = getClient();

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $client['notification_url']);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($notificationData));
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_USERAGENT, 'cURL/PHP');

	$rawResponse = curl_exec($ch);

	$curlError = curl_error($ch);

	if ( ! empty($curlError)) {
		dieWithErrorOutput('CURL got error: ' . $curlError, $notificationData);
	}

	return $rawResponse;
}

function makeDolSign($queryParameters, $client)
{
	unset($queryParameters['err_msg']);
	unset($queryParameters['DOL_SIGN']);

	ksort($queryParameters);

	$stringToHash = '';

	foreach ($queryParameters as $key => $val) {
		$stringToHash .= $key . '=' . $val;
	}

	$dolSign = md5($stringToHash . $client['secret']);

	return $dolSign;
}

function makeReturnUrlQuery($client, $extra = array())
{
	$queryData = $_SESSION['last_request'] + $extra;

	$queryData['DOL_SIGN'] = makeDolSign($queryData, $client);

	return http_build_query($queryData);
}

function dieWithErrorOutput($message, $extra = null)
{
	die(getErrorOutput($message, $extra));
}

function dieWithDefaultOutput($message = null, $extra = null)
{
	die(getDefaultOuptut($message, $extra));
}

function dieWithReturn($returnUrlName)
{
	$client = getClient();

	$url = http_build_url($client[$returnUrlName], array('query' => makeReturnUrlQuery($client)));

	ob_clean();
	header('Location: ' . $url);
	die();
}