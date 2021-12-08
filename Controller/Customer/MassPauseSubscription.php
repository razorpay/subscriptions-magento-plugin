<?php

namespace Razorpay\Subscription\Controller\Customer;

use Razorpay\Magento\Controller\BaseController;
use Razorpay\Subscription\Helper\Subscription;

class MassPauseSubscription extends BaseController
{
    /**
     * @var \Razorpay\Magento\Model\Config
     */
    private $config;

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    private $cache;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var Subscription
     */
    private $subscription;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Razorpay\Model\Config\Payment $razorpayConfig
     * @param \Magento\Framework\App\CacheInterface $cache
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(

        \Magento\Framework\App\Action\Context       $context,
        \Magento\Customer\Model\Session             $customerSession,
        \Magento\Checkout\Model\Session             $checkoutSession,
        \Razorpay\Magento\Model\Config              $config,
        \Magento\Framework\App\CacheInterface       $cache,
        \Razorpay\Subscription\Helper\Subscription  $subscription,
        \Psr\Log\LoggerInterface                    $logger,
        \Magento\Framework\Message\ManagerInterface $messageManager
    )
    {
        parent::__construct(
            $context,
            $customerSession,
            $checkoutSession,
            $config

        );
        $this->messageManager = $messageManager;
        $this->config = $config;
        $this->customerSession = $customerSession;
        $this->cache = $cache;
        $this->subscription = $subscription;
        $this->logger = $logger;
    }

    public function execute()
    {
        $orders = $this->getRequest()->getParam('orders');

        try {
            if (!$this->customerSession->isLoggedIn()) {
                return $this->_redirect('customer/account/login');
            }
            
            foreach ($orders as $order) {
               $subscriptionData =  $this->getSubscription($order);
               if($subscriptionData['status'] != "cancelled"){
                  $this->subscription->pauseSubscription($subscriptionData['subscription_id'], $this->rzp);
                }
            }

            $this->messageManager->addSuccess(__("Subscription is paused successfully!"));
            return $this->_redirect('razorpaysubscription/customer/index/');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
           return $this->_redirect('razorpaysubscription/customer/index/');
        }
    }

    public function getSubscription($id)
    {
        $_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $subscribCollection = $_objectManager->create('Razorpay\Subscription\Model\ResourceModel\Subscrib\Collection');
        
        $subscribCollection->addFieldToFilter('entity_id', $id);
        $singleData = $subscribCollection->getFirstItem();
        return $singleData->getData();
    }
}