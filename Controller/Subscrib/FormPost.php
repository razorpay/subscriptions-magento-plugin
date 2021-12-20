<?php

namespace Razorpay\Subscription\Controller\Subscrib;

use Razorpay\Magento\Controller\BaseController;
use Razorpay\Subscription\Helper\Subscription;
use \Razorpay\Subscription\Model\ResourceModel\Subscrib\CollectionFactory as SubscribCollectionFactory;
use \Razorpay\Subscription\Model\ResourceModel\Plans\CollectionFactory as PlanCollectionFactory;

class FormPost extends BaseController
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
     * SubscribCollectionFactory
     * @var null|SubscribFactory
     */
    protected $_subscribCollectionFactory = null;
    
    /**
     * PlanCollectionFactory
     * @var null|PlanFactory
     */
    protected $_planCollectionFactory = null;

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
        PlanCollectionFactory $planCollectionFactory,
        SubscribCollectionFactory $subscribCollectionFactory,
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
        $this->_planCollectionFactory = $planCollectionFactory;
        $this->_subscribCollectionFactory = $subscribCollectionFactory;
    }

    public function execute()
    {
        $postValues = $this->getRequest()->getPostValue();
        $subscriptionId = $this->getRequest()->getParam('id');

        unset($postValues['id']);
        unset($postValues['form_key']); 
 
        $plan = $this->fetchPlan($postValues['plan_id']);
      
        try {
            if (!$this->customerSession->isLoggedIn()) {
                return $this->_redirect('customer/account/login');
            }

            if($this->planValidate($subscriptionId, $plan['magento_product_id'])){
               $postValues['entity_id'] = $plan['entity_id'];
               $postValues['schedule_change_at'] = 'cycle_end';
               $this->subscription->editSubscription($subscriptionId, $postValues, $this->rzp);
               $this->messageManager->addSuccess(__("Subscription updated successfully!"));
            }
           
            return $this->_redirect('razorpaysubscription/customer/index/');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
           return $this->_redirect('razorpaysubscription/customer/index/');
        }
    }

    protected function planValidate($subscriptionId, $productId)
    {
        $response = false;

        $subscribCollection = $this->_subscribCollectionFactory->create();
        $subscribCollection->addFieldToFilter('subscription_id', $subscriptionId);
        $subscribCollection->addFieldToFilter('magento_user_id', $this->customerSession->getCustomer()->getId());
        $singleData= $subscribCollection->getFirstItem();
        $data = $singleData->getData();
        
        if(count($data)==0){
          return $response;
        }

        if($data['product_id']==$productId){
            $response = true;
        }
        
        return $response ;
    }
    
    protected function fetchPlan($planId)
    {
        $planCollection = $this->_planCollectionFactory->create();
        $planCollection->addFieldToFilter('plan_id', $planId);
        $singleData= $planCollection->getFirstItem();
        $data = $singleData->getData();

        return $data;
    }
}