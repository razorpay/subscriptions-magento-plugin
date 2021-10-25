<?php

namespace Razorpay\Subscription\Model;

use Magento\Framework\Model\AbstractModel;

class Plans extends AbstractModel
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Razorpay\Subscription\Model\ResourceModel\Plans::class);
    }
}
