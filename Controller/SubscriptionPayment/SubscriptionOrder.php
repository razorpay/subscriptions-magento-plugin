<?php

namespace Razorpay\Subscription\Controller\SubscriptionPayment;

use Razorpay\Magento\Controller\BaseController;
use Razorpay\Subscription\Helper\Subscription;

class SubscriptionOrder extends BaseController
{
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
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Razorpay\Magento\Model\CheckoutFactory $checkoutFactory,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        Subscription $subscription,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct(
            $context,
            $customerSession,
            $checkoutSession,
            $config
        );

        $this->config          = $config;
        $this->cartManagement  = $cartManagement;
        $this->customerSession = $customerSession;
        $this->checkoutFactory = $checkoutFactory;
        $this->cache = $cache;
        $this->orderRepository = $orderRepository;
        $this->subscription = $subscription;
        $this->logger          = $logger;

        $this->objectManagement   = \Magento\Framework\App\ObjectManager::getInstance();
    }

    public function execute()
    {
        try{
            if (empty($_POST['email']) === true) {
                $this->logger->info("Email field is required");

                $responseContent = [
                    'message' => "Email field is required",
                    'parameters' => []
                ];

                $code = 200;
            } else {
                $payment_action = $this->config->getPaymentAction();
                $this->customerSession->setCustomerEmailAddress($_POST['email']);

                if ($payment_action === 'authorize') {
                    $payment_capture = 0;
                } else {
                    $payment_capture = 1;
                }

                $code = 400;

                $this->subscription->createSubscription($this->getQuote(), $this->rzp);

            }
        } catch (\Exception $e){
            $responseContent = [
                'message'   => $e->getMessage(),
                'parameters' => []
            ];

        }
    }
}
