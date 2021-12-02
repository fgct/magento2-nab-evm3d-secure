<?php
namespace Fgc\NabEvm3D\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Fgc\NabEvm3D\Model\NabEvm3D;

/**
 * Payment information management service.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PaymentInformationManagement extends \Magento\Checkout\Model\PaymentInformationManagement
{

    /**
     * @inheritdoc
     */
    public function savePaymentInformationAndPlaceOrder(
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        try {
            $orderId = parent::savePaymentInformationAndPlaceOrder($cartId, $paymentMethod, $billingAddress);
            return $orderId;
        } catch (\Magento\Framework\Exception\CouldNotSaveException $e) {
            if ($paymentMethod->getMethod() == NabEvm3D::CODE) {
                $origin_exception = $e->getPrevious();
                throw new CouldNotSaveException(
                    __($origin_exception->getMessage()),
                    $origin_exception
                );
            } else {
                throw $e;
            }
        }
    }

}
