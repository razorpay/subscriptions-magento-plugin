<?php

namespace Razorpay\Subscription\Model;

use Magento\Framework\Model\AbstractModel;

class SubscriptionsOrderMapping extends AbstractModel
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Razorpay\Subscription\Model\ResourceModel\SubscriptionsOrderMapping::class);
    }

}