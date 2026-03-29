<?php

namespace Razorpay\Subscription\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class CheckoutCartProductAddAfterObserver implements ObserverInterface
{
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    private $objectManagement;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface       $storeManager
     * @param \Magento\Framework\App\RequestInterface          $request
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Psr\Log\LoggerInterface                         $logger
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->objectManagement = \Magento\Framework\App\ObjectManager::getInstance();

    }

    /**
     * @param  EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        /* @var \Magento\Quote\Model\Quote\Item $item */
        $item = $observer->getQuoteItem();
        $additionalOptions = array();
        if ($additionalOption = $item->getOptionByCode('additional_options')) {
            $additionalOptions = $this->serializer->unserialize($additionalOption->getValue());
        }

        $paymentOption = $this->request->getParam('paymentOption');
        $planId = $this->request->getParam('plan_id');

        $this->logger->info($planId);

        if($paymentOption == "subscription" ) {
            $planData = $this->objectManagement->get('Razorpay\Subscription\Model\Plans')
                ->getCollection()
                ->addFieldToSelect("plan_bill_amount", "price")
                ->addFieldToSelect("plan_type", "type")
                ->addFilter('entity_id', $planId)
                ->addFilter('plan_status', 1)
                ->getFirstItem()
                ->getData();

            if(!empty($planData)) {
                $item->setCustomPrice($planData["price"]);
                $item->setOriginalCustomPrice($planData["price"]);
                $item->getProduct()->setIsSuperMode(true);
            }

            $this->logger->info("adding details of subscription to quotes. Payment Option: $paymentOption, Plan id:$planId");
            $additionalOptions[] = [
                'label' => "Subscription type",
                'value' => ucfirst($planData["type"])
            ];


            if(count($additionalOptions) > 0) {
                $item->addOption(
                    array(
                    'product_id' => $item->getProductId(),
                    'code' => 'additional_options',
                    'value' => $this->serializer->serialize($additionalOptions)
                    )
                );
            }
        }

    }
}
