<?php

namespace Razorpay\Subscription\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class SubscriptionsOrderMapping extends AbstractDb
{
    const TABLE_NAME = 'razorpay_subscriptions_order_mapping_details';

    protected function _construct()
    {
        $this->_init(static::TABLE_NAME, 'entity_id');
    }
}