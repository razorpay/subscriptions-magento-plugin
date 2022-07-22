<?php
namespace Razorpay\Subscription\Controller\Adminhtml\Subscription;

use Razorpay\Subscription\Controller\Adminhtml\Subscription;

class Index extends Subscription
{
    const ADMIN_RESOURCE = 'Razorpay_Subscription::rzp_subscriptions';

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /**
 * @var \Magento\Backend\Model\View\Result\Page $resultPage 
*/
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->setActiveMenu('Razorpay_Subscription::rzp_subscriptions');
        $resultPage->addBreadcrumb(__('Jobs'), __('Jobs'));
        $resultPage->addBreadcrumb(__('Manage Departments'), __('Manage Departments'));
        $resultPage->getConfig()->getTitle()->prepend(__('Department'));

        return $resultPage;
    }
}