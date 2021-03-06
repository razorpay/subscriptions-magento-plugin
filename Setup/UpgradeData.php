<?php

namespace Razorpay\Subscription\Setup;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Razorpay\Subscription\Model\SubscriptionProductAttributes;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * Eav setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var \Magento\Eav\Model\Entity\TypeFactory
     */
    private $eavTypeFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Eav\AttributeSetFactory
     */
    private $attributeSetFactory;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory
     */
    private $groupCollectionFactory;

    public function __construct(
        \Magento\Eav\Model\Entity\TypeFactory $eavTypeFactory,
        \Magento\Eav\Model\Entity\Attribute\SetFactory $attributeSetFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory $groupCollectionFactory,
        EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavTypeFactory = $eavTypeFactory;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->groupCollectionFactory = $groupCollectionFactory;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $this->installEntities($setup);
        $installer->endSetup();
    }


    public function installEntities($setup){


        $groupName = 'Subscriptions by Razorpay';

        $attributes = [
            'razorpay_subscription_enabled' => [
                'type' => 'int',
                'label' => 'Subscription Enabled',
                'input' => 'boolean',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'sort_order' => 100,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'group' => $groupName,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'required' => false
            ],
            'razorpay_subscription_mode' => [
                'type' => 'varchar',
                'label' => 'Subscription Mode',
                'input' => 'select',
                'sort_order' => 130,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'group' => $groupName,
                'source' => SubscriptionProductAttributes::class,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'used_for_promo_rules' => true,
                'required' => false
            ],
        ];

        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        foreach ($attributes as $attributeCode => $attribute){
            $eavSetup->addAttribute(Product::ENTITY, $attributeCode, $attribute);
        }

        $this->sortGroup($groupName,11);
    }

    private function sortGroup($attributeGroupName, $order)
    {
        $entityType = $this->eavTypeFactory->create()->loadByCode('catalog_product');
        $setCollection = $this->attributeSetFactory->create()->getCollection();
        $setCollection->addFieldToFilter('entity_type_id', $entityType->getId());

        foreach ($setCollection as $attributeSet)
        {
            $this->groupCollectionFactory->create()
                ->addFieldToFilter('attribute_set_id', $attributeSet->getId())
                ->addFieldToFilter('attribute_group_name', $attributeGroupName)
                ->getFirstItem()
                ->setSortOrder($order)
                ->save();
        }

        return true;
    }
}
