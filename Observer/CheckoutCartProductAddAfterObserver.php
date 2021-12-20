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

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->request = $request;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        /* @var \Magento\Quote\Model\Quote\Item $item */
        $item = $observer->getQuoteItem();
        $additionalOptions = array();
        if ($additionalOption = $item->getOptionByCode('additional_options')){
            $additionalOptions = $this->serializer->unserialize($additionalOption->getValue());
        }

        $paymentOption = $this->request->getParam('paymentOption');
        $frequency = $this->request->getParam('frequency');
        $this->logger->info("here");
//        $this->logger->info($this->request->getBodyParams());
//        $this->logger->info($this->request->getPost());
        $this->logger->info($paymentOption);
        $this->logger->info($frequency);
        if($paymentOption == "subscription")
        {
            $this->logger->info("adding details of subscription to quotes. Payment Option: $paymentOption, Frequency:$frequency");
            $additionalOptions[] = [
                'label' => "Subscription type",
                'value' => ucfirst($frequency)
            ];
        }

        if(count($additionalOptions) > 0)
        {
            $item->addOption(array(
                'product_id' => $item->getProductId(),
                'code' => 'additional_options',
                'value' => $this->serializer->serialize($additionalOptions)
            ));
        }

    }
}
