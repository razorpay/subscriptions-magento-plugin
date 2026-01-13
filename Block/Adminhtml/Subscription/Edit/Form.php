<?php
namespace Razorpay\Subscription\Block\Adminhtml\Subscription\Edit;

use \Magento\Backend\Block\Widget\Form\Generic;

class Form extends Generic
{

    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
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
        $this->setId('subscription_form');
        $this->setTitle(__('Subscription Information'));
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        $subscriptionInfo = $this->getLayout()->createBlock(
            'Razorpay\Subscription\Block\View')->getSubscrib();
        $subscriptionInfo['s_id'] = $this->_request->getParam('subscription_id');

        $plans = $this->getLayout()->createBlock('Razorpay\Subscription\Block\Edit')->getPlans($subscriptionInfo['product_id']);
        $pending = $this->getLayout()->createBlock('Razorpay\Subscription\Block\Edit')->pendingUpdate();
        $readonly = !empty($pending) ? true : false;

        foreach($plans as $option){
            $options[] = ["value" => $option['plan_id'],"label" => $option['plan_name']];
        }

        /** @var \Razorpay\Subscription\Model\Subscriptions $model */
        $model = $this->_coreRegistry->registry('subscribed_subscription');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );

        $form->setHtmlIdPrefix('subscription_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Subscription Details'), 'class' => 'fieldset-wide']
        );

        if ($model->getId()) {
            $fieldset->addField('entity_id', 'hidden', ['name' => 'entity_id']);
        }
        $fieldset->addField('s_id', 'hidden', ['name' => 's_id']);

        $fieldset->addField(
            'subscription_id',
            'text',
            ['name' => 'subscription_id', 'label' => __('Subscription Id'), 'title' => __('Subscription Id'), 'required' => true,'disabled'=>true]
        );

        $fieldset->addField(
            'razorpay_customer_id',
            'text',
            ['name' => 'razorpay_customer_id', 'label' => __('Customer Id'), 'title' => __('Customer Id'), 'required' => true,'disabled'=>true]
        );
        $fieldset->addField(
            'plan_id',
            'select',
            ['name' => 'plan_id', 'label' => __('Select Plan'), 'title' => __('Select Plan'), 'required' => true,'values' => $options,'disabled'=>$readonly]
        );
        $fieldset->addField(
            'plan_name',
            'text',
            ['name' => 'plan_name', 'label' => __('Plan Name'), 'title' => __('Plan Name'), 'required' => true,'disabled'=>true]
        );
        $fieldset->addField(
            'plan_type',
            'text',
            ['name' => 'plan_type', 'label' => __('Plan Type'), 'title' => __('Plan Type'), 'required' => true,'disabled'=>true]
        );
        $fieldset->addField(
            'value',
            'text',
            ['name' => 'value', 'label' => __('Product Name'), 'title' => __('Product Name'), 'required' => true,'disabled'=>true]
        );
        $fieldset->addField(
            'status',
            'text',
            ['name' => 'status', 'label' => __('Status'), 'title' => __('Status'), 'required' => true,'disabled'=>true]
        );
        $fieldset->addField(
            'total_count',
            'text',
            ['name' => 'total_count', 'label' => __('Total Count'), 'title' => __('Total Count'), 'required' => true,'disabled'=>true]
        );
        $fieldset->addField(
            'paid_count',
            'text',
            ['name' => 'paid_count', 'label' => __('Paid Count'), 'title' => __('Paid Count'), 'required' => true,'disabled'=>true]
        );
        $fieldset->addField(
            'subscription_created_at',
            'text',
            ['name' => 'subscription_created_at', 'label' => __('Created At'), 'title' => __('Created At'), 'required' => true,'disabled'=>true]
        );
        $fieldset->addField(
            'next_charge_at',
            'text',
            ['name' => 'next_charge_at', 'label' => __('Next Charge At'), 'title' => __('Next Charge At'), 'required' => true,'disabled'=>true]
        );
        $fieldset->addField(
            'start_at',
            'text',
            ['name' => 'start_at', 'label' => __('Start At'), 'title' => __('Start At'), 'required' => true,'disabled'=>true]
        );
        $fieldset->addField(
            'end_at',
            'text',
            ['name' => 'end_at', 'label' => __('End At'), 'title' => __('End At'), 'required' => true,'disabled'=>true]
        );

        $form->setValues($subscriptionInfo);
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}