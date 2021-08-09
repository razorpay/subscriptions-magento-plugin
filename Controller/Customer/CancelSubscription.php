<?php

namespace Razorpay\Subscription\Controller\Customer;

//use \Magento\Framework\App\Action\Action;
use Razorpay\Magento\Controller\BaseController;
use Razorpay\Subscription\Helper\Subscription;
//use \Razorpay\Subscription\Model\ResourceModel\Subscrib\Collection as SubscribCollection;
//use \Razorpay\Subscription\Model\ResourceModel\Subscrib\CollectionFactory as SubscribCollectionFactory;
//use \Razorpay\Subscription\Model\Subscrib;


class CancelSubscription extends BaseController   {

    // protected $_subscribCollectionFactory = null;
    // protected $_resource;
/**
     * @var \Razorpay\Magento\Model\Config
     */
    private $config;

    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    private $cache;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    private $objectManagement;
    /**
     * @var Subscription
     */
    private $subscription;
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Razorpay\Model\Config\Payment $razorpayConfig
     * @param \Magento\Framework\App\CacheInterface $cache
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
       
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Razorpay\Magento\Model\Config $config,
       // \Magento\Quote\Api\CartManagementInterface $cartManagement,
       // \Razorpay\Magento\Model\CheckoutFactory $checkoutFactory,
        \Magento\Framework\App\CacheInterface $cache,
       // \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Razorpay\Subscription\Helper\Subscription $subscription,
        //SubscribCollectionFactory $subscribCollectionFactory,
        //\Magento\Framework\App\ResourceConnection $Resource,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
       // $this->_subscribCollectionFactory = $subscribCollectionFactory;
       // $this->_resource = $Resource;
        parent::__construct(
            $context,
            $customerSession,
            $checkoutSession,
            $config
            
        );
        $this->messageManager = $messageManager;
        $this->config          = $config;
        //$this->cartManagement  = $cartManagement;
        $this->customerSession = $customerSession;
        //$this->checkoutFactory = $checkoutFactory;
        $this->cache           = $cache;
        //$this->orderRepository = $orderRepository;
        $this->subscription    = $subscription;
        $this->logger          = $logger;

       
    }
    public function execute()
    {

        $id = $this->getRequest()->getParam('s_id');
        $oid = $this->getRequest()->getParam('oid');
        try
        {
            if(!$this->customerSession->isLoggedIn()) {
                return $this->_redirect('customer/account/login');
            }
           
            $this->subscription->cancelSubscription($id, $this->rzp);
            $this->messageManager->addSuccess(__("Subscription is cancelled successfully!"));
            return $this->_redirect('razorpaysubscription/subscrib/view/id/'.$oid);
            
        }
        catch(\Exception $e)
        {
            $this->messageManager->addError(__($e->getMessage()));
            return $this->_redirect('razorpaysubscription/subscrib/view/id/'.$oid);
        }
    
        
        //  if($subscriptionResponse->status=="cancelled"){
        //  $this->messageManager->addSuccess( __('Cancelled  Successfully !') );
        //  }
        //  else{
        //      echo "Not cancelled";
        //  }
        
// $cancel = $this->_subscribCollectionFactory->create();
// $cancel->load($subscriptionResponse->id);
// $cancel->setData('status',$subscriptionResponse->status);
// $cancel->save();


// if($subscriptionResponse->status=="cancelled"){
//     echo $subscriptionResponse->id;
//     // $subscription = $this->objectManagement->create('\Razorpay\Subscription\Model\Subscrib');
//     //    //print_r($subscriptionResponse->plan_id);die;
//     //    $subscription->addFieldToFilter('subscription_id', $id);
//     //    $subscription->setData('status',$subscriptionResponse->status);
//     //    $subscription->save();die;
// }

      // exit;
    }

}
?>