<?php

namespace Razorpay\Subscription\Block;

use \Magento\Framework\Exception\LocalizedException;
use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;
use \Magento\Framework\Registry;
use \Razorpay\Subscription\Model\Subscrib;
use \Razorpay\Subscription\Model\SubscribFactory;
use \Razorpay\Subscription\Controller\Subscrib\View as ViewAction;

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
        SubscribFactory $subscribFactory,
        SubscribCollectionFactory $subscribCollectionFactory,
        \Magento\Framework\App\ResourceConnection $Resource,
        array $data = []
    ) {
        $this->_subscribFactory = $subscribFactory;
        $this->_coreRegistry = $coreRegistry;
        $this->_subscribCollectionFactory = $subscribCollectionFactory;
        $this->_resource = $Resource;
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
      
        $subscribCollection->getSelect()->joinLeft(array('second' => $second_table_name),
                                               'main_table.product_id = second.entity_id',
                                               array('second.value'));
                                               
         $subscribCollection->getSelect()->joinLeft(array('third' => $third_table_name),
                                               'third.attribute_id = second.attribute_id',
                                               array('third.attribute_id as attribute_id'));

        $subscribCollection->getSelect()->joinLeft(array('fourth' => $fourth_table_name),
                                               'main_table.product_id = fourth.entity_id',
                                               array('fourth.sku'));
     
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
}