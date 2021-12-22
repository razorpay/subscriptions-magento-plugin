<?php
namespace Razorpay\Subscription\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class SubscriptionView extends Column
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /** Url Path */
    const SUBSCRIPTION_VIEW_URL_PATH   = 'subscribed/subscription/view';

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = array(),
        UrlInterface $urlBuilder,
        array $data = array())
    {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return void
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');

                $item[$name] = html_entity_decode('<a href="'.$this->urlBuilder->getUrl(self::SUBSCRIPTION_VIEW_URL_PATH, ['subscription_id' => $item['subscription_id'], 'id' => $item['entity_id']]).'">'.$item['subscription_id'].'</a>');
            }
        }

        return $dataSource;
    }
}
