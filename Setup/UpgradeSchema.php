<?php

namespace Razorpay\Subscription\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Razorpay\Subscription\Model\ResourceModel\Plans;
use Razorpay\Subscription\Model\ResourceModel\Subscriptions;

class UpgradeSchema implements  UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context){
        $setup->startSetup();

        $this->createPlanTable($setup);
        $this->createSubscriptionTable($setup);

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
                Table::TYPE_TEXT,
                30,
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
                'status',
                Table::TYPE_TEXT,
                30,
                [
                    'nullable' => false,
                    'comment' => 'Razorpay Subscription status'
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
                    'comment' => 'Nest charge date'
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
        ;

        $setup->getConnection()->createTable($table);

    }
}
