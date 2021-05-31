<?php

namespace Razorpay\Subscription\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Plans extends AbstractDb
{
    const TABLE_NAME = 'razorpay_plans';

    protected function _construct()
    {
        $this->_init(static::TABLE_NAME, 'entity_id');
    }
}
