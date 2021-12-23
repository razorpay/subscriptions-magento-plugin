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
    private $messageManager;

    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Product
     */
    private $product;

    /**
     * @var Subscription
     */
    private $subscriptionHelper;

    private $paymentType;

    public function __construct(
        ManagerInterface $messageManager,
        Cart  $cart,
        Product $product,
        LoggerInterface $logger,
        Subscription $subscriptionHelper
    )
    {
        $this->messageManager = $messageManager;
        $this->cart = $cart;
        $this->logger = $logger;
        $this->product = $product;
        $this->subscriptionHelper = $subscriptionHelper;
    }

    public function execute(Observer $observer)
    {
        $cartItemsCount = $this->cart->getQuote()->getItemsCount();
        $allCartItems = $this->cart->getQuote()->getAllItems();
        $productId = $observer->getRequest()->getParam('product');
        $product = $this->product->load($productId);

        if($product->getRazorpaySubscriptionEnabled() && $this->subscriptionHelper->isSubscriptionActive()) {
            $this->logger->info("Checking for product type before adding to cart");
            if ($cartItemsCount >= 1) {
                $this->paymentType = $observer->getRequest()->getParam('paymentOption');
                if ($this->paymentType == "subscription") {
                    $message = $this->verifyProductInCart($allCartItems, $cartItemsCount, "oneTime");
                } else if ($this->paymentType == "oneTime") {
                    $message = $this->verifyProductInCart($allCartItems, $cartItemsCount, "subscription");
                }

                if (!empty($message)) {
                    $observer->getRequest()->setParam('product', false);
                    return $this->messageManager->addErrorMessage(__($message));
                }
            }
        }
        $this->logger->info("validation ended");
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
        if($this->subscriptionHelper->validateIsASubscriptionProduct($allCartItems, $validateTo)){
            $message = "You cannot have regular products and subscriptions product in your shopping cart";
        } else if($cartItemsCount >= 1 && $this->paymentType != "oneTime") {
            $message = "You can only have 1 recurring subscription product in your shopping cart at a time.";
        }
        return $message;
    }

}
