<?php

namespace Razorpay\Subscription\Helper;

use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use \Psr\Log\LoggerInterface;
use Razorpay\Magento\Model\PaymentMethod;
use Razorpay\Subscription\Model\SubscriptionPaymentMethod;

class SubscriptionWebhook
{

    /**
     * @var LoggerInterface
     */
    private $_logger;
    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    private $_objectManagement;

    public function __construct($logger, $quoteRepository, $order, $storeManagement, $cache, $quoteManagement, $quoteFactory, $cart, $productFactory)
    {
//        $this->objectManagement   = \Magento\Framework\App\ObjectManager::getInstance();
//        $this->quoteManagement    = new \Magento\Quote\Model\QuoteManagement();
//        $this->checkoutFactory    = new \Razorpay\Magento\Model\CheckoutFactory();
//        $this->quoteRepository    = new \Magento\Quote\Model\QuoteRepository();
//        $this->storeManagement    = new \Magento\Store\Model\StoreManagerInterface();
//        $this->customerRepository = new \Magento\Customer\Api\CustomerRepositoryInterface();
//        $this->eventManager       = new \Magento\Framework\Event\ManagerInterface();
//        $this->cache = new \Magento\Framework\App\CacheInterface();
        $this->_logger =  $logger;
        $this->_quoteRepository = $quoteRepository;
        $this->_order = $order;
        $this->_storeManagement = $storeManagement;
        $this->_cache = $cache;
        $this->_quoteManagement = $quoteManagement;
        $this->_objectManagement   = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_quoteFactory = $quoteFactory;
        $this->_cart = $cart;
        $this->_productFactory = $productFactory;


    }

    public function processSubscriptionCharged($data)
    {
        $this->_logger->info("Razorpay Webhook for subscription started for the event {$data['event']}");

        $paymentId = $data['payload']['payment']['entity']['id'];
        $rzpSubscriptionId = $data['payload']['subscription']['entity']['id'];
        $quoteId = $data['payload']['payment']['entity']['notes']['merchant_quote_id'];

        if (empty($quoteId))
        {
            $this->_logger->info("Razorpay Webhook: Quote ID not set for Razorpay subscription id(:$rzpSubscriptionId)");
            return;
        }

        $orderLinkCollection = $this->_objectManagement->get('Razorpay\Magento\Model\OrderLink')
            ->getCollection()
            ->addFilter('quote_id', $quoteId)
            ->addFilter('rzp_payment_id',$paymentId)
            ->addFilter('rzp_order_id', $rzpSubscriptionId)
            ->getFirstItem()
            ;

        $orderLink = $orderLinkCollection->getData();

        if(empty($orderLink["entity_id"])){
            $this->_logger->info("Razorpay Webhook processing started for Razorpay subscription id (:$rzpSubscriptionId), quote id (:$quoteId), payment_id(:$paymentId)");

            $amount = number_format($data['payload']['payment']['entity']['amount']/100, 2, ".", "");

            # fetch the related sales order and verify the payment ID with rzp payment id
            # To avoid duplicate order entry for same quote
            $collection = $this->_objectManagement->get('Magento\Sales\Model\Order')
                ->getCollection()
                ->addFieldToSelect('entity_id')
                ->addFilter('quote_id', $quoteId)
                ->getFirstItem();

            $salesOrder = $collection->getData();

            if (!empty($salesOrder['entity_id']))
            {
                $order = $this->_order->load($salesOrder['entity_id']);
                $orderRzpPaymentId = $order->getPayment()->getLastTransId();
//                if ($orderRzpPaymentId === $paymentId)
//                {
//                    $this->_logger->info("Razorpay Webhook: Sales Order and payment already exist for Razorpay payment_id(:$paymentId)");
//                    return;
//                }
            }






            /* @var \Magento\Quote\Model\Quote $quote */
            $quote = $this->_quoteFactory->create()->load($quoteId);
            $items = $quote->getAllVisibleItems();

            foreach ($items as $item)
            {
                $productId =$item->getProductId();
                $_product = $this->_productFactory->create()->load($productId);

                $options = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());

                $info = $options['info_buyRequest'];
                $request1 = new \Magento\Framework\DataObject();
                $request1->setData($info);

                $this->_cart->addProduct($_product, $request1);
            }

            $this->_cart->save();
            $quote = $this->getQuoteObject($data, $quoteId, $this->_cart);

            $this->_logger->info("Razorpay Webhook: Order creation started with quoteID:$quoteId.");

            //Now start processing the new order creation through webhook

            $this->_cache->save("started", "quote_processing_$quoteId", ["razorpay_subscription"], 30);

            $this->_logger->info("Razorpay Webhook: Quote submitted for order creation with quoteID:$quoteId.");

//            $order = $this->_quoteManagement->submit($quote);

            $order = $this->_quoteManagement->submit($quote);
            print_r("SDFsdfsdssssddsdsd");die;
            $payment = $order->getPayment();

            $this->logger->info("Razorpay Webhook: Adding payment to order for quoteID:$quoteId.");

            $payment->setAmountPaid($amount)
                ->setLastTransId($paymentId)
                ->setTransactionId($paymentId)
                ->setIsTransactionClosed(true)
                ->setShouldCloseParentTransaction(true);

            //set razorpay webhook fields
            $order->setByRazorpayWebhook(1);

            $order->save();

            //disable the quote
            $quote->setIsActive(0)->save();
\print_r("here");die;
            $payment = $order->getPayment();
print_R($payment);die;
            $this->logger->info("Razorpay Webhook: Adding payment to order for quoteID:$quoteId.");

            $payment->setAmountPaid($amount)
                ->setLastTransId($paymentId)
                ->setTransactionId($paymentId)
                ->setIsTransactionClosed(true)
                ->setShouldCloseParentTransaction(true);

            //set razorpay webhook fields
            $order->setByRazorpayWebhook(1);

            $order->save();

            //disable the quote
            $quote->setIsActive(0)->save();

            //dispatch the "razorpay_webhook_order_placed_after" event
            $eventData = [
                'raorpay_payment_id' => $paymentId,
                'magento_quote_id' => $quoteId,
                'magento_order_id' => $order->getEntityId(),
                'amount_captured' => $data['payload']['payment']['entity']['amount']
            ];

            $transport = new DataObject($eventData);

            $this->eventManager->dispatch(
                'razorpay_webhook_order_placed_after',
                [
                    'context'   => 'razorpay_webhook_order',
                    'payment'   => $paymentId,
                    'transport' => $transport
                ]
            );

            $this->logger->info("Razorpay Webhook Processed successfully for Razorpay payment_id(:$paymentId): and quoteID(: $quoteId) and OrderID(: ". $order->getEntityId() .")");

            return;
//            if ($orderLink['order_placed'])
//            {
//                $this->logger->info(__("Razorpay Webhook: Quote order is already placed and  quotes in active for quoteID: $quoteId and Razorpay payment_id(:$paymentId) with Maze OrderID (:%1) ", $orderLink['increment_order_id']));
//
//                return;
//            }
//            //set the 1st webhook notification time
//            if ($orderLink['webhook_count'] < 1)
//            {
//                $orderLinkCollection->setWebhookFirstNotifiedAt(time());
//            }
//
//            $orderLinkCollection->setWebhookCount($orderLink['webhook_count'] + 1)
//                ->setRzpPaymentId($paymentId)
//                ->save();
        } else {

        }



        return "";
    }

    public function processSubscriptionAuthenticated($data)
    {
        return "";
    }

    protected function getQuoteObject($data, $quoteId, $cart)
    {
        $quote = $this->_quoteRepository->get($quoteId);

        $firstName = $quote->getBillingAddress()->getFirstname() ?? 'null';
        $lastName  = $quote->getBillingAddress()->getLastname() ?? 'null';
        $email     = $quote->getBillingAddress()->getEmail() ?? $data['payload']['payment']['entity']['email'];

        $cart->getPayment()->setMethod(SubscriptionPaymentMethod::METHOD_CODE);

        $store = $quote->getStore();

        if(empty($store) === true)
        {
            $store = $this->_storeManagement->getStore();
        }

        $websiteId = $store->getWebsiteId();

        $customer = $this->_objectManagement->create('Magento\Customer\Model\Customer');

        $customer->setWebsiteId($websiteId);

        //get customer from quote , otherwise from payment email
        $customer = $customer->loadByEmail($email);

        //if quote billing address doesn't contains address, set it as customer default billing address
        if ((empty($quote->getBillingAddress()->getFirstname()) === true) and
            (empty($customer->getEntityId()) === false))
        {
            $cart->getBillingAddress()->setCustomerAddressId($customer->getDefaultBillingAddress()['id']);
        }

        //If need to insert new customer as guest
        if ((empty($customer->getEntityId()) === true) or
            (empty($quote->getBillingAddress()->getCustomerId()) === true))
        {
            $cart->setCustomerFirstname($firstName);
            $cart->setCustomerLastname($lastName);
            $cart->setCustomerEmail($email);
            $cart->setCustomerIsGuest(true);
            $cart->getBillingAddress()->setfirstName($firstName);
            $cart->getBillingAddress()->setLastName($lastName);
            $cart->getBillingAddress()->setStreet($quote->getBillingAddress()->getStreet());
            $cart->getBillingAddress()->setCity($quote->getBillingAddress()->getCity());
            $cart->getBillingAddress()->setRegion($quote->getBillingAddress()->getRegion());
            $cart->getBillingAddress()->setRegionId($quote->getBillingAddress()->getRegionId());
            $cart->getBillingAddress()->setPostCode($quote->getBillingAddress()->getPostCode());
            $cart->getBillingAddress()->setCountry($quote->getBillingAddress()->getCountry());
            $cart->getBillingAddress()->setTelephone($quote->getBillingAddress()->getTelephone());

            $cart->getShippingAddress()->setfirstName($firstName);
            $cart->getShippingAddress()->setLastName($lastName);
            $cart->getShippingAddress()->setStreet($quote->getBillingAddress()->getStreet());
            $cart->getShippingAddress()->setCity($quote->getBillingAddress()->getCity());
            $cart->getShippingAddress()->setRegion($quote->getBillingAddress()->getRegion());
            $cart->getShippingAddress()->setRegionId($quote->getBillingAddress()->getRegionId());
            $cart->getShippingAddress()->setPostCode($quote->getBillingAddress()->getPostCode());
            $cart->getShippingAddress()->setCountry($quote->getBillingAddress()->getCountry());
            $cart->getShippingAddress()->setTelephone($quote->getBillingAddress()->getTelephone());
        }
//        $cart->setGrandTotal($quote->getGrandTotal());
//        $cart->setBaseGrandTotal($quote->getBaseGrandTotal());
//        $cart->setCheckoutMethod($quote->getCheckoutMethod());
        $cart->setCustomerId($quote->getCustomerId());
//        $cart->setSubtotal($quote->getSubtotal());
//        $cart->setBaseSubtotal($quote->getBaseSubtotal());
//        $cart->setSubtotalWithDiscount($quote->getSubtotalWithDiscount());
//        $cart->setBaseSubtotalWithDiscount($quote->getBaseSubtotalWithDiscount());
//        $cart->setBaseSubtotalWithDiscount($quote->getBaseSubtotalWithDiscount());


        //skip address validation
        $cart->getBillingAddress()->setShouldIgnoreValidation(true);
        $cart->getShippingAddress()->setShouldIgnoreValidation(true);

        $cart->setStore($store);

        $cart->collectTotals();

        $cart->save();


        return $cart;
    }

}
