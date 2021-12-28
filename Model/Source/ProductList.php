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
            ->addAttributeToSelect('*')
          ->addAttributeToFilter('type_id', array('in' => array('simple','configurable')));
          
        foreach ($collection as $category) {
            $options[] = [
                'label' => $category->getName(),
                'value' => $category->getId(),
            ];
        }
 
        return $options;
    }
}

