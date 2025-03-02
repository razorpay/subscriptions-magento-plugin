<?php

namespace Razorpay\Subscription\Model\Source\Plan;

use Magento\Framework\Data\OptionSourceInterface;

class PlanInterval implements OptionSourceInterface
{

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value'     => 'yearly',
                'label'     => 'Year(s)',
            ],
            [
                'value'     => 'monthly',
                'label'     => 'Month(s)',
            ],
            [
                'value'     => 'weekly',
                'label'     => 'Week(s)',
            ],
            [
                'value'     => 'daily',
                'label'     => 'Day(s)',
            ]
        ];
    }
}


