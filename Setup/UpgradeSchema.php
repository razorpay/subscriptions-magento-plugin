<?php

namespace Razorpay\Subscription\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Razorpay\Subscription\Model\ResourceModel\Plans;
use Razorpay\Subscription\Model\ResourceModel\Subscriptions;
use Razorpay\Subscription\Model\ResourceModel\SubscriptionsOrderMapping;

class UpgradeSchema implements  UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context){
        $setup->startSetup();

        $this->createPlanTable($setup);
        $this->createSubscriptionTable($setup);
        $this->createSubscriptionOrderMappingTable($setup);

        $setup->endSetup();
    }

    private function createPlanTable($setup){
        $table = $setup->getConnection()->newTable($setup->getTable(Plans::TABLE_NAME));

        $table
            ->addColumn(
                'entity_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'unsigned' => true,
                    'primary'  => true,
                    'nullable' => false

                ]
            )
            ->addColumn(
                'plan_id',
                Table::TYPE_TEXT,
                30,
                [
                    'unique'   => true,
                    'nullable' => false,
                    'comment' => 'Razorpay Plan Id'
                ]
            )
            ->addColumn(
                'magento_product_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'nullable' => false,
                    'comment' => 'Magento Product Id'
                ]
            )
            ->addColumn(
                'plan_name',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => false,
                    'comment' => 'Plan name'
                ]
            )
            ->addColumn(
                'plan_type',
                Table::TYPE_TEXT,
                30,
                [
                    'nullable' => false,
                    'comment' => 'Razorpay Plan Product Plan type'
                ]
            )
            ->addColumn(
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                [
                    'nullable' => false,
                    'default' => Table::TIMESTAMP_INIT,
                    'comment' => 'Created at'
                ]
            )
            ->addIndex(
                'plan',
                ['plan_id'],
                [
                    'type'      => AdapterInterface::INDEX_TYPE_UNIQUE,
                    'nullable'  => false,
                ]
            )
            ->addIndex(
                'plan_product',
                ['plan_id', 'plan_type','magento_product_id','plan_name'],
                [
                    'type'      => AdapterInterface::INDEX_TYPE_INDEX,
                ]
            )
        ;

        $setup->getConnection()->createTable($table);

    }

    private function createSubscriptionTable($setup)
    {
        $table = $setup->getConnection()->newTable($setup->getTable(Subscriptions::TABLE_NAME));

        $table
            ->addColumn(
                'entity_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'unsigned' => true,
                    'primary'  => true,
                    'nullable' => false

                ]
            )
            ->addColumn(
                'plan_entity_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'nullable' => false,
                    'comment' => 'Razorpay Plan table Id'
                ]
            )
            ->addColumn(
                'subscription_id',
                Table::TYPE_TEXT,
                30,
                [
                    'unique'   => true,
                    'nullable' => false,
                    'comment' => 'Razorpay subscription id'
                ]
            )
            ->addColumn(
                'quote_id',
                Table::TYPE_INTEGER,
                [
                    'nullable' => true,
                    'comment' => 'Magento quote id'
                ]
            )
            ->addColumn(
                'product_id',
                Table::TYPE_INTEGER,
                [
                    'nullable' => true,
                    'comment' => 'Magento product id'
                ]
            )
            ->addColumn(
                'razorpay_customer_id',
                Table::TYPE_TEXT,
                30,
                [
                    'nullable' => false,
                    'comment' => 'Razorpay Customer id'
                ]
            )
            ->addColumn(
                'magento_user_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'nullable' => true,
                    'comment' => 'Magento user id'
                ]
            )
            ->addColumn(
                'status',
                Table::TYPE_TEXT,
                30,
                [
                    'nullable' => false,
                    'comment' => 'Razorpay Subscription status'
                ]
            )
            ->addColumn(
                'cancel_by',
                Table::TYPE_TEXT,
                30,
                [
                    'nullable' => false,
                    'comment' => 'Razorpay Subscription cancel by user or admin'
                ]
            )
            ->addColumn(
                'total_count',
                Table::TYPE_INTEGER,
                30,
                [
                    'nullable' => false,
                    'default' => 0,
                    'comment' => 'Total subscription count to be charged'
                ]
            )
            ->addColumn(
                'paid_count',
                Table::TYPE_INTEGER,
                30,
                [
                    'nullable' => false,
                    'default' => 0,
                    'comment' => 'Total subscription paid count'
                ]
            )
            ->addColumn(
                'remaining_count',
                Table::TYPE_INTEGER,
                30,
                [
                    'nullable' => false,
                    'default' => 0,
                    'comment' => 'Subscription remaining to be charged'
                ]
            )
            ->addColumn(
                'auth_attempts',
                Table::TYPE_INTEGER,
                30,
                [
                    'nullable' => false,
                    'default' => 0,
                    'comment' => 'The number of times that the charge for the current billing cycle has been attempted on the card.'
                ]
            )
            ->addColumn(
                'start_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                [
                    'nullable' => true,
                    'comment' => 'Subscription start date'
                ]
            )
            ->addColumn(
                'end_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                [
                    'nullable' => true,
                    'comment' => 'Subscription end date'
                ]
            )
            ->addColumn(
                'subscription_created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                [
                    'nullable' => true,
                    'comment' => 'Subscription Created at'
                ]
            )
            ->addColumn(
                'next_charge_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                [
                    'nullable' => true,
                    'comment' => 'Next charge date'
                ]
            )
            ->addColumn(
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                [
                    'nullable' => false,
                    'default' => Table::TIMESTAMP_INIT,
                    'comment' => 'Created at'
                ]
            )
            ->addIndex(
                'subscription_id',
                ['subscription_id'],
                [
                    'type'      => AdapterInterface::INDEX_TYPE_UNIQUE,
                    'nullable'  => false,
                ]
            )
            ->addIndex(
                'plan_entity_id',
                ['plan_entity_id'],
                [
                    'type'      => AdapterInterface::INDEX_TYPE_INDEX,
                ]
            )
            ->addIndex(
                'subscription_status',
                ['subscription_id','status'],
                [
                    'type'      => AdapterInterface::INDEX_TYPE_INDEX,
                ]
            )
            ->addIndex(
                'product_id',
                ['subscription_id','product_id'],
                [
                    'type'      => AdapterInterface::INDEX_TYPE_INDEX,
                ]
            )
            ->addIndex(
                'subscription_user',
                ['subscription_id','razorpay_customer_id','magento_user_id'],
                [
                    'type'      => AdapterInterface::INDEX_TYPE_INDEX,
                ]
            )
        ;

        $setup->getConnection()->createTable($table);

    }

    public function createSubscriptionOrderMappingTable($setup)
    {
        $table = $setup->getConnection()->newTable($setup->getTable(SubscriptionsOrderMapping::TABLE_NAME));

        $table
            ->addColumn(
                'entity_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'unsigned' => true,
                    'primary'  => true,
                    'nullable' => false

                ]
            )
            ->addColumn(
                'subscription_entity_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'nullable' => false,
                    'comment' => 'Razorpay subscription table Id'
                ]
            )
            ->addColumn(
                'is_trial_order',
                Table::TYPE_BOOLEAN,
                1,
                [
                    'nullable' => false,
                    'default' => 0,
                    'comment' => 'Is a trial order'
                ]
            )
            ->addColumn(
                'increment_order_id',
                Table::TYPE_TEXT,
                32,
                [
                    'nullable' => true
                ]
            )
            ->addColumn(
                'rzp_payment_id',
                Table::TYPE_TEXT,
                25,
                [
                    'nullable' => true
                ]
            )
            ->addColumn(
                'by_webhook',
                Table::TYPE_BOOLEAN,
                1,
                [
                    'nullable' => false,
                    'default' => 0
                ]
            )
            ->addColumn(
                'by_frontend',
                Table::TYPE_BOOLEAN,
                1,
                [
                    'nullable' => false,
                    'default' => 0
                ]
            )
            ->addColumn(
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                [
                    'nullable' => false,
                    'default' => Table::TIMESTAMP_INIT,
                    'comment' => 'Created at'
                ]
            )
            ->addIndex(
                'subscription_id',
                ['subscription_entity_id'],
                [
                    'type'      => AdapterInterface::INDEX_TYPE_INDEX,
                    'nullable'  => false,
                ]
            )
            ->addIndex(
                'order_details',
                ['is_trial_order','increment_order_id','rzp_payment_id'],
                [
                    'type'      => AdapterInterface::INDEX_TYPE_INDEX,
                    'nullable'  => false,
                ]
            )
        ;
        $setup->getConnection()->createTable($table);

    }
}
