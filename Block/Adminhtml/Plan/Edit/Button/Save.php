<?php

namespace Razorpay\Subscription\Block\Adminhtml\Plan\Edit\Button;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class Save
 */
class Save extends Generic implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Save'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => ['button' => ['event' => 'save']],
                'form-role' => 'save',
            ],
            'sort_order' => 90,
        ];
    }
}









//    extends Generic
//{
//    /**
//     * {@inheritdoc}
//     */
//    public function getButtonData()
//    {
//
//        return [
//            'label' => __('Save'),
//            'class' => 'save primary',
//            'data_attribute' => [
//                'mage-init' => [
//                    'buttonAdapter' => [
//                        'actions' => [
//                            [
//                                'targetName' => 'plan_from.plan_from',
//                                'actionName' => 'save',
//                                'params' => [
//                                    false
//                                ]
//                            ]
//                        ]
//                    ]
//                ]
//            ],
//            'class_name' => Container::DEFAULT_CONTROL,
//
//        ];
//
//    }
//
//    /**
//     * Get URL for back
//     *
//     * @return string
//     */
//    private function getBackUrl()
//    {

//        return $this->getUrl('subscribed/*/save');
//    }

//}

