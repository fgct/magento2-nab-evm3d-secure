<?php

namespace Fgc\NabEvm3D\Model;

use Magento\Framework\DataObject;
use Magento\Quote\Api\Data\PaymentInterface;
use Fgc\NabEvm3D\Helper\Data;

class NabEvm3D extends \Magento\Payment\Model\Method\Cc
{
    const CODE = 'fgc_nabevm3d';

    protected $_code = self::CODE;

    protected $_canAuthorize = true;
    protected $_canCapture = true;

    /**
     * @var \Fgc\NabEvm3D\Helper\Data
     */
    protected $helper;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        Data $helper
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            $resource,
            $resourceCollection,
            $data
        );
        $this->helper = $helper;
    }

    public function validate()
    {
        /*
         * calling parent validate function
         */
        parent::validate();
        $info = $this->getInfoInstance();
        
        $liabilityShiftIndicator = $info->getAdditionalInformation('liabilityShiftIndicator');
        $transStatus = $info->getAdditionalInformation('transStatus');
        $authenticationValue = $info->getAdditionalInformation('authenticationValue');
        $eci = $info->getAdditionalInformation('eci');

        $errorMsg = false;

        switch ($transStatus) {
            case 'Y':
                if ($liabilityShiftIndicator == 'N' && $this->helper->isIssuerOnly()) {
                    $errorMsg = __( 'Decline transactions with merchant liability response');
                    break;
                }
                if (empty($authenticationValue) || empty($eci)) {
                    $errorMsg = __( 'Authentication Value / SLI Not Presence');
                    break;
                }
                break;
            case 'A':
                $errorMsg = __('Attempted Authentication');
                break;
            case 'N':
                $errorMsg = __('Not Authenticated');
                break;
            case 'U':
                $errorMsg = __('Unable to authenticate');
                break;
            case 'R':
                $errorMsg = __('Rejected');
                break;
            default:
                break;
        }
        if ($errorMsg) {
            throw new \Magento\Framework\Exception\LocalizedException($errorMsg);
        }
        return $this;
    }

    /**
     * Assign data to info model instance
     *
     * @param \Magento\Framework\DataObject|mixed $data
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);
        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_object($additionalData)) {
            $additionalData = new DataObject($additionalData ?: []);
        }

        if (!empty($additionalData->getData())) {
            $payment = $this->getInfoInstance();
            $payment->setAdditionalInformation('liabilityShiftIndicator', $additionalData->getData('liabilityShiftIndicator'));
            $payment->setAdditionalInformation('authenticationValue', $additionalData->getData('authenticationValue'));
            $payment->setAdditionalInformation('eci', $additionalData->getData('eci'));
            $payment->setAdditionalInformation('transStatus', $additionalData->getData('transStatus'));
            $payment->setAdditionalInformation('transStatusReason', $additionalData->getData('transStatusReason'));
        }
        return $this;
    }

    /**
     * Capture Payment.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {
            //check if payment has been authorized
            if(is_null($payment->getParentTransactionId()) && $this->canAuthorize()) {
                // $this->authorize($payment, $amount);
            }

            $order = $payment->getOrder();

            //build array of payment data for API request.
            $request = [
                'amount' => $amount,
                'purchaseOrderNo' => $order->getIncrementId(),
                'cardNumber' => $payment->getCcNumber(),
                'cardHolderName' => $order->getCustomerFirstname().' '.$order->getCustomerLastname(),
                'expiryDate' => $payment->getCcExpMonth().'/'.$payment->getCcExpYear(),
                'CAVV' => $payment->getAdditionalInformation('authenticationValue'), // authenticationValue
                'SLI' => $payment->getAdditionalInformation('eci'), // eci - E-Commerce Indicator/Security Level Indicator(SLI)
            ];

            // make API request to credit card processor.
            // $response = $this->makeCaptureRequest($request);
            $response = $this->helper->processTransaction($request);

            $payment->setTransactionId($response['txnID']);
            $payment->setParentTransactionId($response['txnID']);

            //transaction is done.
            $payment->setIsTransactionClosed(1);

        } catch (\Exception $e) {
            $this->debug($payment->getData());
            // throw new \Magento\Framework\Exception\LocalizedException($e->getMessage());
            throw $e;
        }

        return $this;
    }

    /**
     * Authorize a payment.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {

            ///build array of payment data for API request.
            $request = [
                'cc_type' => $payment->getCcType(),
                'cc_exp_month' => $payment->getCcExpMonth(),
                'cc_exp_year' => $payment->getCcExpYear(),
                'cc_number' => $payment->getCcNumberEnc(),
                'amount' => $amount
            ];

            //check if payment has been authorized
            $response = $this->makeAuthRequest($request);

        } catch (\Exception $e) {
            $this->debug($payment->getData(), $e->getMessage());
        }

        if(isset($response['transactionID'])) {
            // Successful auth request.
            // Set the transaction id on the payment so the capture request knows auth has happened.
            $payment->setTransactionId($response['transactionID']);
            $payment->setParentTransactionId($response['transactionID']);
        }

        //processing is not done yet.
        $payment->setIsTransactionClosed(0);

        return $this;
    }

    /**
     * Set the payment action to authorize_and_capture
     *
     * @return string
     */
    public function getConfigPaymentAction()
    {
        return self::ACTION_AUTHORIZE_CAPTURE;
    }

    /**
     * Test method to handle an API call for authorization request.
     *
     * @param $request
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function makeAuthRequest($request)
    {
        $response = ['transactionId' => 123]; //todo implement API call for auth request.

        if(!$response) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Failed auth request.'));
        }

        return $response;
    }

    /**
     * Test method to handle an API call for capture request.
     *
     * @param $request
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function makeCaptureRequest($request)
    {
        try {
            $response = $this->helper->processTransaction($request);
            return $response;
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException($e->getMessage());
        }
    }
}