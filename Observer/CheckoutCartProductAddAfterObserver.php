<?php

namespace Razorpay\Subscription\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class CheckoutCartProductAddAfterObserver implements ObserverInterface
{
    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected $_layout;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $_request;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $_serializer;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\View\LayoutInterface $layout
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\LayoutInterface $layout,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->_layout = $layout;
        $this->_storeManager = $storeManager;
        $this->_request = $request;
        $this->_serializer = $serializer;
        $this->_logger = $logger;
    }
    /**
     * Add order information into GA block to render on checkout success pages
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        /* @var \Magento\Quote\Model\Quote\Item $item */
        $item = $observer->getQuoteItem();
        $additionalOptions = array();
        if ($additionalOption = $item->getOptionByCode('additional_options')){
            $additionalOptions = $this->_serializer->unserialize($additionalOption->getValue());
        }

        $paymentOption = $this->_request->getParam('paymentOption');
        $frequency = $this->_request->getParam('frequency');
        if($paymentOption == "subscription")
        {
            $this->_logger->info("adding details of subscription. Payment Option: $paymentOption, Frequency:$frequency");
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
                'value' => $this->_serializer->serialize($additionalOptions)
            ));
        }

    }
}
