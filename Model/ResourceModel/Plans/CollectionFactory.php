<?php
namespace Razorpay\Subscription\Model\ResourceModel\Plans;
 
class CollectionFactory
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager = null;
 
    /**
     * Instance name to create
     *
     * @var string
     */
    protected $_instanceName = null;
 
    /**
     * Factory constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager, $instanceName = '\\Razorpay\\Subscription\\Model\\ResourceModel\\Plans\\Collection')
    {
        $this->_objectManager = $objectManager;
        $this->_instanceName = $instanceName;
    }
 
    /**
     * Create class instance with specified parameters
     *
     * @param array $data
     * @return \Razorpay\Subscription\Model\ResourceModel\Plans\Collection
     */
    public function create(array $data = array())
    {
        return $this->_objectManager->create($this->_instanceName, $data);
    }
}