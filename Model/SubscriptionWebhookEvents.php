<?php

namespace Razorpay\Subscription\Model;

use Magento\Framework\Data\OptionSourceInterface;
use Razorpay\Magento\Model\WebhookEvents;

class SubscriptionWebhookEvents implements OptionSourceInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $events = array();
        $webhookEvent = new WebhookEvents();
        foreach ($webhookEvent->toOptionArray() as $value){
            array_push($events, $value);
        }
        array_push(
            $events, [
            'value' => "subscription.charged",
            'label' => __('subscription.charged')
            ]
        );

        array_push(
            $events, [
            'value' => "subscription.paused",
            'label' => __('subscription.paused')
            ]
        );

        array_push(
            $events, [
            'value' => "subscription.cancelled",
            'label' => __('subscription.cancelled')
            ]
        );
        return $events;
    }
}
