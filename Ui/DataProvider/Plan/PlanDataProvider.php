<?php

namespace Razorpay\Subscription\Ui\DataProvider\Plan;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Razorpay\Subscription\Model\ResourceModel\Plans;
use Razorpay\Subscription\Model\ResourceModel\Plans\CollectionFactory;

class PlanDataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    private $loadedData;
    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $palnCollectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $planCollectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $planCollectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        foreach ($items as $page) {
            $rec = $page->getData();
            $rec['readonly'] = true;
            $this->loadedData[$page->getId()] = $rec;
        }
        return $this->loadedData;
    }
}
