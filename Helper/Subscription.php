<?php

namespace Razorpay\Subscription\Helper;

use Magento\Checkout\Model\Cart;
use Magento\Framework\App\Helper\AbstractHelper;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\Product;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\Error;
use Razorpay\Subscription\Model\SubscriptionConfig;

class Subscription extends AbstractHelper
{
    /**
     * @var Product
     */
    private $product;
    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    private $objectManagement;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var SubscriptionConfig
     */
    private $subscriptionConfig;
    /**
     * @var Cart
     */
    private $cart;
    public function __construct(Product $product, LoggerInterface $logger, Cart  $cart, SubscriptionConfig $subscriptionConfig)
    {
        $this->product = $product;
        $this->logger = $logger;
        $this->subscriptionConfig = $subscriptionConfig;
        $this->cart = $cart;
        $this->objectManagement = \Magento\Framework\App\ObjectManager::getInstance();
    }

    /**
     * This function is used to create subscription for the user
     *
     * @param  $quote
     * @param  $rzp
     * @return mixed
     * @throws \Exception
     */
    public function createSubscription($quote, $rzp)
    {
        try {
            /* @var \Magento\Quote\Model\Quote $quote */

            $planData = $this->fetchPlanId($quote, $rzp);
            $this->logger->info("-------------------------Creating Subscription---------------------------");

            if ($quote->getIsActive()) {

                $subscriptionData = [
                    "customer_id" => $this->getCustomerId($quote, $rzp),
                    "plan_id" => $planData['plan_id'],
                    "total_count" => (int)$planData['plan_bill_cycle'],
                    "quantity" => (int)$quote->getItemsQty(),
                    "customer_notify" => 0,
                    "notes" => [
                        "source" => "magento-subscription",
                        "magento_quote_id" => $quote->getId(),
                    ],
                    "source" => "magento-subscription",

                ];
                    
                if ($planData['plan_trial']) {
                    $subscriptionData["start_at"] = strtotime("+{$planData['plan_trial']} days");
                }
                $items = $item = [];
                if ($quote->getShippingAddress()->getShippingAmount() && $quote->getShippingAddress()->getShippingAmount() > 0) {
                    $item["item"] = [
                        "name" => "Shipping charges",
                        "amount" => (int)(number_format($quote->getShippingAddress()->getShippingAmount() * 100, 0, ".", "")),
                        "currency" => $quote->getQuoteCurrencyCode(),
                        "description" => "Shipping charges"
                    ];
                    array_push($items, $item);
                    $subscriptionData["addons"] = $items;
                }
                if($planData["plan_addons"]){
                    $item["item"] = [
                        "name" => "Addon amount",
                        "amount" => (int)(number_format($planData["plan_addons"] * 100, 0, ".", "")),
                        "currency" => $quote->getQuoteCurrencyCode(),
                        "description" => "Addon amount"
                    ];
                    array_push($items, $item);
                    $subscriptionData["addons"] = $items;
                }
                $this->logger->info("Subscription creation data", $subscriptionData);
                $subscriptionResponse = $rzp->subscription->create($subscriptionData);
                $this->logger->info("Subscription response object ", json_decode(json_encode($subscriptionResponse), true));

                $subscription = $this->objectManagement->create('Razorpay\Subscription\Model\Subscriptions');
                $subscription->setPlanEntityId($planData['entity_id'])
                    ->setSubscriptionId($subscriptionResponse->id)
                    ->setRazorpayCustomerId($subscriptionResponse->customer_id)
                    ->setmagentoUserId($quote->getCustomerId())
                    ->setProductId($planData['magento_product_id'])
                    ->setQuoteId($quote->getId())
                    ->setStatus($subscriptionResponse->status)
                    ->setTotalCount($subscriptionResponse->total_count)
                    ->setAuthAttempts($subscriptionResponse->auth_attempts)
                    ->setPaidCount($subscriptionResponse->paid_count)
                    ->setRemainingCount($subscriptionResponse->remaining_count)
                    ->setStartAt(date("Y-m-d h:i:sa", $subscriptionResponse->start_at))
                    ->setEndAt(date("Y-m-d h:i:sa", $subscriptionResponse->end_at))
                    ->setSubscriptionCreatedAt(date("Y-m-d h:i:sa", $subscriptionResponse->created_at))
                    ->setNextChargeAt(date("Y-m-d h:i:sa", $subscriptionResponse->charge_at))
                    ->save();

                return $subscriptionResponse;
            }
        } catch (\Exception $e) {
            $this->logger->critical("Exception: {$e->getMessage()}");
            throw new \Exception($e->getMessage());
        } catch (Error $e) {
            $this->logger->critical("Exception: {$e->getMessage()}");
            throw new \Exception($e->getMessage());
        }
    }

    public function fetchPlanId($quote)
    {
        try {
            $this->logger->info("-------------------------Plan fetch start---------------------------");
            $planType = $product = $planName = $productId = "";
            if ($quote->getIsActive()) {

                $planData = $this->objectManagement->get('Razorpay\Subscription\Model\Plans')
                    ->getCollection()
                    ->addFilter('entity_id', $this->getPlanIdFromQuote($quote))
                    ->addFilter('plan_status', 1)
                    ->getFirstItem()
                    ->getData();

                $this->logger->info("Fetching plan id for the following: Product id: {$planData['magento_product_id']}  product name: {$planData['plan_name']}  Plan type: {$planData['plan_type']} ");

                return $planData;
            }

        } catch (\Exception $e) {
            $this->logger->critical("Exception: {$e->getMessage()}");
            throw new \Exception($e->getMessage());
        } catch (Error $e) {
            $this->logger->critical("Exception: {$e->getMessage()}");
            throw new \Exception($e->getMessage());

        }
    }

    /**
     * This function gets the product details from quotes
     *
     * @param  $quote
     * @return array
     */
    public function getPlanIdFromQuote($quote)
    {
        /* @var \Magento\Quote\Model\Quote $quote */

        foreach ($quote->getItems() as $item) {
            return $item->getBuyRequest()->getPlanId();
        }
    }

    /**
     * This functions creates customers in razorpay
     *
     * @param  $quote
     * @param  $rzp
     * @return mixed
     * @throws \Exception
     */
    public function getCustomerId($quote, $rzp)
    {
        try {
            /* @var \Magento\Quote\Model\Quote $quote */
            $args = [
                'email' => $quote->getBillingAddress()->getEmail(),
                'name' => $quote->getBillingAddress()->getFirstname() . " " . $quote->getBillingAddress()->getLastname(),
                'contact' => $quote->getBillingAddress()->getTelephone()
            ];
            $this->logger->info("Creating or fetching customer info ", $args);

            //
            // This line of code tells api that if a customer is already created,
            // return the created customer instead of throwing an exception
            // https://docs.razorpay.com/v1/page/customers-api
            //
            $args['fail_existing'] = '0';

            $customerResponse = $rzp->customer->create($args);
            $this->logger->info("Customer response object ", json_decode(json_encode($customerResponse), true));

            return $customerResponse->id;
        } catch (\Exception $e) {
            $this->logger->info("Exception: {$e->getMessage()}");
            throw new \Exception($e->getMessage());
        } catch (Error $e) {
            $this->logger->critical("Exception: {$e->getMessage()}");
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * This function get the merchant preferences from razorpay dashboard
     *
     * @param  $apiKey
     * @return array
     */
    public function getMerchantPreferences($apiKey)
    {
        try {
            $api = new Api($apiKey, "");

            $response = $api->request->request("GET", "preferences");
        } catch (Error $e) {
            $this->logger->critical("preferrence: " . $e->getMessage());
            throw new \Exception($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->info("Exception: {$e->getMessage()}");
            throw new \Exception($e->getMessage());
        }

        $preferences = [];

        $preferences['embedded_url'] = Api::getFullUrl("checkout/embedded");
        $preferences['is_hosted'] = false;
        $preferences['image'] = $response['options']['image'];

        if (isset($response['options']['redirect']) && $response['options']['redirect'] === true) {
            $preferences['is_hosted'] = true;
        }
        return $preferences;
    }

    /**
     * @param  $cartItems
     * @param  $validateTo
     * @return bool
     */
    public function validateIsASubscriptionProduct($cartItems, $validateTo)
    {
        try {
            $this->logger->info("-----------------Validating cart started-----------------");
            foreach ($cartItems as $item) {
                /* @var \Magento\Quote\Model\Quote\Item $item */
                foreach ($item->getOptions() as $option) {
                    /* @var \Magento\Quote\Model\Quote\Item\Option $option */
                    $optionData = json_decode($option->getValue(), true);
                    $this->logger->info(json_encode($optionData));
                    if (array_key_exists("paymentOption", $optionData) && in_array($validateTo, $optionData)) {
                        return true;
                    }
                }
            }

            $this->logger->info("-----------------Validating cart ended-----------------");
            return false;
        } catch (\Exception $e) {
            $this->logger->critical("Exception: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Cancel subscription
     *
     * @param  $id
     * @param  $rzp
     * @throws \Exception
     */
    public function cancelSubscription($id, $rzp, $updateBy)
    {
        //fetch and cancel subscription
        $subscriptionResponse = $rzp->subscription->fetch($id)->cancel(["cancel_at_cycle_end" => 0]);

        //update record
        $subscription = $this->objectManagement->create('Razorpay\Subscription\Model\Subscriptions');
        $postUpdate = $subscription->load($subscriptionResponse->id, 'subscription_id');

        $postUpdate->setStatus('cancelled');
        $postUpdate->setCancelBy($updateBy);

        $postUpdate->save();
    }

    /**
     * Pause Subscription
     *
     * @param  $id
     * @return array
     */
    public function pauseSubscription($id, $rzp, $updateBy)
    {
        //fetch and pause subscription
        $subscriptionResponse = $rzp->subscription->fetch($id)->pause(["pause_at"=>"now"]);

        //update record
        $subscription = $this->objectManagement->create('Razorpay\Subscription\Model\Subscriptions');
        $postUpdate = $subscription->load($subscriptionResponse->id, 'subscription_id');

        $postUpdate->setStatus('paused');
        $postUpdate->setCancelBy($updateBy);

        $postUpdate->save();
    }

    /**
     * Resume Subscription
     *
     * @param  $id
     * @return array
     */
    public function resumeSubscription($id, $rzp, $updateBy)
    {
        //fetch and resume subscription
        $subscriptionResponse = $rzp->subscription->fetch($id)->resume(["resume_at"=>"now"]);

        //update record
        $subscription = $this->objectManagement->create('Razorpay\Subscription\Model\Subscriptions');
        $postUpdate = $subscription->load($subscriptionResponse->id, 'subscription_id');

        $postUpdate->setStatus('active');
        $postUpdate->setCancelBy($updateBy);

        $postUpdate->save();
    }

    /**
     * Fetch all Subscription invoices
     *
     * @param  $subscriptionId
     * @return array
     */
    public function fetchSubscriptionInvoice($subscriptionId, $rzp)
    {

        $subscriptionResponse = $rzp->invoice->all(["subscription_id"=>$subscriptionId]);

        //update record
        $subscription = $this->objectManagement->create('Razorpay\Subscription\Model\Subscriptions');
        $postUpdate = $subscription->load($subscriptionId, 'subscription_id');

        if($subscriptionResponse->count > $postUpdate->getPaidCount()) {
            $postUpdate->setRemainingCount($postUpdate->getTotalCount() - $subscriptionResponse->count);
            $postUpdate->setPaidCount($subscriptionResponse->count);
            $postUpdate->setNextChargeAt($subscriptionResponse->items[0]['billing_end']);
            $postUpdate->save();
        }
        return $subscriptionResponse ;
    }

    /**
     * Edit Subscription
     *
     * @param  $subscriptionId
     * @return array
     */
    public function editSubscription($subscriptionId, $attributes, $rzp)
    {
        $entity_id = $attributes['entity_id'];
        unset($attributes['entity_id']);
        try{
            $subscriptionResponse = $rzp->subscription->fetch($subscriptionId)->update($attributes);
            $subscription = $this->objectManagement->create('Razorpay\Subscription\Model\Subscriptions');
            $postUpdate = $subscription->load($subscriptionId, 'subscription_id');
            $postUpdate->setPlanEntityId($entity_id);
            $postUpdate->save();

        }catch(\Error $e){
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Fetch pending updates
     *
     * @param  $subscriptionId
     * @return array
     */
    public function pendingUpdate($subscriptionId, $rzp)
    {
        try{
            $subscriptionResponse = $rzp->subscription->fetch($subscriptionId)->pendingUpdate();
            return $subscriptionResponse;
        }catch(Error $e){
            return [];
        }
    }

    /**
     * Checking if subscription is active or not in payment setting
     *
     * @return bool
     */
    public function isSubscriptionActive()
    {
        return (bool) (int) $this->subscriptionConfig->getConfigData(SubscriptionConfig::IS_SUBSCRIPTION_ACTIVE);
    }
}