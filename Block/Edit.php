<?php

namespace Razorpay\Subscription\Block;

use \Magento\Framework\Exception\LocalizedException;
use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;
use \Magento\Framework\Registry;
use \Razorpay\Subscription\Model\Subscrib;
use \Razorpay\Subscription\Model\SubscribFactory;
use \Razorpay\Subscription\Controller\Subscrib\Edit as EditAction;

use \Razorpay\Subscription\Model\ResourceModel\Subscrib\CollectionFactory as SubscribCollectionFactory;
use \Razorpay\Subscription\Model\ResourceModel\Plans\CollectionFactory as PlanCollectionFactory;

class Edit extends Template
{
    /**
     * Core registry
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * Subscrib
     * @var null|Subscrib
     */
    protected $_subscrib = null;

    /**
     * SubscribCollectionFactory
     * @var null|SubscribFactory
     */
    protected $_planCollectionFactory = null;

    protected $_subscribCollectionFactory = null;

    /**
     * @var Subscription
     */
    private $_subscription;
   
    protected $_resource;
    /**
     * Constructor
     * @param Context $context
     * @param Registry $coreRegistry
     * @param SubscribFactory $SubscribCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PlanCollectionFactory $planCollectionFactory,
        SubscribCollectionFactory $subscribCollectionFactory,
        \Magento\Framework\App\ResourceConnection $Resource,
        \Razorpay\Subscription\Helper\Subscription $subscription,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_planCollectionFactory = $planCollectionFactory;
        $this->_subscribCollectionFactory = $subscribCollectionFactory;
        $this->_resource = $Resource;
        $this->_subscription = $subscription;
        parent::__construct($context, $data);
    }

    /**
     * Lazy loads the requested subscrib
     * @return Subscrib
     * @throws LocalizedException
     */
    public function getCurrentPlan()
    {
        $planArr = $this->getSubscription();

        $planCollection = $this->_planCollectionFactory->create();
        $second_table_name = $this->_resource->getTableName('razorpay_subscriptions');

        $planCollection->getSelect()->joinLeft(array('second' => $second_table_name),
                                               'main_table.entity_id = second.plan_entity_id');
        
        $planCollection->addFieldToFilter('main_table.entity_id', $planArr['plan_entity_id']);
        $planCollection->addFieldToFilter('second.subscription_id',$this->getSubscriptionId());
        $singleData= $planCollection->getFirstItem();
        $data = $singleData->getData();
        return $data; 
    }

    /**
     * Lazy loads the requested subscrib
     * @return Subscrib
     * @throws LocalizedException
     */
    public function getPlans($productId)
    {
        $planCollection = $this->_planCollectionFactory->create();
        $planCollection->addFieldToFilter('magento_product_id', $productId);
        $data = $planCollection->getData();
        return $data; 

    }

    /**
     * Retrieves the entity id from the registry
     * @return int
     */
    public function getSubscriptionId()
    {
        return (string) $this->_coreRegistry->registry(
            EditAction::REGISTRY_KEY_POST_ID
        );
    }

    /**
     * Retrieves the subscription Id
     * @return string
     */
    public function getSubscription()
    {
        $subscribCollection = $this->_subscribCollectionFactory->create();
        $subscribCollection->addFieldToFilter('subscription_id', $this->getSubscriptionId());
        $singleData= $subscribCollection->getFirstItem();
        $data = $singleData->getData();
        return $data;
    }

    /**
     * Return the Url for saving.
     *
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->_urlBuilder->getUrl(
            'razorpaysubscription/subscrib/formPost',
            ['_secure' => true, 'id' => $this->getSubscriptionId()]
        );
    }

    public function pendingUpdate()
    {
        $_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $self = $_objectManager->create('Razorpay\Subscription\Model\SubscriptionPaymentMethod');
        $subscriptionInvoices = $this->_subscription->pendingUpdate($this->getSubscriptionId(), $self->rzp) ;
        return $subscriptionInvoices ;   
    }
}
