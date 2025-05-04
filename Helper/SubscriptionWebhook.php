<?php

namespace Razorpay\Subscription\Helper;

use Magento\Framework\DataObject;
use \Psr\Log\LoggerInterface;
use Razorpay\Magento\Model\Config;
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
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $_storeManagement;
    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    private $_cache;
    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    private $_quoteRepository;
    /**
     * @var mixed|Config
     */
    private $_config;

    private $_quoteFactory;

    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    private $_quoteManagement;


    public function __construct($logger)
    {
        $this->_logger = $logger;
        $this->_objectManagement = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_storeManagement = $this->_objectManagement->get(\Magento\Store\Model\StoreManagerInterface::class);
        $this->_cache= $this->_objectManagement->get(\Magento\Framework\App\CacheInterface::class);
        $this->_quoteRepository= $this->_objectManagement->get(\Magento\Quote\Model\QuoteRepository::class);
        $this->_config= $this->_objectManagement->get(\Razorpay\Magento\Model\Config::class);
        $this->_quoteManagement= $this->_objectManagement->get(\Magento\Quote\Model\QuoteManagement::class);
        $this->_quoteFactory = new  \Magento\Quote\Model\QuoteFactory($this->_objectManagement);

    }

    /**
     * Processing subscription charge event
     *
     * @param  $data
     * @return int|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processSubscriptionCharged($data)
    {
        $this->_logger->info("Razorpay Subscription Webhook for subscription started for the event {$data['event']}");

        $paymentId = $data['payload']['payment']['entity']['id'];
        $rzpSubscriptionId = $data['payload']['subscription']['entity']['id'];
        $quoteId = $data['payload']['subscription']['entity']['notes']['magento_quote_id'];
        $webHookSource = $data['payload']['subscription']['entity']['source'];
        $amount = number_format($data['payload']['payment']['entity']['amount'] / 100, 2, ".", "");

        if (empty($quoteId)) {
            $this->_logger->info("Razorpay Subscription Webhook: Quote ID not set for Razorpay subscription id(:$rzpSubscriptionId)");
            return;
        }

        $orderLinkCollection = $this->_objectManagement->get('Razorpay\Magento\Model\OrderLink')
            ->getCollection()
            ->addFilter('quote_id', $quoteId)
            ->addFilter('rzp_order_id', $rzpSubscriptionId)
            ->getFirstItem();

        $orderLink = $orderLinkCollection->getData();

        // Process only if its from magento source
        if ($webHookSource == "magento-subscription") {
            if (!empty($orderLink['entity_id'])) {
                // Check if front-end cache flag active
                if (empty($this->_cache->load("quote_Front_processing_" . $quoteId)) === false) {
                    $this->_logger->info("Razorpay Subscription Webhook: Order processing is active for quoteID: $quoteId and Razorpay payment_id(:$paymentId)");
                    header('Status: 409 Conflict, too early for processing', true, 409);
                    $orderLinkCollection->setWebhookFirstNotifiedAt(time())
                        ->setWebhookCount($orderLink['webhook_count'] + 1)
                        ->save();
                    return;
                }

                $webhookWaitTime = $this->_config->getConfigData(Config::WEBHOOK_WAIT_TIME) ?? 300;

                //ignore webhook call for some time as per config, from first webhook call
                if ((time() - $orderLinkCollection->getWebhookFirstNotifiedAt()) < $webhookWaitTime) {
                    $this->_logger->info(__("Razorpay Subscription Webhook: Order processing is active for quoteID: $quoteId and Razorpay payment_id(:$paymentId) and webhook attempt: %1", ($orderLink['webhook_count'] + 1)));
                    $orderLinkCollection->setWebhookFirstNotifiedAt(time())
                        ->setWebhookCount($orderLink['webhook_count'] + 1)
                        ->save();
                    header('Status: 409 Conflict, too early for processing', true, 409);

                    return;
                }

                // checking if payment id is null hen processing same quote for order else creating new order
                if (empty($orderLink['rzp_payment_id'])) {
                    //validate if the quote Order is still active
                    $quote =  $this->_quoteRepository->get($quoteId);

                    //exit if quote is not active
                    if (!$quote->getIsActive()) {
                        $this->_logger->info("Razorpay Subscription Webhook: Quote order is inactive for quoteID: $quoteId and Razorpay payment_id(:$paymentId)");
                        return;
                    }

                    if ($this->verifyPaymentIdTowardsOrder($quoteId, $paymentId)) {
                        return;
                    }

                    $quote = $this->getQuoteObject($data, $quoteId);

                    $quoteUpdated = $this->_quoteRepository->get($quoteId);

                    //exit if quote is not active
                    if (!$quoteUpdated->getIsActive()) {
                        $this->_logger->info("Razorpay Subscription Webhook: Quote order is inactive for quoteID: $quoteId and Razorpay payment_id(:$paymentId)");
                        return;
                    }


                    if ($orderLink['order_placed']) {
                        $this->_logger->info(__("Razorpay Subscription Webhook: Quote order is inactive for quoteID: $quoteId and Razorpay payment_id(:$paymentId) with Maze OrderID (:%1) ", $orderLink['increment_order_id']));
                        return;
                    }

                    //Now start processing the new order creation through webhook

                    $this->_cache->save("started", "quote_processing_$quoteId", ["razorpay"], 30);

                    $this->_logger->info("Razorpay Subscription Webhook: Quote submitted for order creation with quoteID:$quoteId.");

                    $order = $this->createOrderFromQuote($quote, $amount, $paymentId);

                    //disable the quote
                    $quote->setIsActive(0)->save();
                    $orderLinkCollection->setWebhookFirstNotifiedAt(time())
                        ->setOrderId($order->getId())
                        ->setIncrementOrderId($order->getRealOrderId())
                        ->setRzpPaymentId($paymentId)
                        ->setByWebhook(true)
                        ->setorderPlaced(true)
                        ->setWebhookCount($orderLink['webhook_count'] + 1)
                        ->save();
                } else {
                    $this->_logger->info("Razorpay Subscription Webhook processing started for Razorpay subscription id (:$rzpSubscriptionId), quote id (:$quoteId), payment_id(:$paymentId)");

                    if ($this->verifyPaymentIdTowardsOrder($quoteId, $paymentId)) {
                        return;
                    }

                    // check if payment id exist for same subscription id
                    $orderLinkCollection = $this->_objectManagement->get('Razorpay\Magento\Model\OrderLink')
                        ->getCollection()
                        ->addFilter('rzp_payment_id', $paymentId)
                        ->addFilter('rzp_order_id', $rzpSubscriptionId)
                        ->addFilter('order_placed', true)
                        ->getFirstItem();

                    $orderLink = $orderLinkCollection->getData();
                    if(!empty($orderLink["entity_id"])){
                        $this->_logger->info("Razorpay Subscription Webhook: Sales Order and payment already exist for Razorpay payment_id(:$paymentId) and for subscription_id: (:$rzpSubscriptionId)");
                        return ;
                    }

                    $quote  = $this->_quoteRepository->get($quoteId);
                    /* @var \Magento\Quote\Model\Quote $quote */

                    $subscriptionCollection = $this->_objectManagement->get('Razorpay\Subscription\Model\Subscriptions')
                        ->getCollection()
                        ->addFilter('subscription_id', $rzpSubscriptionId)
                        ->getFirstItem();

                    $subscriptionData = $subscriptionCollection->getData();
                    $orderData = $this->createNewOrderData($data, $subscriptionData, $quote);

                    /* @var \Magento\Quote\Model\Quote $newQuote */
                    $newQuote = $this->_quoteFactory->create();
                    $newQuote->setStore($quote->getStore());
                    $newQuote->setWebsite($quote->getStore()->getWebsiteId());

                    $firstName = $quote->getBillingAddress()->getFirstname() ?? 'null';
                    $lastName = $quote->getBillingAddress()->getLastname() ?? 'null';
                    $email = $quote->getBillingAddress()->getEmail() ?? $data['payload']['payment']['entity']['email'];

                    $customer = $this->_objectManagement->create('Magento\Customer\Model\Customer');

                    $customer->setWebsiteId($quote->getStore()->getWebsiteId());

                    $customer = $customer->loadByEmail($email);

                    //If need to insert new customer as guest
                    if ((empty($customer->getEntityId()) === true) or
                        (empty($quote->getBillingAddress()->getCustomerId()) === true)) {
                        $newQuote->setCustomerFirstname($firstName);
                        $newQuote->setCustomerLastname($lastName);
                        $newQuote->setCustomerEmail($email);
                        $newQuote->setCustomerIsGuest(true);
                    } else {
                        $newQuote->assignCustomer($customer);
                    }

                    foreach ($quote->getAllItems() as $quoteItem) {
                        /* @var \Magento\Quote\Model\Quote\Item $quoteItem */
                        $productCustomData = json_decode($quoteItem->getOptionByCode("info_buyRequest")->getValue(), true);
                    }
                    // Add subscription product in quote
                    foreach ($orderData['items'] as $item) {
                        $product = $this->_objectManagement->create('Magento\Catalog\Model\Product')->load($item['product_id']);
                        $product->setPrice($product->getPrice());
                        $newQuote->addProduct($product, new DataObject($productCustomData)
                        );
                    }

                    // Set Addresses to quote
                    $newQuote->getBillingAddress()->addData($orderData['shipping_address']);
                    $newQuote->getShippingAddress()->addData($orderData['shipping_address']);

                    // Collect shipping rates, set Shipping & Payment Method
                    $shippingAddress = $newQuote->getShippingAddress();
                    $shippingAddress->setCollectShippingRates(true)
                        ->collectShippingRates()
                        ->setShippingMethod($quote->getShippingAddress()->getShippingMethod());
                    $newQuote->getPayment()->setMethod(SubscriptionPaymentMethod::METHOD_CODE);
                    $newQuote->setIsMultiShipping(0);
                    $newQuote->collectTotals()->save();


                    $order = $this->createOrderFromQuote($newQuote, $amount, $paymentId);
                    //disable the quote
                    $newQuote->setIsActive(0)->save();


                    // saving in razorpay_sales_order details
                    $orderLink = $this->_objectManagement->create('Razorpay\Magento\Model\OrderLink');
                    $orderLink->setQuoteId($newQuote->getId())
                        ->setRzpOrderId($rzpSubscriptionId)
                        ->setOrderId($order->getId())
                        ->setIncrementOrderId($order->getRealOrderId())
                        ->setRzpPaymentId($paymentId)
                        ->setByWebhook(true)
                        ->setorderPlaced(true)
                        ->setWebhookCount(1)
                        ->setWebhookFirstNotifiedAt(time())
                        ->setWebhookCount(1)
                        ->save();

                }

                $this->_logger->info("Razorpay Subscription Webhook Processed successfully for Razorpay payment_id(:$paymentId): and quoteID(: $quoteId) and OrderID(: " . $order->getEntityId() . ")");
            }
        }
        return 0;
    }

    /**
     * Processing subscription pause/resume/cancel event
     * @param $data
     * @return int|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processSubscriptionAction($data)
    {
         $this->_logger->info("Razorpay Subscription Webhook for subscription started for the event {$data['event']}");

         $rzpSubscriptionId = $data['payload']['subscription']['entity']['id'];
         $quoteId = $data['payload']['subscription']['entity']['notes']['magento_quote_id'];
         $webHookSource = $data['payload']['subscription']['entity']['notes']['source'];
         $status = $data['payload']['subscription']['entity']['status'];

        if (empty($quoteId)) {
            $this->_logger->info("Razorpay Subscription Webhook: Quote ID not set for Razorpay subscription id(:$rzpSubscriptionId)");
            return;
        }

        // Process only if its from magento source
        if ($webHookSource == "magento-subscription") {
            $subscription = $this->_objectManagement->create('Razorpay\Subscription\Model\Subscriptions');
            $postUpdate = $subscription->load($rzpSubscriptionId, 'subscription_id');
            $postUpdate->setStatus($status);
            if($status == "cancelled"){
                $postUpdate->setCancelBy('Razorpay');
            }
            $postUpdate->save();

            $this->_logger->info("Razorpay Subscription Webhook Processed successfully for Razorpay subscriptionId(:$rzpSubscriptionId)");
        }
        return 0;
    }

    /**
     * @param $post
     * @param $quoteId
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getQuoteObject($post, $quoteId)
    {
        $quote  = $this->_quoteRepository->get($quoteId);

        $firstName = $quote->getBillingAddress()->getFirstname() ?? 'null';
        $lastName = $quote->getBillingAddress()->getLastname() ?? 'null';
        $email = $quote->getBillingAddress()->getEmail() ?? $post['payload']['payment']['entity']['email'];

        $quote->getPayment()->setMethod(SubscriptionPaymentMethod::METHOD_CODE);

        $store = $quote->getStore();

        if (empty($store) === true) {
            $store = $this->_storeManagement->getStore();
        }

        $websiteId = $store->getWebsiteId();

        $customer = $this->_objectManagement->create('Magento\Customer\Model\Customer');

        $customer->setWebsiteId($websiteId);

        //get customer from quote , otherwise from payment email
        $customer = $customer->loadByEmail($email);

        //if quote billing address doesn't contains address, set it as customer default billing address
        if ((empty($quote->getBillingAddress()->getFirstname()) === true)
            and (empty($customer->getEntityId()) === false)
        ) {
            $quote->getBillingAddress()->setCustomerAddressId($customer->getDefaultBillingAddress()['id']);
        }

        //If need to insert new customer as guest
        if ((empty($customer->getEntityId()) === true)
            or (empty($quote->getBillingAddress()->getCustomerId()) === true)
        ) {
            $quote->setCustomerFirstname($firstName);
            $quote->setCustomerLastname($lastName);
            $quote->setCustomerEmail($email);
            $quote->setCustomerIsGuest(true);
        }

        //skip address validation as some time billing/shipping address not set for the quote
        $quote->getBillingAddress()->setShouldIgnoreValidation(true);
        $quote->getShippingAddress()->setShouldIgnoreValidation(true);

        $quote->setStore($store);

        $quote->collectTotals();

        $quote->save();

        return $quote;
    }


    /**
     * Fetch the related sales order and verify
     * the payment ID with rzp payment id
     * To avoid duplicate order entry for same quote
     *
     * @param  $quoteId
     * @param  $paymentId
     * @return bool
     */
    protected function verifyPaymentIdTowardsOrder($quoteId, $paymentId): bool
    {
        $collection = $this->_objectManagement->get('Magento\Sales\Model\Order')
            ->getCollection()
            ->addFieldToSelect('entity_id')
            ->addFilter('quote_id', $quoteId)
            ->getFirstItem();

        $salesOrder = $collection->getData();

        if (!empty($salesOrder['entity_id'])) {
            $order = $this->_objectManagement->get('Magento\Sales\Model\Order')->load($salesOrder['entity_id']);
            /* @var \Magento\Sales\Model\Order $order */
            $orderRzpPaymentId = $order->getPayment()->getLastTransId();
            if ($orderRzpPaymentId === $paymentId) {
                $this->_logger->info("Razorpay Subscription Webhook: Sales Order and payment already exist for Razorpay payment_id(:$paymentId)");
                return true;
            }
        }
        return false;
    }

    /**
     * @param  $data
     * @param  $subscriptionData
     * @param  $quote
     * @return array
     */
    protected function createNewOrderData($data, $subscriptionData, $quote): array
    {
        return [
            'email' => $data['payload']['payment']['entity']["email"],
            'currency_id' => $quote->getBaseCurrencyCode(),
            'shipping_address' => [
                'firstname' => $quote->getShippingAddress()->getFirstname(),
                'lastname' => $quote->getShippingAddress()->getLastname(),
                'street' => $quote->getShippingAddress()->getStreet(),
                'city' => $quote->getShippingAddress()->getCity(),
                'country_id' => $quote->getShippingAddress()->getCountryId(),
                'region' => $quote->getShippingAddress()->getRegionId(),
                'postcode' => $quote->getShippingAddress()->getPostcode(),
                'telephone' => $quote->getShippingAddress()->getTelephone(),
            ],
            'billing_address' => [
                'firstname' => $quote->getBillingAddress()->getFirstname(),
                'lastname' => $quote->getBillingAddress()->getLastname(),
                'street' => $quote->getBillingAddress()->getStreet(),
                'city' => $quote->getBillingAddress()->getCity(),
                'country_id' => $quote->getBillingAddress()->getCountryId(),
                'region' => $quote->getBillingAddress()->getRegionId(),
                'postcode' => $quote->getBillingAddress()->getPostcode(),
                'telephone' => $quote->getBillingAddress()->getTelephone(),
            ],
            'items' => [
                [
                    'product_id' => $subscriptionData['product_id'],
                    'qty' => $data['payload']['subscription']['entity']['quantity'],
                ]
            ],
        ];
    }

    /**
     * creating new order from quote
     *
     * @param  $quote
     * @param  $amount
     * @param  $paymentId
     * @return mixed
     */
    protected function createOrderFromQuote($quote, $amount, $paymentId)
    {
        $order = $this->_quoteManagement->submit($quote);

        $payment = $order->getPayment();

        $this->_logger->info("Razorpay Subscription Webhook: Adding payment to order for quoteID: " . $quote->getId() . ".");

        $payment->setAmountPaid($amount)
            ->setLastTransId($paymentId)
            ->setTransactionId($paymentId)
            ->setIsTransactionClosed(true)
            ->setShouldCloseParentTransaction(true);

        //set Razorpay Webhook fields
        $order->setByRazorpayWebhook(1);

        $order->save();

        return $order;
    }
}