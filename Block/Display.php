<?php
namespace Razorpay\Subscription\Block;

use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;
use \Razorpay\Subscription\Model\ResourceModel\Subscrib\Collection as SubscribCollection;
use \Razorpay\Subscription\Model\ResourceModel\Subscrib\CollectionFactory as SubscribCollectionFactory;
use \Razorpay\Subscription\Model\Subscrib;

class Display extends Template
{
    protected $customerSession;
    protected $subscribCollectionFactory = null;
    protected $moduleReader;

    protected $_resource;
    private  $urlInterface;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     * @param Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param SubscribCollectionFactory $subscribCollectionFactory
     * @param \Magento\Framework\App\ResourceConnection $Resource
     * @param \Magento\Framework\Module\Dir\Reader $moduleReader
     * @param array $data
     */
    public function __construct(
        Context                                   $context,
        \Magento\Customer\Model\Session           $customerSession,
        SubscribCollectionFactory                 $subscribCollectionFactory,
        \Magento\Framework\App\ResourceConnection $Resource,

        \Magento\Framework\Module\Dir\Reader $moduleReader,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\UrlInterface $urlInterface,
        array $data = []
       
    ) {
        $this->_subscribCollectionFactory = $subscribCollectionFactory;
        $this->_resource = $Resource;
        parent::__construct($context, $data);
        $this->subscribCollectionFactory = $subscribCollectionFactory;
        $this->resource = $Resource;
        $this->moduleReader = $moduleReader;
        $this->customerSession = $customerSession;
        $this->logger          = $logger;
        $this->urlInterface = $urlInterface;
    }

    /**
     * @return Subscrib[]
     */
    protected function filterOrder()
    {
        $this->razorpay_subscriptions = "main_table";
        $this->catalog_product_entity_table = $this->getTable("catalog_product_entity");
        $this->getSelect()
            ->join(array('payment' => $this->catalog_product_entity_table), $this->razorpay_subscriptions . '.product_id= payment.entity_id');

        $this->catalog_product_entity_varchar_table = $this->getTable("catalog_product_entity");
        $this->getSelect()
            ->join(array('pid' => $this->catalog_product_entity_varchar_table), $this->catalog_product_entity_table . '.entity_id= pid.entity_id',

        );
    }

    public function getSubscribs()
    {
        // get param values
        $page = ($this->getRequest()->getParam('p')) ? $this->getRequest()->getParam('p') : 1;
        $pageSize = ($this->getRequest()->getParam('limit')) ? $this->getRequest()->getParam('limit') : 10; // set minimum records

        $customerId = $this->customerSession->getCustomer()->getId();
        $subscribCollection = $this->subscribCollectionFactory->create();

        $subscribCollection = $this->_subscribCollectionFactory->create();
   
        $second_table_name = $this->_resource->getTableName('catalog_product_entity_varchar');
        $third_table_name = $this->_resource->getTableName('eav_attribute');
      
        $subscribCollection->getSelect()->joinLeft(array('second' => $second_table_name),
                                               'main_table.product_id = second.entity_id',
                                               array('second.value'));
                                               
        $subscribCollection->getSelect()->joinLeft(array('third' => $third_table_name),
                                                'third.attribute_id = second.attribute_id',
                                                array('third.attribute_id as attribute_id'));
        
        $subscribCollection->getSelect()->where("third.attribute_code='name' and second.entity_id=main_table.product_id");
        $subscribCollection->getSelect()->where("main_table.magento_user_id=".$customerId);
        
        $subscribCollection->setPageSize($pageSize);
        $subscribCollection->setCurPage($page);   

        return $subscribCollection;
  
    }

    /**
     * For a given subscrib, returns its url
     * @param Subscrib $subscrib
     * @return string
     */

    public function getSubscribUrl(
        Subscrib $subscrib
    ) {
        
        return $this->urlInterface->getUrl() ."razorpaysubscription/subscrib/view/id/{$subscrib->getId()}";
        
    }

    /**
     * @inheritDoc
     */
    protected function _prepareLayout()
    {
        $subscribCollection = $this->_subscribCollectionFactory->create();

        try{
        parent::_prepareLayout();
        if ($this->getSubscribs()) {
            $pager = $this->getLayout()->createBlock(
                \Magento\Theme\Block\Html\Pager::class,
                'razorpaysubscription.customer.index'
            )->setCollection(
                $this->getSubscribs() 
            );
            $this->setChild('pager', $pager);
            $this->getSubscribs()->load();
        }
        return $this;
       }catch(\Exception $e){
        $this->logger->info("Exception subscription paging: {$e->getMessage()}");
      }
    }
    
    /**
     * Get Pager child block output
     *
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    /**
     * For a given subscrib, returns its url
     * @param Subscrib $subscrib
     * @return string
     */
    public function editSubscribUrl(
        Subscrib $subscrib
    ) {
        
        return $this->urlInterface->getUrl() ."razorpaysubscription/subscrib/edit/id/{$subscrib->getSubscriptionId()}";
        
    }
	
}
