<?php

namespace Razorpay\Subscription\Block;

use \Magento\Framework\Exception\LocalizedException;
use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;
use \Magento\Framework\Registry;
use \Razorpay\Subscription\Model\Subscrib;
use \Razorpay\Subscription\Model\SubscribFactory;
use \Razorpay\Subscription\Controller\Subscrib\View as ViewAction;
use Razorpay\Subscription\Helper\Subscription;
use Razorpay\Api\Api;

use \Razorpay\Subscription\Model\ResourceModel\Subscrib\Collection as SubscribCollection;
use \Razorpay\Subscription\Model\ResourceModel\Subscrib\CollectionFactory as SubscribCollectionFactory;

class View extends Template
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
    protected $_subscribFactory = null;
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
     * @param SubscribFactory $subscribFactory
     * @param SubscribCollectionFactory $subscribCollectionFactory
     * @param ResourceConnection $Resource
     * @param Subscription $subscription
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        SubscribFactory $subscribFactory,
        SubscribCollectionFactory $subscribCollectionFactory,
        \Magento\Framework\App\ResourceConnection $Resource,
        \Razorpay\Subscription\Helper\Subscription $subscription,
        array $data = []
    ) {
        $this->_subscribFactory = $subscribFactory;
        $this->_coreRegistry = $coreRegistry;
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
    public function getSubscrib()
    {
        $subscribCollection = $this->_subscribCollectionFactory->create();

        $second_table_name = $this->_resource->getTableName('catalog_product_entity_varchar');
        $third_table_name = $this->_resource->getTableName('eav_attribute');
        $fourth_table_name = $this->_resource->getTableName('catalog_product_entity');
        $fiveth_table_name = $this->_resource->getTableName('razorpay_sales_order');
        $six_table_name = $this->_resource->getTableName('sales_invoice');
        $seventh_table_name = $this->_resource->getTableName('razorpay_plans');

        $subscribCollection->getSelect()->joinLeft(array('second' => $second_table_name),
            'main_table.product_id = second.entity_id',
            array('second.value'));

        $subscribCollection->getSelect()->joinLeft(array('third' => $third_table_name),
            'third.attribute_id = second.attribute_id',
            array('third.attribute_id as attribute_id'));

        $subscribCollection->getSelect()->joinLeft(array('fourth' => $fourth_table_name),
            'main_table.product_id = fourth.entity_id',
            array('fourth.sku'));

        $subscribCollection->getSelect()->joinLeft(array('five' => $fiveth_table_name),
            'main_table.subscription_id = five.rzp_order_id',
            array('five.increment_order_id'));

        $subscribCollection->getSelect()->joinLeft(array('six' => $six_table_name),
            'six.transaction_id = five.rzp_payment_id',
            array('six.subtotal','six.grand_total','six.total_qty','six.store_currency_code','six.base_shipping_amount'));

        $subscribCollection->getSelect()->joinLeft(array('seven' => $seventh_table_name),
            'main_table.plan_entity_id = seven.entity_id',
            array('seven.plan_id','seven.plan_type','seven.plan_name'));

        $subscribCollection->getSelect()->where("third.attribute_code='name' and second.entity_id=main_table.product_id");

        $subscribCollection->addFieldToFilter('main_table.entity_id', $this->_getSubscribId());
        $singleData= $subscribCollection->getFirstItem();
        $data = $singleData->getData();
        return $data;

    }

    /**
     * Retrieves the subscrib id from the registry
     * @return int
     */
    protected function _getSubscribId()
    {
        return (int) $this->_coreRegistry->registry(
            ViewAction::REGISTRY_KEY_POST_ID
        );
    }
    public function getSubscribCancelUrl(
        Subscrib $subscrib
    ) {

        return '/razorpaysubscription/customer/CancelSubscription/s_id' . $subscrib->getSubscriptionId();

    }

    public function getCurrencySymbol($_code)
    {
        $_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $_Symbol = $_objectManager->create('Magento\Directory\Model\CurrencyFactory')->create()->load($_code);
        return $_Symbol->getCurrencySymbol();
    }

    public function getSubscriptionInvoice()
    {
        $subscribCollection = $this->_subscribCollectionFactory->create();

        $subscribCollection->addFieldToFilter('entity_id', $this->_getSubscribId());
        $singleData= $subscribCollection->getFirstItem();

        $_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $self = $_objectManager->create('Razorpay\Subscription\Model\SubscriptionPaymentMethod');
        $subscriptionInvoices = $this->_subscription->fetchSubscriptionInvoice($singleData->getSubscriptionId(), $self->rzp) ;
        return $subscriptionInvoices ;
    }

}
