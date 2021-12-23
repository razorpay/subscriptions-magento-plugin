<?php
namespace Razorpay\Subscription\Block\Product;

use Magento\Catalog\Block\Product\AbstractProduct;

class AddToCartProductOptionView  extends AbstractProduct
{
    private $objectManagement;

    public function __construct(\Magento\Catalog\Block\Product\Context $context, array $data = [])
    {
        parent::__construct($context, $data);
        $this->objectManagement = \Magento\Framework\App\ObjectManager::getInstance();

    }

    public function getProductSubscriptionOptions($productId)
    {
        return $this->objectManagement->get('Razorpay\Subscription\Model\Plans')
            ->getCollection()
            ->addFieldToSelect('plan_name', "planName")
            ->addFieldToSelect("plan_desc", "desc")
            ->addFieldToSelect("entity_id", "id")
            ->addFieldToSelect("plan_type", "type")
            ->addFieldToSelect("plan_bill_amount", "price")
            ->addFilter('magento_product_id', $productId)
            ->addFilter('plan_status', 1)
            ->getData();
    }

    public function getCurrencySymbol()
    {
        $storeManager = $this->objectManagement->get('\Magento\Store\Model\StoreManagerInterface');
        $currencyCode = $storeManager->getStore()->getCurrentCurrencyCode();
        $currency = $this->objectManagement->create('\Magento\Directory\Model\CurrencyFactory')->create()->load($currencyCode);
        return $currency->getCurrencySymbol();
    }
}
