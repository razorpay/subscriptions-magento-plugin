<?php

namespace Razorpay\Subscription\Model\ResourceModel\Subscrib;
/**
 * Class Collection
 * @package MRazorpay\Subscription\Model\ResourceModel\Subscrib
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';
    /**
     * @var string
     */
    protected $_eventPrefix = 'md_customer_reviews_collection';
    /**
     * @var string
     */
    protected $_eventObject = 'reviews_collection';

    /**
     * Define resource model
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Razorpay\Subscription\Model\Subscrib', 'Razorpay\Subscription\Model\ResourceModel\Subscrib');

    }
}