<?php
namespace Razorpay\Subscription\Model\Source;
 
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;
 
class ProductList implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;
 
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }
 
    public function toOptionArray()
    {
        $options[] = ['label' => '-- Please Select --', 'value' => ''];
        $collection = $this->collectionFactory->create()
            ->addAttributeToSelect('*');
            ///->addAttributeToFilter('is_active', '1');
 
        foreach ($collection as $category) {
            $options[] = [
                'label' => $category->getName(),
                'value' => $category->getId(),
            ];
        }
 
        return $options;
    }
}


// class ProductList implements \Magento\Framework\Data\OptionSourceInterface
// {
//     /**
//      * @var \Razorpay\Subscription\Model\Plans
//      */
//     protected $_productCollectionFactory;
 
//     /**
//      * Constructor
//      *
//      * @param \Razorpay\Subscription\Model\Plans $plan
//      */
//     public function __construct( \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory, array $data = [] )
//     {
//         $this->_productCollectionFactory = $productCollectionFactory;    
//         parent::__construct($data);
//     }
 
//     /**
//      * Get options
//      *
//      * @return array
//      */
//     public function toOptionArray()
//     {
//         $options[] = ['label' => '', 'value' => ''];
//         // $planCollection = $this->_plan->getCollection()
//         $collection = $this->_productCollectionFactory->create();
//         $collection->addFieldToSelect('entity_id')
//             ->addFieldToSelect('name');
//         foreach ($Collection as $plan) {
//             $options[] = [
//                 'label' => $plan->getName(),
//                 'value' => $plan->getId(),
//             ];
//         }
//         return $options;
//     }
// }
