<?php

namespace Razorpay\Subscription\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment;
use Razorpay\Subscription\Model\SubscriptionPaymentMethod;

/**
 * Class OrderPlaceSaveAfterObserver
 */
class OrderPlaceSaveAfterObserver implements ObserverInterface
{

    /**
     * Store key
     */
    const STORE = 'store';

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;



    /**
     * StatusAssignObserver constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Razorpay\Magento\Model\Config $config,
        \Razorpay\Magento\Model\PaymentMethod $rzpMethod
    ) {
        $this->orderRepository = $orderRepository;
        $this->checkoutSession = $checkoutSession;
        $this->config = $config;

        $this->rzpMethod = $rzpMethod;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {

        $order = $observer->getOrder();

        /** @var Payment $payment */
        $payment = $order->getPayment();

        $pay_method = $payment->getMethodInstance();

        $code = $pay_method->getCode();

        if($code === SubscriptionPaymentMethod::METHOD_CODE)
        {
            $this->updateOrderLinkStatus($payment);

        }

    }

    /**
     * @param Payment $payment
     *
     * @return void
     */
    private function updateOrderLinkStatus(Payment $payment)
    {
        $order = $payment->getOrder();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $lastQuoteId = $order->getQuoteId();
        $rzpPaymentId  = $payment->getLastTransId();

        $amount_paid = number_format($this->rzpMethod->getAmountPaid($rzpPaymentId) / 100, 2, ".", "");

        $order->addStatusHistoryComment(
            __('Actual Amount Paid of %1, with Razorpay Offer/Fee applied.',  $order->getBaseCurrency()->formatTxt($amount_paid))
        );
        $order->save();

        //update quote
        $quote = $objectManager->get('Magento\Quote\Model\Quote')->load($lastQuoteId);
        $quote->setIsActive(false)->save();
        $this->checkoutSession->replaceQuote($quote);

        //update razorpay orderLink
        $orderLinkCollection = $objectManager->get('Razorpay\Magento\Model\OrderLink')
            ->getCollection()
            ->addFieldToSelect('entity_id')
            ->addFilter('quote_id', $lastQuoteId)
            ->addFilter('rzp_payment_id', $rzpPaymentId)
            ->addFilter('increment_order_id', $order->getRealOrderId())
            ->getFirstItem();

        $orderLink = $orderLinkCollection->getData();

        if (empty($orderLink['entity_id']) === false)
        {
            $orderLinkCollection->setOrderId($order->getEntityId())
                ->setOrderPlaced(true)
                ->save();
        }

    }
}