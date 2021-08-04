<?php

namespace Razorpay\Subscription\Controller\Customer;


use Razorpay\Magento\Controller\BaseController;
use Razorpay\Subscription\Helper\Subscription;



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
      \Magento\Framework\App\CacheInterface $cache,
        \Razorpay\Subscription\Helper\Subscription $subscription,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
       
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
    
        
        
    }

}
?>