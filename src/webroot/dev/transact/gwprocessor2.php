<?php

ob_start();

// project=1898&mode_type=108&amount=55&source=1898&nickname=00bmpbqes00w4k4cswcs&order_id=00cdz29qa01co000sowc&paymentCurrency=RUB

require_once('output.php');

session_id('zzzz');

session_start();

//$iniDirectory = '../../../../src/conf/';
$iniDirectory = '../../../../../src/conf/';

$iniDirectoryRealpath = realpath($iniDirectory);

$iniFilename = $iniDirectoryRealpath . '/supra.ini';

if ( ! file_exists($iniFilename)) {
	dieWithErrorOutput('File "supra.ini" not found in directory "' . $iniDirectoryRealpath . '".');
}

if ( ! empty($_REQUEST['a'])) {

	handleTransactRequest();
} else {

	$whatDo = isset($_REQUEST['do']) ? $_REQUEST['do'] : 'transact';

	switch ($whatDo) {

		case 'transact': {

				dieWithDefaultOutput('Oh, hai!');
			} break;


		case 'success': {
			
				$_SESSION['last_request']['Status'] = 'success';
				session_write_close();

				sendNotification();

				sleep(2);

				dieWithReturn();
			} break;

		case 'failure': {
			
				$_SESSION['last_request']['Status'] = 'failed';
				session_write_close();
				
				sendNotification();

				sleep(2);
				
				dieWithReturn();
			} break;


		case 'success-notify': {

				$_SESSION['last_request']['Status'] = 'success';
				session_write_close();

				$result = sendNotification();

				dieWithDefaultOutput('Notification response', htmlspecialchars($result));
			} break;
		
		case 'failed-notify': {

				$_SESSION['last_request']['Status'] = 'failed';
				session_write_close();

				$result = sendNotification();

				dieWithDefaultOutput('Notification response', htmlspecialchars($result));
			} break;		

		default: {
				
			}
	}
}

/**
 * 
 */
function handleTransactRequest()
{
	$a = $_REQUEST['a'];

	switch ($a) {

		case 'init': {

				$_SESSION['last_request'] = $_REQUEST;

				$merchant = getMerchant();

				if (empty($merchant)) {

					$response = array('ERROR' => 'Bad merchant');
				} else {

					generateTransactTransactionId();

					$response = array(
						'OK' => $_SESSION['last_request']['transact_transaction_id']
					);
				}

				dieWithTransactResponse($response);
			} break;

		case 'charge': {

				$merchant = getMerchant();

				$response = array(
					'Redirect' => getStubUrl(),
				);

				dieWithTransactResponse($response);
			} break;

		case 'status_request': 
		case 'transaction_status': {

				$response = array(
					'Status' => $_SESSION['last_request']['Status']
				);

				dieWithTransactResponse($response);
			} break;

		default: {

				dieWithErrorOutput('Transact request not recognized: ' . $a);
			}
	}
}

/**
 * 
 */
function generateTransactTransactionId()
{
	if ( ! isset($_SESSION['last_request']['transact_transaction_id'])) {
		$_SESSION['last_request']['transact_transaction_id'] = sha1($_SESSION['last_request']['merchant_transaction_id']);
	}
}

/**
 * @global string $iniFilename
 * @return array | null 
 */
function getMerchant()
{
	global $iniFilename;

	$ini = parse_ini_file($iniFilename, true);

	$merchantId = $_SESSION['last_request']['guid'];

	$merchantSectionName = 'transact_stub_' . $merchantId;

	if (isset($ini[$merchantSectionName])) {
		$merchant = $ini[$merchantSectionName];
	} else {
		$merchant = null;
	}

	return $merchant;
}

/**
 * @return array
 */
function sendNotification()
{
	$notificationData = array(
		'ID' => $_SESSION['last_request']['transact_transaction_id'],
		'MerchantID' => $_SESSION['last_request']['merchant_transaction_id'],
		'Status' => $_SESSION['last_request']['Status'],
	);

	$response = postNotification($notificationData);

	return $response;
}

/**
 * 
 * @param array $notificationData
 * @return array
 */
function postNotification($notificationData)
{
	$merchant = getMerchant();

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $merchant['notification_url']);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $notificationData);
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

/**
 * @param array $merchant
 * @param array $extra
 * @return string
 */
function makeReturnToMerchantUrlQuery($merchant, $extra = array())
{
	$queryData = array('merchant_transaction_id' => $_SESSION['last_request']['merchant_transaction_id'], 'Status' => $_SESSION['last_request']['Status']) + $extra;

	return http_build_query($queryData);
}

/**
 * @param string $message
 * @param array $extra
 */
function dieWithErrorOutput($message, $extra = null)
{
	die(getErrorOutput($message, $extra));
}

/**
 * @param string $message
 * @param array $extra
 */
function dieWithDefaultOutput($message = null, $extra = null)
{
	die(getDefaultOuptut($message, $extra));
}

/**
 */
function dieWithReturn($queryExtra = array())
{
	$client = getMerchant();

	$url = http_build_url($client['return_url'], array('query' => makeReturnToMerchantUrlQuery($client, $queryExtra)));

	ob_clean();
	header('Location: ' . $url);
	die();
}

/**
 * @param array $responseData
 */
function dieWithTransactResponse($responseData)
{
	$parts = array();

	foreach ($responseData as $name => $value) {
		$parts[] = $name . ':' . $value;
	}

	ob_clean();
	echo(join('~', $parts));
	die();
}

/**
 * 
 * @param string $whatDo
 * @param array $extra
 * @return string
 */
function getStubUrl($whatDo = 'transact', $extra = array())
{
	return http_build_url($_SERVER['REQUEST_URI'], array('query' => http_build_query($extra + array('do' => $whatDo))));
}