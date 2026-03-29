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
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var Subscription
     */
    private $subscriptionHelper;
    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    public function __construct(
        LoggerInterface $logger,
        Subscription $subscriptionHelper,
        ProductRepositoryInterface $productRepository,
        Cart  $cart,
        ManagerInterface $messageManager
    ) {
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->subscriptionHelper = $subscriptionHelper;
        $this->cart = $cart;
        $this->messageManager = $messageManager;
    }

    /**
     * @param  Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $result = $observer->getEvent()->getResult();
        $code = $observer->getEvent()->getMethodInstance()->getCode();
        $quote = $this->cart->getQuote();

        if ($this->cart->getQuote()->getItemsCount() > 1) {
            $this->disablePaymentMethod($result, SubscriptionPaymentMethod::METHOD_CODE, $code);
        } else {
            /* @var \Magento\Quote\Model\Quote $quote */
            if($quote->getIsActive()) {
                foreach ($quote->getItems() as $item) {
                    /* @var \Magento\Quote\Model\Quote\Item $item */
                    $productId = $item->getProduct()->getId();
                }
                $product = $this->productRepository->getById($productId);

                $isSubscriptionProduct = $this->subscriptionHelper->validateIsASubscriptionProduct($this->cart->getQuote()->getAllItems(), "subscription");

                if ($this->subscriptionHelper->isSubscriptionActive() && $product->getRazorpaySubscriptionEnabled() && $isSubscriptionProduct) {
                    $this->disablePaymentMethod($result, PaymentMethod::METHOD_CODE, $code);
                } else {
                    $this->disablePaymentMethod($result, SubscriptionPaymentMethod::METHOD_CODE, $code);
                }
            } else {
                return $this->messageManager->addErrorMessage(__(" No items in the cart"));
            }
        }
    }

    /**
     * Disabling the payment method
     *
     * @param $result
     * @param $paymentMethod
     * @param $methodCode
     */
    public function disablePaymentMethod($result, $paymentMethod, $methodCode)
    {
        $this->logger->info("-------------disabling  $paymentMethod ---------------------");
        if($methodCode == $paymentMethod) {
            $result->setData('is_available', false);
        }
    }
}
