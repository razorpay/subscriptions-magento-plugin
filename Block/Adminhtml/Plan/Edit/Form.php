<?php
namespace Razorpay\Subscription\Block\Adminhtml\Plan\Edit;
 
use \Magento\Backend\Block\Widget\Form\Generic;
 
class Form extends Generic
{
 
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_status;
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_productList;
 
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param \Razorpay\Subscription\Model\Source\Plan\Status $status
     * @param \Razorpay\Subscription\Model\Source\ProductList $productList
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Razorpay\Subscription\Model\Source\Plan\Status $status,
        \Razorpay\Subscription\Model\Source\ProductList $productList,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        $this->_status = $status;
        $this->_productList = $productList;
        parent::__construct($context, $registry, $formFactory, $data);
    }
 
    /**
     * Init form
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('plan_form');
        $this->setTitle(__('Plan Information'));
    }
 
    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \Razorpay\Subscription\Model\Plans $model */
        $model = $this->_coreRegistry->registry('subscribed_plan');
 
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );
 
        $form->setHtmlIdPrefix('plan_');
 
        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Plan Details'), 'class' => 'fieldset-wide']
        );
 
        if ($model->getId()) {
            $fieldset->addField('entity_id', 'hidden', ['name' => 'entity_id']);
        }
 
        $fieldset->addField(
            'plan_name',
            'text',
            ['name' => 'plan_name', 'label' => __('Plan Name'), 'title' => __('Plan Name'), 'required' => true]
        );
 
        $fieldset->addField(
            'plan_desc',
            'textarea',
            ['name' => 'plan_desc', 'label' => __('Plan Description'), 'title' => __('Plan Description'), 'required' => true]
        );
        // $fieldset->addField(
        //     'magento_product_id',
        //     'text',
        //     ['name' => 'magento_product_id', 'label' => __('Select Product'), 'title' => __('Select Product'), 'required' => true]
        // );
         // product List - Dropdown
          $productList = $this->_productList->toOptionArray();
       
         $fieldset->addField(
             'magento_product_id',
             'select',
             ['name' => 'magento_product_id', 'label' => __('Select Product'), 'title' => __('Select Product'), 'required' => true, 'values' => $productList]
         );
        $fieldset->addField(
            'plan_bill_amount',
            'text',
            ['name' => 'plan_bill_amount', 'label' => __('Billing Amount'), 'title' => __('Billing Amount'), 'required' => true]
        );
        $fieldset->addField(
            'plan_interval',
            'text',
            ['name' => 'plan_interval', 'label' => __('Billing Frequency'), 'title' => __('Billing Frequency'), 'required' => true]
        );
        // $fieldset->addField(
        //     'plan_type',
        //     'text',
        //     ['name' => 'plan_type', 'label' => __('Billing Frequency'), 'title' => __('Billing Frequency'), 'required' => true]
        // );
        $fieldset->addField(
            'plan_type', 'select', array(
                'label'              => 'Interval',
                'name'               => 'plan_type',
                'values'=> array('daily'=>'Daily', 'weekly'=>'Weekly','monthly'=>'Monthly','yearly'=>'Yearly'),
                'required'=>true
            )
        );
        $fieldset->addField(
            'plan_bill_cycle',
            'text',
            ['name' => 'plan_bill_cycle', 'label' => __('No. of Billing Cycles'), 'title' => __('No. of Billing Cycles'), 'required' => true]
        );

        $fieldset->addField(
            'plan_trial',
            'text',
            ['name' => 'plan_trial', 'label' => __('Start Subscription'), 'title' => __('Start Subscription'), 'required' => true]
        );
        $fieldset->addField(
            'plan_addons',
            'text',
            ['name' => 'plan_addons', 'label' => __('Add-On Amount (Optional)'), 'title' => __('Add-On Amount (Optional)')]
        );
       
        // Status - Dropdown
        if (!$model->getId()) {
            $model->setStatus('1'); // Enable status when adding a Plan
        }
        $statuses = $this->_status->toOptionArray();
        $fieldset->addField(
            'plan_status',
            'select',
            ['name' => 'plan_status', 'label' => __('Status'), 'title' => __('Status'), 'required' => true, 'values' => $statuses]
        );
        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);
 
        return parent::_prepareForm();
    }
}
