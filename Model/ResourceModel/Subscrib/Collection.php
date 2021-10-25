<?php

namespace Razorpay\Subscription\Model\ResourceModel\Subscrib;
use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
/**
 * Class Collection
 * @package Razorpay\Subscription\Model\ResourceModel\Subscrib
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';
    /**
     * @var string
     */
    protected $_eventPrefix = 'razorpay_subscription_subscrib_collection';
    /**
     * @var string
     */
    protected $_eventObject = 'subscrib_collection';

    /**
     * Define resource model
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Razorpay\Subscription\Model\Subscrib', 'Razorpay\Subscription\Model\ResourceModel\Subscrib');

    }
}