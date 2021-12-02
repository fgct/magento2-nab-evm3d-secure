<?php
namespace Fgc\NabEvm3D\Model;


/**
 * Class PaymentAction
 */
class LiabilityShift implements \Magento\Framework\Option\ArrayInterface
{
    const SETTINGS_ISSUER_ONLY = 'issuer_only';
    const SETTINGS_ISSUER_AND_MERCHANT = 'issuer_and_merchant';
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::SETTINGS_ISSUER_AND_MERCHANT,
                'label' => __('Issuer and Merchant Liability Transactions')
            ],
            [
              'value' => self::SETTINGS_ISSUER_ONLY,
              'label' => __('Issuer Liability Transactions Only')
            ],
        ];
    }
}
