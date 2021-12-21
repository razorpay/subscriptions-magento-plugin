<?php

namespace Razorpay\Subscription\Controller\Customer;

use Razorpay\Magento\Controller\BaseController;
use Razorpay\Subscription\Helper\Subscription;

class ResumeSubscription extends BaseController
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
        $id = $this->getRequest()->getParam('s_id');
        $oid = $this->getRequest()->getParam('oid');
        try {
            if (!$this->customerSession->isLoggedIn()) {
                return $this->_redirect('customer/account/login');
            }

            $updateBy = 'customer';
            $this->subscription->resumeSubscription($id, $this->rzp, $updateBy);
            $this->messageManager->addSuccess(__("Subscription is resumed successfully!"));
            return $this->_redirect('razorpaysubscription/subscrib/view/id/' . $oid);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
            return $this->_redirect('razorpaysubscription/subscrib/view/id/' . $oid);
        }
    }
}
