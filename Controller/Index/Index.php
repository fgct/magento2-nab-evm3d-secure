<?php
namespace Fgc\NabEvm3D\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Fgc\NabEvm3D\Helper\Data;
 
class Index extends Action implements HttpPostActionInterface
{
 
    private $resultJsonFactory;
 
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CheckoutSession $checkoutSession,
        Data $helper
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
    }
 
    public function execute()
    {
        $quote = $this->checkoutSession->getQuote();
        $total = $quote->getBaseGrandTotal();
        $currencyCode = $quote->getQuoteCurrencyCode();
        if (!$this->getRequest()->getPostValue()) {
            // return;
        }
        $currency = $this->getRequest()->getPost('currency', $currencyCode);
        $amount = floatval($this->getRequest()->getPost('amount', $total));

        $result = [
            'success' => false,
            'message' => '',
        ];

        try {
            $order = $this->helper->createOrder([
                'amount' => $amount,
                'currency' => $currency,
            ]);
            $result['data'] = [
                'clientId' => $order['providerClientId'],
                'orderToken' => $order['orderToken'],
                'simpleToken' => $order['simpleToken'],
                'sessionId' => $order['sessionId'],
            ];
            $result['success'] = true;
        } catch (\Exception $e) {
            $result['message'] = $e->getMessage();
        }
        

        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($result);
    }
}
