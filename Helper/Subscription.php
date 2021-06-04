<?php

namespace Razorpay\Subscription\Helper;

use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\Product;
use Razorpay\Api\Errors\Error;
use \Magento\Quote\Model\Quote;


class Subscription
{
    /**
     * @var Product
     */
    private $_product;
    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    private $_objectManagement;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct (Product $product, LoggerInterface $logger)
    {
        $this->_product = $product;
        $this->logger = $logger;
        $this->_objectManagement   = \Magento\Framework\App\ObjectManager::getInstance();
    }

    public function createSubscription($quote, $rzp){
        try{
            $planId = $this->createOrGetPlanId($quote, $rzp);
            echo $planId;
        } catch(Exception $e) {
            throwException( $e->getMessage() );
        }
    }

    /**
     * This function creates or fetch plan id
     * for creating plan need to call razorpay plan api
     * if already created then its is fetched from db
     * @param $quote
     * @param $rzp
     * @return mixed
     */
    public function createOrGetPlanId($quote, $rzp){
        try {
            $this->logger->info("-------------------------Plan creation/fetch start---------------------------");

            $planType = $product = $planName = $productId = "";

            /* @var \Magento\Quote\Model\Quote $quote */
            foreach( $quote->getItems() as $item){
                $planType = $this->getAdditionalItemOption($item);
                $productId = $item->getProduct()->getId();
                $product = $this->_product->load($item->getProduct()->getId());
                $planName = $product->getName()."_$planType";
            }

            $this->logger->info("Fetching plan id if existing for the following: Product id: $productId  product name: {$product->getName()}  Plan type: $planType ");

            //Fetching plan id if existing
            $planCollection = $this->_objectManagement->get('Razorpay\Subscription\Model\Plans')
                ->getCollection()
                ->addFieldToSelect('plan_id')
                ->addFilter('plan_name', $planName)
                ->addFilter('magento_product_id', $productId)
                ->addFilter('plan_type', $planType)
                ->getFirstItem()
                ->getData();

            if (empty($planCollection)) {
                $planData = [
                    "period" => $planType,
                    "interval" => $product->getRazorpaySubscriptionIntervalCount(),
                    "item" => [
                        "name" => $planName,
                        "amount" => (int) (number_format($product->getPrice() * 100, 0, ".", "")),
                        "currency" => $quote->getQuoteCurrencyCode(),
                        "description" => "Plan creation ". $product->getName() ." of the type $planType"
                    ],
                    "notes" => [
                        "source"    => "magento"
                    ]
                ];
                $this->logger->info("Creating new plan for the product $planName of the type $planType", $planData);
                $planResponse = $rzp->plan->create($planData);
                $this->logger->info("Razorpay plan creation response", json_decode(json_encode($planResponse),true));

                $plan = $this->_objectManagement->create('Razorpay\Subscription\Model\Plans');
                $plan->setPlanName($planName)
                    ->setPlanType($planType)
                    ->setMagentoProductId($productId)
                    ->setPlanId($planResponse->id)
                    ->save();
                return $planResponse->id;
            }
            return $planCollection["plan_id"];

        } catch(\Exception $e) {
            $this->logger->info("Exception: {$e->getMessage()}");
            throwException( $e->getMessage() );
        }
        catch(Error $e) {
            $this->logger->info("Exception: {$e->getMessage()}");
            throwException( $e->getMessage() );

        }
    }

    /**
     * This functions is used to fetch frequency from the item quotes
     * @param $item
     * @return mixed
     */
    public function getAdditionalItemOption($item){
        foreach ($item->getOptions() as $option){
            /* @var \Magento\Quote\Model\Quote\Item\Option $option */
            $optionData = json_decode($option->getValue(),true);
            return $optionData["frequency"];
        }
    }
}