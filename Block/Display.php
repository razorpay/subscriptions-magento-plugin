<?php
namespace Razorpay\Subscription\Block;

use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;
use \Razorpay\Subscription\Model\ResourceModel\Subscrib\Collection as SubscribCollection;
use \Razorpay\Subscription\Model\ResourceModel\Subscrib\CollectionFactory as SubscribCollectionFactory;
use \Razorpay\Subscription\Model\Subscrib;


class Display extends Template
{
/**
     * CollectionFactory
     * @var null|CollectionFactory
     */
    protected $_subscribCollectionFactory = null;

    protected $_resource;
    
    /**
     * Constructor
     *
     * @param Context $context
     * @param SubscribCollectionFactory $subscribCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        SubscribCollectionFactory $subscribCollectionFactory,
        \Magento\Framework\App\ResourceConnection $Resource
       
    ) {
        $this->_subscribCollectionFactory = $subscribCollectionFactory;
        $this->_resource = $Resource;
        parent::__construct($context);
    }

    /**
     * @return Subscrib[]
     */

    protected function filterOrder()
    {
        $this->razorpay_subscriptions = "main_table";
        $this->catalog_product_entity_table = $this->getTable("catalog_product_entity");
        $this->getSelect()
            ->join(array('payment' =>$this->catalog_product_entity_table), $this->razorpay_subscriptions . '.product_id= payment.entity_id',
            
        );
        $this->catalog_product_entity_varchar_table = $this->getTable("catalog_product_entity");
        $this->getSelect()
            ->join(array('pid' =>$this->catalog_product_entity_varchar_table), $this->catalog_product_entity_table . '.entity_id= pid.entity_id',
            
        );
        //$this->getSelect()->where("payment_method=".$payment_method);
    }
    public function getSubscrib() 
    {
        /** @var SubscribCollection $subscribCollection */
        // $subscribCollection = $this->_subscribCollectionFactory->create();
        // $subscribCollection->addFieldToSelect('*')->load();
        // return $subscribCollection->getItems();
       // $subscribCollection = $this->_subscribCollectionFactory->create();
        

    //    select value, r.* from razorpay_subscriptions r left join catalog_product_entity_varchar ON r.product_id=catalog_product_entity_varchar.entity_id left join eav_attribute on eav_attribute.attribute_id = catalog_product_entity_varchar.attribute_id where eav_attribute.attribute_code='name' and catalog_product_entity_varchar.entity_id =r.product_id

        $collection = $this->_subscribCollectionFactory->create();
        $second_table_name = $this->_resource->getTableName('catalog_product_entity_varchar');
        $third_table_name = $this->_resource->getTableName('eav_attribute');
      //  $collection->getCollection()->addFieldToSelect('value');
        $collection->getSelect()->joinLeft(array('second' => $second_table_name),
                                               'main_table.product_id = second.entity_id',
                                               array('second.value as productname'));
     $collection->getSelect()->joinLeft(array('third' => $third_table_name),
                                               'third.attribute_id = second.attribute_id',
                                               array('third.attribute_id as attribute_id'));

    $collection->getSelect()->where("third.attribute_code='name' and second.entity_id=main_table.product_id");

                 //echo $collection->getSelect()->__toString();
                                              // exit;
                                               return $collection; 
        


    //     $this->getSelect()->join(
    //         ['productTable'=>$this->getTable('catalog_product_entity')],
    //         'main_table.product_id = productTable.entity_id','*');
    //   $this->getSelect()->join(
    //         ['catalogTable'=>$this->getTable('catalog_product_entity_varchar')],
    //         'productTable.entity_id = catalogTable.entity_id', '*');

    


    }

    /**
     * For a given subscrib, returns its url
     * @param Subscrib $subscrib
     * @return string
     */
    public function getSubscribUrl(
        Subscrib $subscrib
    ) {
        return '/razorpaysubscription/customer/view/id/' . $subscrib->getId();
    }


	// protected $_postFactory;
	// public function __construct(
	// 	\Magento\Framework\View\Element\Template\Context $context,
	// 	\Razorpay\Subscription\Model\SubscribFactory $postFactory,
	// 	array $data = []
	// )
	// {
	// 	$this->_postFactory = $postFactory;
	// 	parent::__construct($context, $data);
	// }

	// public function sayHello()
	// {
	// 	return __('Hello World');
	// }

	// public function getPostCollection(){
	// 	// //echo "getPostCollection";
	// 	// $post = $this->_postFactory->create();
	// 	//  //$post->getCollection();
	// 	// return $post->getData();
		
	// 	$collection = $this->_postFactory->create();
    //     $collection->addAttributeToSelect('*');
    //     $collection->setPageSize(3); // fetching only 3 products
    //     return $collection;
	// }
}