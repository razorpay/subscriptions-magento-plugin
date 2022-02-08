<?php
namespace Razorpay\Subscription\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Razorpay\Subscription\Model\SubscriptionProductAttributes;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class RazorpaySubscriptionAttribute implements DataPatchInterface, PatchRevertableInterface {
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;
    /**
     * @var $eavSetupFactory
     */
    private $eavSetupFactory;
    /**
     * @var $eavTypeFactory
     */
    private $eavTypeFactory;
    /**
     * @var $groupCollectionFactory
     */
    private $groupCollectionFactory;
    /**
     * @var $attributeSetFactory
     */
    private $attributeSetFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory,
        \Magento\Eav\Model\Entity\TypeFactory $eavTypeFactory,
        \Magento\Eav\Model\Entity\Attribute\SetFactory $attributeSetFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory $groupCollectionFactory
    )
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavTypeFactory = $eavTypeFactory;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->groupCollectionFactory = $groupCollectionFactory;


    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $groupName = 'Subscriptions by Razorpay';

        /** @var EavSetup $eavSetup */

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
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        foreach ($attributes as $attributeCode => $attribute) {
            $eavSetup->addAttribute(Product::ENTITY, $attributeCode, $attribute);
        }
        $this->sortGroup($groupName, 11);
    }

    /**
     * @param $attributeGroupName
     * @param $order
     * @return bool
     */
    private function sortGroup($attributeGroupName, $order)
    {
        $entityType = $this->eavTypeFactory->create()->loadByCode(Product::ENTITY);
        $setCollection = $this->attributeSetFactory->create()->getCollection();
        $setCollection->addFieldToFilter('entity_type_id', $entityType->getId());

        foreach ($setCollection as $attributeSet) {
            $this->groupCollectionFactory->create()
                ->addFieldToFilter('attribute_set_id', $attributeSet->getId())
                ->addFieldToFilter('attribute_group_name', $attributeGroupName)
                ->getFirstItem()
                ->setSortOrder($order)
                ->save();
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function revert()
    {
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }
}
