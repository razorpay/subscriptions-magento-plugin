<?php
namespace Razorpay\Subscription\Controller\Adminhtml\Plan;
 
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action;
 
class Index extends Action
{
    const ADMIN_RESOURCE = 'Razorpay_Subscription::plan';
 
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
 
    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }
 
    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Razorpay_Subscription::rzp_subscriptions');
        $resultPage->addBreadcrumb(__('Plans'), __('Plans'));
        $resultPage->addBreadcrumb(__('Manage Plans'), __('Manage Plans'));
        $resultPage->getConfig()->getTitle()->prepend(__('Plan'));
 
        return $resultPage;
    }
}