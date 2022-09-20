<?php

namespace Fgc\NabEvm3D\Lib;

class Order
{

	/**
	 * Order ID.
	 * A unique id generated for EMV 3D Secure Order.
	 * Format type: String, LEN=36
	 *
	 * @access public
	 *
	 * @var string $orderId Order ID
	 */
	public $orderId;

	/**
	 * Order Token
	 * A bearer token used to authenticate calls from browser. 
	 *
	 * @access public
	 *
	 * @var string $orderToken Order Token
	 */
	public $orderToken;

	/**
	 * A shortened authentication token.
	 * The token is used to authenticate calls from browser. 
	 *
	 * @access public
	 *
	 * @var string $simpleToken Simple Token
	 */
	public $simpleToken;

	/**
	 * Transaction amount. 
	 * Returned unchanged from the request.
	 *
	 * @access public
	 *
	 * @var string $amount Amount
	 */
	public $amount;

	/**
	 * Transaction amount in cents. 
	 * Returned unchanged from the request.
	 *
	 * @access public
	 *
	 * @var string $amountInCents Amount
	 */
	public $amountInCents;

	/**
	 * Transaction amount in dollar. 
	 * Returned unchanged from the request.
	 *
	 * @access public
	 *
	 * @var string $amountInDollar Amount
	 */
	public $amountInDollar;

	/**
	 * Transaction currency.
	 * Returned unchanged from the request.
	 *
	 * @access public
	 *
	 * @var string $currency Currency
	 */
	public $currency;

	/**
	 * Order Type.
	 * Returned unchanged from the request.
	 *
	 * @access public
	 *
	 * @var string $orderType Order Type
	 */
	public $orderType;

	/**
	 * Order Status.
	 * Returns ‘NEW’ after EMV 3D Secure order creation
	 *
	 * @access public
	 *
	 * @var string $status Status
	 */
	public $status;

	/**
	 * Merchant ID.
	 * Returned unchanged from the request.
	 *
	 * @access public
	 *
	 * @var string $merchantId Merchant ID.
	 */
	public $merchantId;

	/**
	 * Merchant Order Reference.
	 * A merchant assigned reference to identify the order
	 * Returned unchanged from the request.
	 *
	 * @access public
	 *
	 * @var string $merchantOrderReference Merchant Order Reference
	 */
	public $merchantOrderReference;

	/**
	 * Provider Client Id.
	 * The client Id assigned to the merchant by the EMV 3D Secure provider.
	 *
	 * @access public
	 *
	 * @var string $providerClientId Provider Client Id
	 */
	public $providerClientId;

	/**
	 * Session Id.
	 * A unique session id.
	 *
	 * @access public
	 *
	 * @var string $sessionId Session Id
	 */
	public $sessionId;

	/**
	 * Purpose of creating the order.
	 * Returned unchanged from the request.
	 *
	 * @access public
	 *
	 * @var string $intents THREED_SECURE
	 */
	public $intents;

	/**
	 * Init Auth class
	 *
	 * @param array $response
	 *
	 * @return Order An Order instance
	 */
	public function __construct($response)
	{
		$this->orderId = $response["orderId"];
		$this->orderToken = $response["orderToken"];
		$this->simpleToken = $response["simpleToken"];
		$this->amount = $response["amount"];
		$this->amountInCents = $response["amount"];
		$this->amountInDollar = $response["amount"] / 100;
		$this->currency = $response["currency"];
		$this->orderType = $response["orderType"];
		$this->status = $response["status"];
		$this->merchantId = $response["merchantId"];
		if (isset($response["threedSecure"])) {
			$this->providerClientId = $response["threedSecure"]["providerClientId"];
			$this->sessionId = $response["threedSecure"]["sessionId"];
		}
		$this->intents = $response["intents"];
		if (isset($response['merchantOrderReference'])) {
			$this->merchantOrderReference = $response["merchantOrderReference"];
		}

		return $this;
	}
}
