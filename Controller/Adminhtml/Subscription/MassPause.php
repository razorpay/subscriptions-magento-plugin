<?php

namespace Razorpay\Subscription\Controller\Adminhtml\Subscription;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Razorpay\Magento\Controller\BaseController;
use Razorpay\Subscription\Helper\Subscription;
use Razorpay\Subscription\Model\ResourceModel\Subscrib\CollectionFactory;

class MassPause extends BaseController
{
    /**
     * @var Magento\Backend\Helper\Data
     */
    private $backendHelper;
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

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
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context       $context,
        \Magento\Customer\Model\Session             $customerSession,
        \Magento\Checkout\Model\Session             $checkoutSession,
        \Razorpay\Magento\Model\Config              $config,
        Filter                                      $filter,
        CollectionFactory                           $collectionFactory,
        \Magento\Backend\Helper\Data                $backendHelper,
        \Razorpay\Subscription\Helper\Subscription  $subscription,
        \Magento\Framework\Message\ManagerInterface $messageManager
    )
    {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        parent::__construct(
            $context,
            $customerSession,
            $checkoutSession,
            $config
        );
        $this->backendHelper = $backendHelper;
        $this->messageManager = $messageManager;
        $this->config = $config;
        $this->customerSession = $customerSession;
        $this->subscription = $subscription;
    }

    /**
     * Execute action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $collectionSize = $collection->getSize();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $adminSession = $objectManager->get('Magento\Backend\Model\Auth\Session');
        if ($adminSession->isLoggedIn()) {
            try {
                $updateBy = 'admin';
                foreach ($collection as $item) {
                    $id = $item['subscription_id'];
                    $this->subscription->pauseSubscription($id, $this->rzp, $updateBy);
                }

                $this->messageManager->addSuccess(__('A total of %1 subscription(s) have been paused.', $collectionSize));
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath('subscribed/index/index');
            } catch (\Exception $e) {
                $this->messageManager->addError(__($e->getMessage()));
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath('subscribed/index/index');
            }

        } else {
            return $this->backendHelper->getHomePageUrl();
        }
    }
}
