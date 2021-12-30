<?php
namespace Razorpay\Subscription\Model\Source\Plan;
 
class Status implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Razorpay\Subscription\Model\Plans
     */
    protected $_plan;
 
    /**
     * Constructor
     *
     * @param \Razorpay\Subscription\Model\Plans $plan
     */
    public function __construct(\Razorpay\Subscription\Model\Plans $plan)
    {
        $this->_plan = $plan;
    }
 
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options[] = ['label' => '', 'value' => ''];
        $availableOptions = $this->_plan->getAvailableStatuses();
        foreach ($availableOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }
        return $options;
    }
}