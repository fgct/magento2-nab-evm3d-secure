<?php

namespace Fgc\NabEvm3D\Lib;

use Exception;
use SimpleXMLElement;

class Auth
{

	const MODE_PRODUCTION = 'production';
	const MODE_TEST = 'sandbox';

	const URL_EVM_3D_LIVE = 'https://transact.nab.com.au/services/order-management/v2/payments/orders';
	const URL_EVM_3D_TEST = 'https://demo.transact.nab.com.au/services/order-management/v2/payments/orders';

	const URL_NAB_PAYMENT_LIVE = 'https://transact.nab.com.au/live/xmlapi/payment';
	const URL_NAB_PAYMENT_TEST = 'https://demo.transact.nab.com.au/xmlapi/payment';

	private $mode;
	private $merchant_id;
	private $merchant_password;
	private $useDollar = true;

	/**
	 * Init Auth class
	 *
	 * @param string $mode production | sandbox
	 * @param string $merchant_id Merchant ID
	 * @param string $merchant_password Merchant Password
	 *
	 * @return Auth An Auth instance
	 */
	public function __construct($mode, $merchant_id, $merchant_password)
	{
		if (!in_array($mode, [self::MODE_PRODUCTION, self::MODE_TEST])) {
			throw new Exception('MODE must one of type: production or sandbox');
		}
		$this->mode = $mode;
		$this->merchant_id = $merchant_id;
		$this->merchant_password = $merchant_password;

		return $this;
	}

	/**
	 * Input amount unit: cent or dollar, default is dollar
	 *
	 * @param bool $bool true | false
	 *
	 * @return void
	 */
	public function useDollar($bool = true) {
		$this->useDollar = $bool;
		return $this;
	}

	/**
	 * Create order method
	 *
	 * @param array $params ['ip', 'amount', 'currency', 'orderType', 'intents']
	 * @return Order|Exception An Order instance or throw Exception
	 */
	public function createOrder($params)
	{
		if (!isset($params['amount'])) {
			throw new Exception('Missing amount param');
		}
		$params['amount'] = floatval($params['amount']);
		if ($this->useDollar) {
			$params['amount'] = $params['amount'] * 100;
		}
		if (!isset($params['currency'])) {
			$params['currency'] = 'AUD';
		}
		if (!isset($params['ip'])) {
			$params['ip'] = $this->get_client_ip();
			/* if (!$params['ip']) {
				throw new Exception('Can not get client IP address');
			} */
		}

		if (!isset($params['orderType'])) {
			$params['orderType'] = 'PAYMENT';
		}
		if (!isset($params['intents'])) {
			$params['intents'] = ['THREED_SECURE'];
		}

		if (!$this->isInit()) {
			throw new Exception('Auth is not init');
		}

		$url = $this->isProductionMode() ? self::URL_EVM_3D_LIVE : self::URL_EVM_3D_TEST;

		// Build request arguments.
		$response = $this->request('POST', $url, json_encode($params), 'array');

		if (!empty($response)) {
			if (isset($response["orderToken"])) {
				$order = new Order($response);
				if ($this->useDollar) {
					$order->amount = $order->amountInDollar;
				}
				return $order;
			}
			if (isset($response["errors"]) && count($response["errors"])) {
				throw new Exception(json_encode($response["errors"][0]));
			}
		}
		throw new Exception(json_encode($response));
	}

	/**
	 * @param array $data
	 * 
	 * @var $data[amount] int, required
	 * @var $data[currency] string, required, eg: AUD
	 * @var $data[purchaseOrderNo] string, required
	 * @var $data[cardNumber] string, required
	 * @var $data[cardHolderName] string, required
	 * @var $data[expiryDate] string, required, eg: 02/24
	 * @var $data[cvv] string, optional - Card CVV
	 * @var $data[CAVV] string, required - Authentication Value.
	 * @var $data[SLI] string, required - E-Commerce Indicator/Security Level Indicator(SLI).
	 * @var $data[xID] string, optional - 3D Secure Transaction ID.
	 * 
	 * @return Transaction | Exception
	 */
	public function processTransaction($data)
	{
		$url = $this->isProductionMode() ? self::URL_NAB_PAYMENT_LIVE : self::URL_NAB_PAYMENT_TEST;

		list($month, $year) = explode('/', $data['expiryDate']);
		$month = sprintf("%02d", $month);
		$year = substr($year, -2);

		if (!isset($data['recurringflag'])) {
			$data['recurringflag'] = 'no';
		}
		if (!isset($data['currency'])) {
			$data['currency'] = 'AUD';
		}
		if (!isset($data['txnType'])) {
			$data['txnType'] = '0'; // Standard Payment
		}
		if (!isset($data['txnSource'])) {
			$data['txnSource'] = '23'; // XML API
		}

		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><NABTransactMessage/>');
		$MessageInfo = $xml->addChild('MessageInfo');
		$MessageInfo->addChild('messageID', substr(md5(time()), 0, 30));
		$MessageInfo->addChild('messageTimestamp', date('YdmHisv000') . '+660');
		$MessageInfo->addChild('timeoutValue', 60); // Minimum "60"
		$MessageInfo->addChild('apiVersion', 'xml-4.2');
		$MerchantInfo = $xml->addChild('MerchantInfo');
		$MerchantInfo->addChild('merchantID', $this->merchant_id);
		$MerchantInfo->addChild('password', $this->merchant_password);
		$xml->addChild('RequestType', 'Payment');
		$Payment = $xml->addChild('Payment');
		$TxnList = $Payment->addChild('TxnList');
		$TxnList->addAttribute('count', 1);
		$Txn = $TxnList->addChild('Txn');
		$Txn->addAttribute('ID', 1);
		$Txn->addChild('txnType', $data['txnType']);
		$Txn->addChild('txnSource', $data['txnSource']);
		if ($this->useDollar) {
			$Txn->addChild('amount', $data['amount'] * 100);
		} else { // cents
			$Txn->addChild('amount', $data['amount']);
		}
		$Txn->addChild('currency', $data['currency']);
		$Txn->addChild('purchaseOrderNo', $data['purchaseOrderNo']);
		if (isset($data['txnID'])) {
			$Txn->addChild('txnID', $data['txnID']);
		}
		if (isset($data['initialAuth'])) {
			$Txn->addChild('initialAuth', $data['initialAuth']);
		}
		if (isset($data['preauthID'])) {
			$Txn->addChild('preauthID', $data['preauthID']);
		}
		if (isset($data['txnChannel'])) {
			$Txn->addChild('txnChannel', $data['txnChannel']);
		}
		if (isset($data['orderId'])) {
			$Txn->addChild('orderId', $data['orderId']);
		}
		if (isset($data['reasonCode'])) {
			$Txn->addChild('reasonCode', $data['reasonCode']);
		}

		$CreditCardInfo = $Txn->addChild('CreditCardInfo');
		if (isset($data['cvv'])) {
			$CreditCardInfo->addChild('cvv', $data['cvv']);
		}
		if (isset($data['xID'])) {
			$CreditCardInfo->addChild('xID', $data['xID']);
		}
		if (isset($data['CAVV'])) {
			$CreditCardInfo->addChild('CAVV', $data['CAVV']);
		}
		if (isset($data['SLI'])) {
			$CreditCardInfo->addChild('SLI', $data['SLI']);
		}
		if (isset($data['threeDSVersion'])) {
			$CreditCardInfo->addChild('threeDSVersion', $data['threeDSVersion']);
		}
		if (isset($data['DirectoryServerTransactionId'])) {
			$CreditCardInfo->addChild('DirectoryServerTransactionId', $data['DirectoryServerTransactionId']);
		}
		$CreditCardInfo->addChild('cardNumber', $data['cardNumber']);
		$CreditCardInfo->addChild('expiryDate', $month . '/' . $year);
		$CreditCardInfo->addChild('cardHolderName', $data['cardHolderName']);
		$CreditCardInfo->addChild('recurringflag', $data['recurringflag']);

		$xml_content = $xml->asXML();
		$response = $this->request('POST', $url, $xml_content, 'xml');

		if (!isset($response->Status->statusCode) || !$response->Status->statusCode || !isset($response->Payment->TxnList->Txn)) {
			throw new Exception(json_encode($response));
		}

		switch ($response->Status->statusCode) {
			case '000': // Message processed correctly (check transaction response for details)
				switch ($response->Payment->TxnList->Txn->responseCode) {
					case '00': // Approved
					case '08': // Approved
						break;
					default:
						throw new Exception($response->Payment->TxnList->Txn->responseCode . ': ' . $response->Payment->TxnList->Txn->responseText);
						break;
				}
				break;
			default:
				throw new Exception($response->Status->statusCode . ': ' . $response->Status->statusDescription);
		}
		$transaction = new Transaction((array) $response->Payment->TxnList->Txn);
		if ($this->useDollar) {
			$transaction->amount = $transaction->amountInDollar;
		}
		return $transaction;
	}

	public function isInit()
	{
		return !empty($this->merchant_id) && !empty($this->merchant_password);
	}

	public function isProductionMode()
	{
		return $this->mode == self::MODE_PRODUCTION;
	}

	/**
	 * request method
	 *
	 * @param string $method Method: POST, PUT, GET etc
	 * @param string $url The URL request
	 * @param array|string $data Data for POST method
	 * @param bool|string $decode false | 'array' | 'json' | 'xml'
	 *
	 * @return mixed
	 */
	function request($method, $url, $data = false, $decode = false)
	{
		$ch = curl_init();

		switch ($method) {
			case "POST":
				curl_setopt($ch, CURLOPT_POST, 1);

				if ($data)
					curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
				break;
			case "PUT":
				curl_setopt($ch, CURLOPT_PUT, 1);
				break;
			default:
				if ($data)
					$url = sprintf("%s?%s", $url, http_build_query($data));
		}

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if ($this->isInit()) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'Authorization: Basic ' . base64_encode($this->merchant_id . ":" . $this->merchant_password),
				'Accept: application/json',
				'Content-Type: application/json',
			]);
		}
		$response = curl_exec($ch);
		curl_close($ch);

		if ($decode == 'array') {
			$response = json_decode($response, true);
		} else if ($decode == 'json') {
			$response = json_decode($response);
		} else if ($decode == 'xml') {
			$response = simplexml_load_string($response);
		}
		return $response;
	}

	private function get_client_ip()
	{
		if (getenv('HTTP_CLIENT_IP')) {
			$ip_address = getenv('HTTP_CLIENT_IP');
		} elseif (getenv('HTTP_X_FORWARDED_FOR')) {
			$ip_address = getenv('HTTP_X_FORWARDED_FOR');
		} elseif (getenv('HTTP_X_FORWARDED')) {
			$ip_address = getenv('HTTP_X_FORWARDED');
		} elseif (getenv('HTTP_FORWARDED_FOR')) {
			$ip_address = getenv('HTTP_FORWARDED_FOR');
		} elseif (getenv('HTTP_FORWARDED')) {
			$ip_address = getenv('HTTP_FORWARDED');
		} elseif (getenv('REMOTE_ADDR')) {
			$ip_address = getenv('REMOTE_ADDR');
		} else {
			$ip_address = '';
		}
		return $ip_address;
	}
}
