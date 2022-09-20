<?php

namespace Fgc\NabEvm3D\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Helper\Context;
use Fgc\NabEvm3D\Lib\Auth;
use Fgc\NabEvm3D\Model\LiabilityShift;

class Data extends AbstractHelper
{
  const XML_PATH_PAYMENT_ENABLE = 'payment/fgc_nabevm3d/active';
  const XML_PATH_PAYMENT_SANBOX_MODE = 'payment/fgc_nabevm3d/sandbox_mode';
  const XML_PATH_PAYMENT_MERCHANT_ID = 'payment/fgc_nabevm3d/merchant_id';
  const XML_PATH_PAYMENT_MERCHANT_PASSWORD = 'payment/fgc_nabevm3d/merchant_password';
  const XML_PATH_PAYMENT_LIABILITY_SHIFT = 'payment/fgc_nabevm3d/liability_shift';

  public function __construct(Context $context)
    {
        parent::__construct($context);
    }

  public function isEnable()
  {
    $status = $this->scopeConfig->getValue(self::XML_PATH_PAYMENT_ENABLE);

    return (bool)$status;
  }

  public function isSanboxMode()
  {
    $status = $this->scopeConfig->getValue(self::XML_PATH_PAYMENT_SANBOX_MODE);

    return (bool)$status;
  }

  public function getMerchantId()
  {
    $value = $this->scopeConfig->getValue(self::XML_PATH_PAYMENT_MERCHANT_ID);

    return $value;
  }

  public function getMerchantPassword()
  {
    $value = $this->scopeConfig->getValue(self::XML_PATH_PAYMENT_MERCHANT_PASSWORD);

    return $value;
  }

  public function isIssuerOnly()
  {
    $value = $this->scopeConfig->getValue(self::XML_PATH_PAYMENT_LIABILITY_SHIFT);

    return $value == LiabilityShift::SETTINGS_ISSUER_ONLY;
  }

  public function initAuth()
  {
    $mode = $this->isSanboxMode() ? Auth::MODE_TEST : Auth::MODE_PRODUCTION;
    $auth = new Auth($mode, $this->getMerchantId(), $this->getMerchantPassword());
    return $auth;
  }

  public function createOrder($data)
  {
    $auth = $this->initAuth();
    $order = (array) $auth->createOrder($data);

    return $order;
  }

  public function processTransaction($data)
  {
    $auth = $this->initAuth();
    $result = (array) $auth->processTransaction($data);

    return $result;
  }
}
