<?php

namespace Razorpay\Subscription\Observer;

use Magento\Checkout\Model\Cart;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Catalog\Model\Product;
use Psr\Log\LoggerInterface;

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

    public function __construct(
        ManagerInterface $messageManager,
        Cart  $cart,
        Product $product,
        LoggerInterface $logger
    )
    {
        $this->_messageManager = $messageManager;
        $this->_cart = $cart;
        $this->_logger = $logger;
        $this->_product = $product;
    }

    public function execute(Observer $observer)
    {
        $cartItemsCount = $this->_cart->getQuote()->getItemsCount();
        $cartItemsAll = $this->_cart->getQuote()->getAllItems();
        $productId = $observer->getRequest()->getParam('product');
        $product = $this->_product->load($productId);

        if($product->getRazorpaySubscriptionEnabled()) {
            if ($cartItemsCount >= 1) {
                $this->paymentType = $observer->getRequest()->getParam('paymentOption');
                if ($this->paymentType == "subscription") {
                    $message = $this->verifyProductInCart($cartItemsAll, $cartItemsCount, "oneTime");
                } else if ($this->paymentType == "oneTime") {
                    $message = $this->verifyProductInCart($cartItemsAll, $cartItemsCount, "subscription");
                }

                if (!empty($message)) {
                    $observer->getRequest()->setParam('product', false);
                    return $this->_messageManager->addErrorMessage(__($message));
                }
            }
        }
    }

    /**
     * @param $cartItemsAll
     * @param $cartItemsCount
     * @param $validateTo
     * @return string
     */
    private function verifyProductInCart($cartItemsAll, $cartItemsCount, $validateTo): string
    {
        $message = "";
        if($this->validateNonSubscriptionProductInCart($cartItemsAll, $validateTo)){
            $message = "You cannot have regular products and subscriptions product in your shopping cart";
        } else if($cartItemsCount >= 1 && $this->paymentType != "oneTime") {
            $message = "You can only have 1 recurring subscription product in your shopping cart at a time.";
        }
        return $message;

    }

    /**
     * @param $cartItems
     * @param $validateTo
     * @return bool
     */
    private function validateNonSubscriptionProductInCart($cartItems, $validateTo): bool
    {
        $this->_logger->info("-----------------Validating cart started-----------------");
        foreach ($cartItems as $item) {
            /* @var \Magento\Quote\Model\Quote\Item $item */
            foreach ($item->getOptions() as $option){
                /* @var \Magento\Quote\Model\Quote\Item\Option $option */
                $optionData = json_decode($option->getValue(),true);
                if(in_array($validateTo, $optionData)){
                    return true;
                }
            }
        }
        $this->_logger->info("-----------------Validating cart ended-----------------");
        return false;
    }
}
