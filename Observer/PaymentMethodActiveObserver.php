<?php

namespace Razorpay\Subscription\Observer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Razorpay\Magento\Model\PaymentMethod;
use Razorpay\Subscription\Helper\Subscription;
use Razorpay\Subscription\Model\SubscriptionConfig;
use Psr\Log\LoggerInterface;
use Razorpay\Subscription\Model\SubscriptionPaymentMethod;

class PaymentMethodActiveObserver implements ObserverInterface
{
    /**
     * @var Psr\Log\LoggerInterface
     */
    private $_logger;
    /**
     * @var SubscriptionConfig
     */
    private $_subscriptionConfig;
    /**
     * @var ProductRepositoryInterface
     */
    private $_productRepository;
    /**
     * @var Subscription
     */
    private $_helper;
    /**
     * @var Cart
     */
    private $_cart;
    /**
     * @var ManagerInterface
     */
    private $_messageManager;

    public function __construct(
        LoggerInterface $logger,
        SubscriptionConfig $subscriptionConfig,
        ProductRepositoryInterface $productRepository,
        Subscription $helper,
        Cart  $cart,
        ManagerInterface $messageManager
    )
    {
        $this->_logger = $logger;
        $this->_subscriptionConfig = $subscriptionConfig;
        $this->_productRepository = $productRepository;
        $this->_helper = $helper;
        $this->_cart = $cart;
        $this->_messageManager = $messageManager;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $result = $observer->getEvent()->getResult();
        $code = $observer->getEvent()->getMethodInstance()->getCode();
        $quote = $this->_cart->getQuote();

        if ( $this->_cart->getQuote()->getItemsCount() > 1) {
            $this->disablePaymentMethod($result, SubscriptionPaymentMethod::METHOD_CODE, $code);
        } else {
            /* @var \Magento\Quote\Model\Quote $quote */
            if($quote->getIsActive()) {
                foreach ($quote->getItems() as $item) {
                    /* @var \Magento\Quote\Model\Quote\Item $item */
                    $productId = $item->getProduct()->getId();
                }
                $product = $this->_productRepository->getById($productId);

                $isSubscriptionProduct = $this->_helper->validateIsASubscriptionProduct($this->_cart->getQuote()->getAllItems(), "subscription");

                if ($this->_subscriptionConfig->isSubscriptionActive() && $product->getRazorpaySubscriptionEnabled() && $isSubscriptionProduct) {
                    $this->disablePaymentMethod($result, PaymentMethod::METHOD_CODE, $code);
                } else {
                    $this->disablePaymentMethod($result, SubscriptionPaymentMethod::METHOD_CODE, $code);
                }
            } else {
                return $this->_messageManager->addErrorMessage(__(" No items in the cart"));
            }
        }
    }

    /**
     * Disabling the payment method
     * @param $result
     * @param $paymentMethod
     * @param $methodCode
     */
    public function disablePaymentMethod($result, $paymentMethod, $methodCode)
    {
        $this->_logger->info("-------------disabling  $paymentMethod ---------------------");
        if( $methodCode == $paymentMethod) {
            $result->setData('is_available', false);
        }
    }
}
