<?php

namespace Razorpay\Subscription\Controller\Subscription;

use Magento\Framework\Controller\ResultFactory;
use Razorpay\Magento\Controller\BaseController;
use Razorpay\Magento\Model\PaymentMethod;
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
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Razorpay\Magento\Model\CheckoutFactory $checkoutFactory,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Razorpay\Subscription\Helper\Subscription $subscription,
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
        $this->cache           = $cache;
        $this->orderRepository = $orderRepository;
        $this->subscription    = $subscription;
        $this->logger          = $logger;

        $this->objectManagement   = \Magento\Framework\App\ObjectManager::getInstance();
    }

    public function execute()
    {
        try{
            $receiptId = $this->getQuote()->getId();

            if(empty($_POST['error']) === false)
            {
                $this->messageManager->addError(__('Payment Failed'));
                return $this->_redirect('checkout/cart');
            }

            if (isset($_POST['order_check']))
            {
                if (empty($this->cache->load("quote_processing_".$receiptId)) === false)
                {
                    $responseContent = [
                        'success'   => true,
                        'order_id'  => false,
                        'parameters' => []
                    ];

                    # fetch the related sales order and verify the payment ID with rzp payment id
                    # To avoid duplicate order entry for same quote
                    $collection = $this->_objectManager->get('Magento\Sales\Model\Order')
                        ->getCollection()
                        ->addFieldToSelect('entity_id')
                        ->addFilter('quote_id', $receiptId)
                        ->getFirstItem();

                    $salesOrder = $collection->getData();

                    if (empty($salesOrder['entity_id']) === false)
                    {
                        $this->logger->info("Razorpay inside order already processed with webhook quoteID:" . $receiptId
                            ." and OrderID:".$salesOrder['entity_id']);

                        $this->checkoutSession
                            ->setLastQuoteId($this->getQuote()->getId())
                            ->setLastSuccessQuoteId($this->getQuote()->getId())
                            ->clearHelperData();

                        $order = $this->orderRepository->get($salesOrder['entity_id']);

                        if ($order) {
                            $this->checkoutSession->setLastOrderId($order->getId())
                                ->setLastRealOrderId($order->getIncrementId())
                                ->setLastOrderStatus($order->getStatus());
                        }

                        $responseContent['order_id'] = true;
                    }
                }
                else
                {
                    if(empty($receiptId) === false)
                    {
                        //set the chache to stop webhook processing
                        $this->cache->save("started", "quote_Front_processing_$receiptId", ["razorpay"], 30);

                        $this->logger->info("Razorpay front-end order processing started quoteID:" . $receiptId);

                        $responseContent = [
                            'success'   => false,
                            'parameters' => []
                        ];
                    }
                    else
                    {
                        $this->logger->info("Razorpay order already processed with quoteID:" . $this->checkoutSession
                                ->getLastQuoteId());

                        $responseContent = [
                            'success'    => true,
                            'order_id'   => true,
                            'parameters' => []
                        ];

                    }
                }

                $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
                $response->setData($responseContent);
                $response->setHttpResponseCode(200);

                return $response;
            }

            if(isset($_POST['razorpay_payment_id']))
            {
                $this->getQuote()->getPayment()->setMethod(PaymentMethod::METHOD_CODE);

                try
                {
                    if(!$this->customerSession->isLoggedIn()) {
                        $this->getQuote()->setCheckoutMethod($this->cartManagement::METHOD_GUEST);
                        $this->getQuote()->setCustomerEmail($this->customerSession->getCustomerEmailAddress());
                    }
                    $this->cartManagement->placeOrder($this->getQuote()->getId());
                    return $this->_redirect('checkout/onepage/success');
                }
                catch(\Exception $e)
                {
                    $this->messageManager->addError(__($e->getMessage()));
                    return $this->_redirect('checkout/cart');
                }
            }
            else
            {
                if (empty($_POST['email']) === true) {
                    $this->logger->info("Email field is required");

                    $responseContent = [
                        'message' => "Email field is required",
                        'parameters' => []
                    ];

                    $code = 200;
                } else {
                    $paymentAction = $this->config->getPaymentAction();
                    $this->customerSession->setCustomerEmailAddress($_POST['email']);

                    $responseContent = [
                        'message' => 'Unable to create your order. Please contact support.',
                        'parameters' => []
                    ];

                    if ($paymentAction === 'authorize') {
                        $paymentCapture = 0;
                    } else {
                        $paymentCapture = 1;
                    }

                    $code = 400;

                    $subscription = $this->subscription->createSubscription($this->getQuote(), $this->rzp);

                    if ($subscription && $subscription->id) {
                        $merchantPreferences = $this->subscription->getMerchantPreferences($this->config->getKeyId());
                        $responseContent = [
                            'success' => true,
                            'rzp_order' => $subscription->id,
                            'order_id' => $this->getQuote()->getId(),
                            'amount' => (int)(number_format($this->getQuote()->getGrandTotal() * 100, 0, ".", "")),
                            'quote_currency' => $this->getQuote()->getQuoteCurrencyCode(),
                            'quote_amount' => number_format($this->getQuote()->getGrandTotal(), 2, ".", ""),
                            'maze_version' => $this->_objectManager->get('Magento\Framework\App\ProductMetadataInterface')->getVersion(),
                            'magento_pg_version' => $this->_objectManager->get('Magento\Framework\Module\ModuleList')->getOne('Razorpay_Magento')['setup_version'],
                            'module_version' => $this->_objectManager->get('Magento\Framework\Module\ModuleList')->getOne('Razorpay_Subscription')['setup_version'],
                            'is_hosted' => $merchantPreferences['is_hosted'],
                            'image' => $merchantPreferences['image'],
                            'embedded_url' => $merchantPreferences['embedded_url'],
                        ];

                        $code = 200;

                        $this->checkoutSession->setRazorpayOrderID($subscription->id);
                        $this->checkoutSession->setRazorpayOrderAmount((int) number_format($this->getQuote()->getGrandTotal() * 100, 0, ".", ""));

                        //save to razorpay orderLink
                        $orderLinkCollection = $this->_objectManager->get('Razorpay\Magento\Model\OrderLink')
                            ->getCollection()
                            ->addFilter('quote_id', $receiptId)
                            ->getFirstItem();

                        $orderLinkData = $orderLinkCollection->getData();

                        if (empty($orderLinkData['entity_id']) === false) {
                            $orderLinkCollection->setRzpOrderId($subscription->id)
                                ->save();
                        } else {
                            $orderLink = $this->_objectManager->create('Razorpay\Magento\Model\OrderLink');
                            $orderLink->setQuoteId($receiptId)
                                ->setRzpOrderId($subscription->id)
                                ->save();
                        }
                    }
                }
            }
        } catch (\Exception $e){
            $this->logger->info("Exception subscription controller: {$e->getMessage()}");

            $responseContent = [
                'message'   => $e->getMessage(),
                'parameters' => []
            ];
        }

        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response->setData($responseContent);
        $response->setHttpResponseCode($code);
        return $response;
    }
}