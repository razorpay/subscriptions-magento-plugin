<?php

namespace Razorpay\Subscription\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class Subscrib
 * @package Razorpay\Subscription\Model\ResourceModel
 */
class Subscrib extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('razorpay_subscriptions', 'entity_id');
    }
}