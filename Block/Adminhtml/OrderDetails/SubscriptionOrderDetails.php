<?php

namespace Razorpay\Subscription\Block\Adminhtml\OrderDetails;

use Magento\Backend\Block\Template;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Razorpay\Subscription\Model\Subscriptions;

class SubscriptionOrderDetails extends Template
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    private $objectManager;

    public function __construct(Template\Context $context, array $data = [])
    {
        parent::__construct($context, $data);
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    }


    public function fetchSubscriptionOrderDetails()
    {
        $order = $this->objectManager->get('\Magento\Sales\Model\Order')->load($this->getRequest()->getParam('order_id'));

        /* @var \Magento\Sales\Model\Order $order */
        $resource = $this->objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();

        $tableName = $resource->getTableName('razorpay_subscriptions_order_mapping_details');
        $sql = "SELECT  sub.entity_id, sub.subscription_id, p.plan_type FROM " . $tableName
            . " AS somd JOIN razorpay_subscriptions AS sub ON somd.subscription_entity_id = sub.entity_id"
            . " JOIN razorpay_plans AS p ON sub.plan_entity_id = p.entity_id WHERE somd.increment_order_id = ".$order->getIncrementId();
        $result = $connection->query($sql);

        return $result->fetch();

    }

}
