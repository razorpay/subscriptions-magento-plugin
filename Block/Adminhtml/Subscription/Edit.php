<?php
namespace Razorpay\Subscription\Block\Adminhtml\Subscription;

use Magento\Backend\Block\Widget\Form\Container;

class Edit extends Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry           $registry
     * @param array                                 $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Department edit block
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'entity_id';
        $this->_blockGroup = 'Razorpay_Subscription';
        $this->_controller = 'adminhtml_subscription';

        parent::_construct();

        if ($this->_isAllowedAction('Razorpay_Subscription::subscription_save')) {
            $this->buttonList->update('save', 'label', __('Save Subscription'));

            $this->buttonList->remove('back');
            $data = array(
                'label' =>  'Back',
                'onclick'   => 'setLocation(\'' . $this->getUrl('subscribed/index/index/') . '\')',
                'class'     =>  'back'
            );
            $this->buttonList->add('back', $data);
        } else {
            $this->buttonList->remove('save');
        }

    }

    /**
     * Get header with Department name
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        if ($this->_coreRegistry->registry('subscribed_subscription')->getId()) {
            return __("Edit Subscription '%1'", $this->escapeHtml($this->_coreRegistry->registry('subscribed_subscription')->getName()));
        } else {
            return __('New Subscription');
        }
    }

    /**
     * Check permission for passed action
     *
     * @param  string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * Getter of url for "Save and Continue" button
     * tab_id will be replaced by desired by JS later
     *
     * @return string
     */
    protected function _getSaveAndContinueUrl()
    {
        return $this->getUrl('subscribed/*/save', ['_current' => true, 'back' => 'edit', 'active_tab' => '']);
    }
}