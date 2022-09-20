<?php

namespace Fgc\NabEvm3D\Lib;

class Transaction
{

	/**
	 * Transaction type specifies the type of transaction being processed.
	 *
	 * @access public
	 *
	 * @var string $txnType Transaction type
	 */
	public $txnType;

	/**
	 * Transaction source specifies the source of transaction being
   * processed via the NAB Transact XML API. The source must always
   * have a value of "23".
	 *
	 * @access public
	 *
	 * @var string $txnSource Transaction source
	 */
	public $txnSource;

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
	 * Unique merchant transaction identifier, typically an invoice number.
   * Note: Must be the same as <purchaseOrderNo> element of the
   * original transaction when performing a refund or advice or
   * subsequent payment based on a parent transaction.
	 *
	 * @access public
	 *
	 * @var string $purchaseOrderNo Purchase Order Number
	 */
	public $purchaseOrderNo;

	/**
	 * Indicates whether the transaction processed has
   * been approved or not.
   * Always "Yes" or "No"
	 *
	 * @access public
	 *
	 * @var string $approved Approved
	 */
	public $approved;

	/**
	 * Response code of the transaction. Either a 2-digit bank response or
   * a 3-digit NAB Transact response.
   * Element <responseText> provides more information in a text format.
   * Refer to Appendix G for a list of the NAB Transact Payment Bank
   * Response Codes.
	 *
	 * @access public
	 *
	 * @var string $responseCode ResponseCode
	 */
	public $responseCode;

	/**
	 * Textual description of the response code received.
	 *
	 * @access public
	 *
	 * @var string $responseText Response Text
	 */
	public $responseText;

	/**
	 * Bank settlement date is when the funds will be settled into the
   * merchant’s account.
   * This will not be returned if NAB did not receive the transaction.
   * (A settlement date may still be returned for declined transactions.)
	 *
	 * @access public
	 *
	 * @var string $settlementDate Bank settlement date
	 */
	public $settlementDate;

	/**
	 * Bank transaction ID will not be returned if the transaction was not
   * been processed or in some cases the transaction request was not
   * received by NAB.
	 *
	 * @access public
	 *
	 * @var string $txnID Transaction ID
	 */
	public $txnID;

	public $authID;

	/**
	 * Init Auth class
	 *
	 * @param array $response
	 *
	 * @return Order An Order instance
	 */
	public function __construct($response)
	{
		$this->txnType = $response["txnType"];
		$this->txnSource = $response["txnSource"];
		$this->amount = $response["amount"];
		$this->amountInCents = $response["amount"];
		$this->amountInDollar = $response["amount"] / 100;
		$this->currency = $response["currency"];
		$this->purchaseOrderNo = $response["purchaseOrderNo"];
		$this->approved = $response["approved"];
		$this->responseCode = $response["responseCode"];
    $this->responseText = $response["responseText"];
    $this->settlementDate = $response["settlementDate"];
		$this->txnID = $response["txnID"];
		$this->authID = $response["authID"];

		return $this;
	}
}
