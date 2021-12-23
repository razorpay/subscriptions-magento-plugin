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
     * @param $quote
     * @param $rzp
     * @return mixed
     * @throws \Exception
     */
    public function createSubscription($quote, $rzp)
    {
        try {
            /* @var \Magento\Quote\Model\Quote $quote */
            list("planId" => $planId, "id" => $id) = $this->createOrGetPlanId($quote, $rzp);
            $this->logger->info("-------------------------Creating Subscription---------------------------");

            if ($quote->getIsActive()) {
                list("product" => $product, "productId" => $productId) = $this->getProductDetailsFromQuote($quote);
                $trailDays = $product->getRazorpaySubscriptionTrial() ?? 0;

                $subscriptionData = [
                    "customer_id" => $this->getCustomerId($quote, $rzp),
                    "plan_id" => $planId,
                    "total_count" => (int)$product->getRazorpaySubscriptionBillingCycles(),
                    "quantity" => (int)$quote->getItemsQty(),
                    "customer_notify" => 0,
                    "notes" => [
                        "source" => "magento-subscription",
                        "magento_quote_id" => $quote->getId(),
                    ],
                    "source" => "magento-subscription",

                ];
                if ($trailDays) {
                    $subscriptionData["start_at"] = strtotime("+$trailDays days");
                }
                $items = $item = [];
                if ($quote->getShippingAddress()->getShippingAmount()) {
                    $item["item"] = [
                        "name" => "Shipping charges",
                        "amount" => (int)(number_format($quote->getShippingAddress()->getShippingAmount() * 100, 0, ".", "")),
                        "currency" => $quote->getQuoteCurrencyCode(),
                        "description" => "Shipping charges"
                    ];
                    array_push($items, $item);
                    $subscriptionData["addons"] = $items;
                }
                $this->logger->info("Subscription creation data", $subscriptionData);
                $subscriptionResponse = $rzp->subscription->create($subscriptionData);
                $this->logger->info("Subscription response object ", json_decode(json_encode($subscriptionResponse), true));

                $subscription = $this->objectManagement->create('Razorpay\Subscription\Model\Subscriptions');
                $subscription->setPlanEntityId($id)
                    ->setSubscriptionId($subscriptionResponse->id)
                    ->setRazorpayCustomerId($subscriptionResponse->customer_id)
                    ->setmagentoUserId($quote->getCustomerId())
                    ->setProductId($productId)
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

    /**
     * This function creates or fetch plan id
     * for creating plan need to call razorpay plan api
     * if already created then its is fetched from db
     * @param $quote
     * @param $rzp
     * @return array|mixed
     * @throws \Exception
     */
    public function createOrGetPlanId($quote, $rzp)
    {
        try {
            $this->logger->info("-------------------------Plan creation/fetch start---------------------------");
            $planType = $product = $planName = $productId = "";
            if ($quote->getIsActive()) {
                list("planType" => $planType, "productId" => $productId, "product" => $product, "planName" => $planName) = $this->getProductDetailsFromQuote($quote);

                $this->logger->info("Fetching plan id for the following: Product id: $productId  product name: {$product->getName()}  Plan type: $planType ");

                //Fetching plan id if existing
                $planCollection = $this->objectManagement->get('Razorpay\Subscription\Model\Plans')
                    ->getCollection()
                    ->addFieldToSelect('plan_id', "planId")
                    ->addFieldToSelect("entity_id", "id")
                    ->addFilter('plan_name', $planName)
                    ->addFilter('magento_product_id', $productId)
                    ->addFilter('plan_type', $planType)
                    ->addFilter("plan_interval", 1)
                    ->getFirstItem()
                    ->getData();

                if (empty($planCollection)) {
                    $planData = [
                        "period" => $planType,
                        "interval" => 1,//(int) $product->getRazorpaySubscriptionIntervalCount(),
                        "item" => [
                            "name" => $planName,
                            "amount" => (int)(number_format($product->getPrice() * 100, 0, ".", "")),
                            "currency" => $quote->getQuoteCurrencyCode(),
                            "description" => "Plan creation " . $product->getName() . " of the type $planType"
                        ],
                        "notes" => [
                            "source" => "magento-subscription"
                        ]
                    ];
                    $this->logger->info("Creating new plan for the product $planName of the type $planType", $planData);
                    // Calling razorpay plan api
                    $planResponse = $rzp->plan->create($planData);

                    $this->logger->info("Razorpay plan creation response ", json_decode(json_encode($planResponse), true));

                    $plan = $this->objectManagement->create('Razorpay\Subscription\Model\Plans');
                    $plan->setPlanName($planName)
                        ->setPlanType($planType)
                        ->setMagentoProductId($productId)
                        ->setPlanId($planResponse->id)
                        ->setPlanInterval(1)
                        ->save();

                    return [
                        "id" => $plan->getEntityId(),
                        "planId" => $plan->getPlanId()
                    ];
                }
                return $planCollection;
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
     * This functions is used to fetch frequency from the item quotes
     * @param $item
     * @return mixed
     */
    public function getAdditionalItemOption($item)
    {
        foreach ($item->getOptions() as $option) {
            /* @var \Magento\Quote\Model\Quote\Item\Option $option */
            $optionData = json_decode($option->getValue(), true);
            $planData = $this->objectManagement->get('Razorpay\Subscription\Model\Plans')
                ->getCollection()
                ->addFieldToSelect("plan_type", "type")
                ->addFilter('entity_id', $optionData["plan_id"])
                ->getFirstItem()
                ->getData();
            return $planData["type"];
        }
    }

    /**
     * This function gets the product details from quotes
     * @param $quote
     * @return array
     */
    public function getProductDetailsFromQuote($quote): array
    {
        /* @var \Magento\Quote\Model\Quote $quote */
        foreach ($quote->getItems() as $item) {
            $planType = $this->getAdditionalItemOption($item);
            $productId = $item->getProduct()->getId();
            $product = $this->product->load($item->getProduct()->getId());
            $planName = $product->getName() . "_$planType";
        }
        return [
            "planType" => $planType,
            "productId" => $productId,
            "product" => $product,
            "planName" => $planName
        ];
    }

    /**
     * This functions creates customers in razorpay
     * @param $quote
     * @param $rzp
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
     * @param $apiKey
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
     * @param $cartItems
     * @param $validateTo
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
     * @param $id
     * @param $rzp
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
     * @param $id
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
     * @param $id
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
     * @param $subscriptionId
     * @return array
     */
    public function fetchSubscriptionInvoice($subscriptionId, $rzp){

        $subscriptionResponse = $rzp->invoice->all(["subscription_id"=>$subscriptionId]);

        //update record
        $subscription = $this->objectManagement->create('Razorpay\Subscription\Model\Subscriptions');
        $postUpdate = $subscription->load($subscriptionId, 'subscription_id');

        if($subscriptionResponse->count > $postUpdate->getPaidCount()){
            $postUpdate->setRemainingCount($postUpdate->getTotalCount() - $subscriptionResponse->count);
            $postUpdate->setPaidCount($subscriptionResponse->count);
            $postUpdate->setNextChargeAt($subscriptionResponse->items[0]['billing_end']);
            $postUpdate->save();
        }
        return $subscriptionResponse ;
     }

     /**
     * Edit Subscription
     * @param $subscriptionId
     * @return array
     */
    public function editSubscription($subscriptionId, $attributes, $rzp)
    {
        $entity_id = $attributes['entity_id'];
        unset($attributes['entity_id']);
       try{
        $subscriptionResponse = $rzp->subscription->fetch($subscriptionId)->update($attributes);

        //update record
        $subscription = $this->objectManagement->create('Razorpay\Subscription\Model\Subscriptions');
        $postUpdate = $subscription->load($subscriptionId, 'subscription_id');
        $postUpdate->setPlanEntityId($entity_id);
        $postUpdate->save();

       }catch(\Exception $e){
          $this->_logger->info("Exception: {$e->getMessage()}");
          throw new \Exception( $e->getMessage() );
       }
    }

    /**
     * Fetch pending updates
     * @param $subscriptionId
     * @return array
     */
    public function pendingUpdate($subscriptionId, $rzp)
    {
        try{
            $subscriptionResponse = $rzp->subscription->fetch($subscriptionId)->pendingUpdate();
            return $subscriptionResponse;

        }catch(\Exception $e){
            $this->_logger->info("Exception: {$e->getMessage()}");
            return [];
         }
    }

    /**
     * Checking if subscription is active or not in payment setting
     * @return bool
     */
    public function isSubscriptionActive()
    {
        return (bool) (int) $this->subscriptionConfig->getConfigData(SubscriptionConfig::IS_SUBSCRIPTION_ACTIVE);
    }
}
