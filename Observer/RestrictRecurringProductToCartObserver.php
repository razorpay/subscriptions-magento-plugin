<?php

namespace Razorpay\Subscription\Observer;

use Magento\Checkout\Model\Cart;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Catalog\Model\Product;
use Psr\Log\LoggerInterface;
use Razorpay\Subscription\Helper\Subscription;
use Razorpay\Subscription\Model\SubscriptionConfig;

class RestrictRecurringProductToCartObserver implements ObserverInterface
{
    /**
     * @var ManagerInterface
     */
    private $_messageManager;

    /**
     * @var Cart
     */
    private $_cart;

    /**
     * @var LoggerInterface
     */
    private $_logger;

    /**
     * @var Product
     */
    private $_product;

    private $paymentType;
    /**
     * @var SubscriptionConfig
     */
    private $subscriptionConfig;
    /**
     * @var Subscription
     */
    private $_helper;

    public function __construct(
        ManagerInterface $messageManager,
        Cart  $cart,
        Product $product,
        LoggerInterface $logger,
        SubscriptionConfig $subscriptionConfig,
        Subscription $helper
    )
    {
        $this->_messageManager = $messageManager;
        $this->_cart = $cart;
        $this->_logger = $logger;
        $this->_product = $product;
        $this->subscriptionConfig = $subscriptionConfig;
        $this->_helper = $helper;
    }

    public function execute(Observer $observer)
    {
        $cartItemsCount = $this->_cart->getQuote()->getItemsCount();
        $allCartItems = $this->_cart->getQuote()->getAllItems();
        $productId = $observer->getRequest()->getParam('product');
        $product = $this->_product->load($productId);

        if($product->getRazorpaySubscriptionEnabled() /*&& $this->subscriptionConfig->isSubscriptionActive()*/) {
            if ($cartItemsCount >= 1) {
                $this->paymentType = $observer->getRequest()->getParam('paymentOption');
                if ($this->paymentType == "subscription") {
                    $message = $this->verifyProductInCart($allCartItems, $cartItemsCount, "oneTime");
                } else if ($this->paymentType == "oneTime") {
                    $message = $this->verifyProductInCart($allCartItems, $cartItemsCount, "subscription");
                }

                if (!empty($message)) {
                    $observer->getRequest()->setParam('product', false);
                    return $this->_messageManager->addErrorMessage(__($message));
                }
            }
        }
    }

    /**
     * @param $allCartItems
     * @param $cartItemsCount
     * @param $validateTo
     * @return string
     */
    private function verifyProductInCart($allCartItems, $cartItemsCount, $validateTo): string
    {
        $message = "";
        if($this->_helper->validateIsASubscriptionProduct($allCartItems, $validateTo)){
            $message = "You cannot have regular products and subscriptions product in your shopping cart";
        } else if($cartItemsCount >= 1 && $this->paymentType != "oneTime") {
            $message = "You can only have 1 recurring subscription product in your shopping cart at a time.";
        }
        return $message;
    }

}
