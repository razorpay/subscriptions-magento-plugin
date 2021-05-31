<?php
namespace Razorpay\Subscription\Model\ResourceModel\Plans;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Initialize resource collection
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Razorpay\Subscription\Model\Plans', 'Razorpay\Subscription\Model\ResourceModel\Plans');
    }
}
