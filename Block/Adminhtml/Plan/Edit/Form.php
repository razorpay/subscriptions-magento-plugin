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
            $readonly = true;
        }else { $readonly=false;}
        
        $fieldset->addField(
            'plan_name',
            'text',
            ['name' => 'plan_name', 'label' => __('Plan Name'), 'title' => __('Plan Name'), 'required' => true,'disabled'=>$readonly]
        );
 
        $fieldset->addField(
            'plan_desc',
            'textarea',
            ['name' => 'plan_desc', 'label' => __('Plan Description'), 'title' => __('Plan Description'), 'required' => true,'disabled'=>$readonly]
        );
              // product List - Dropdown
          $productList = $this->_productList->toOptionArray();
       
         $fieldset->addField(
             'magento_product_id',
             'select',
             ['name' => 'magento_product_id', 'label' => __('Select Product'), 'title' => __('Select Product'), 'required' => true, 'values' => $productList,'disabled'=>$readonly]
         );
        $fieldset->addField(
            'plan_bill_amount',
            'text',
            ['name' => 'plan_bill_amount', 'label' => __('Billing Amount'), 'title' => __('Billing Amount'), 'required' => true,'disabled'=>$readonly,'class'=> 'required-entry validate-number']
        );
        $fieldset->addField(
            'plan_interval',
            'text',
            ['name' => 'plan_interval', 'label' => __('Billing Frequency'), 'title' => __('Billing Frequency'), 'required' => true,'disabled'=>$readonly,'class'=> 'required-entry validate-digits']
        );
        
        $fieldset->addField(
            'plan_type', 'select', array(
                'label'              => 'Interval',
                'name'               => 'plan_type',
                'class' => 'required-entry',
                'onchange' => 'checkSelectedItem(this.value)',
              
                'values'    => array(
                    array(
                        'value'     => '',
                        'label'     => 'Please Select',      
                   ),
                   array(
                    'value'     => 'daily',
                    'label'     => 'Daily',      
               ),
                    array(
                         'value'     => 'weekly',
                         'label'     => 'Weekly',      
                    ),
                    array(
                        'value'     => 'monthly',
                        'label'     => 'Monthly',         
                    ),
                    array(
                        'value'     => 'yearly',
                        'label'     => 'Yearly',         
                    )
                    ),
                'required'=>true,
                'disabled'=>$readonly
            )
        )->setAfterElementHtml("
        <script type=\"text/javascript\">
           function checkSelectedItem(selectElement){ 
            plan_interval = document.getElementById('plan_plan_interval').value;
            plan_type = document.getElementById('plan_plan_interval').value;
            if(selectElement =='daily')
            {
            if((plan_interval==='') || (plan_interval <='7')){
            alert('For daily plans, the minimum Billing Frequency is 7.');
                }
            }
           
           }
           
        </script>"); 

          $fieldset->addField(
            'plan_bill_cycle',
            'text',
            ['name' => 'plan_bill_cycle', 'label' => __('No. of Billing Cycles'), 'title' => __('No. of Billing Cycles'), 'required' => true,'disabled'=>$readonly,'class'=> 'required-entry validate-digits']
        );
        if (!$model->getId()) {
            $model->setData('plan_trial', '0');
        }
        $fieldset->addField(
            'plan_trial',
            'text',
            ['name' => 'plan_trial', 'label' => __('Trial Days'), 'title' => __('Trial Days'),'note' => 'Default is 0. The subscription starts immediately after the authorization payment','disabled'=>$readonly,'class'=> 'required-entry validate-digits']
        );
        $fieldset->addField(
            'plan_addons',
            'text',
            ['name' => 'plan_addons', 'label' => __('Add-On Amount (Optional)'), 'title' => __('Add-On Amount (Optional)'),'disabled'=>$readonly,'class'=> 'required-entry validate-number']
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
