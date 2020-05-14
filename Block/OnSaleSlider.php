<?php

namespace Magestar\OnSale\Block;


class OnSaleSlider extends \Magento\Framework\View\Element\Template
{

    const MODULE_ENABLED = 'onsale/general/enable';

    protected $_template = 'Magestar_OnSale::onsaleslider.phtml';

    protected $_priceHelper;

    protected $_imageBuilder;

    protected $searchCriteria;

    protected $filterBuilder;

    protected $productRepository;

    protected $productStatus;

    protected $productVisibility;

    protected $filterGroupBuilder;

    protected $date;

    protected $scopeConfig;

    private $items = null;

    /**
     * OnSaleSlider constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus
     * @param \Magento\Catalog\Model\Product\Visibility $productVisibility
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Magento\Catalog\Block\Product\ImageBuilder $_imageBuilder
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Api\SearchCriteriaInterface $criteria,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\Catalog\Block\Product\ImageBuilder $_imageBuilder,
        \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->productRepository = $productRepository;
        $this->searchCriteria = $criteria;
        $this->filterBuilder = $filterBuilder;
        $this->productStatus = $productStatus;
        $this->productVisibility = $productVisibility;
        $this->_priceHelper = $priceHelper;
        $this->_imageBuilder = $_imageBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->date = $date;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->scopeConfig->getValue(self::MODULE_ENABLED,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE) == 1;
    }

    /**
     * @return
     */
    public function getProductData()
    {
        if ($this->isActive() && $this->items === null) {
            /** @var \Magento\Framework\Api\Search\FilterGroup $withSpecialPrice */
            $withSpecialPrice = $this->filterGroupBuilder->create();
            $withSpecialPrice->setFilters(
                [
                    $this->filterBuilder
                        ->setField('special_price')
                        ->setConditionType('gt')
                        ->setValue(0)
                        ->create(),
                ]
            );

            /** @var \Magento\Framework\Api\Search\FilterGroup $withVisibleStatus */
            $withVisibleStatus = $this->filterGroupBuilder->create();
            $withVisibleStatus->setFilters(
                [
                    $this->filterBuilder
                        ->setField('status')
                        ->setConditionType('in')
                        ->setValue($this->productStatus->getVisibleStatusIds())
                        ->create(),
                ]
            );

            /** @var \Magento\Framework\Api\Search\FilterGroup $visibleInCatalog */
            $visibleInCatalog = $this->filterGroupBuilder->create();
            $visibleInCatalog->setFilters(
                [
                    $this->filterBuilder
                        ->setField('visibility')
                        ->setConditionType('in')
                        ->setValue($this->productVisibility->getVisibleInSiteIds())
                        ->create(),
                ]
            );

            /** @var \Magento\Framework\Api\Search\FilterGroup $specialPriceDate */
            $specialPriceDate = $this->filterGroupBuilder->create();
            $specialPriceDate->setFilters(
                [
                    $this->filterBuilder
                        ->setField('special_to_date')
                        ->setConditionType('null')
                        ->setValue('')
                        ->create(),
                    $this->filterBuilder
                        ->setField('special_to_date')
                        ->setConditionType('gt')
                        ->setValue($this->date->gmtDate())
                        ->create(),
                ]
            );

            $this->searchCriteria->setFilterGroups([
                $withSpecialPrice,
                $withVisibleStatus,
                $visibleInCatalog,
                $specialPriceDate
            ])->setPageSize(10);
            $products = $this->productRepository->getList($this->searchCriteria);
            $this->items = $products->getItems();
        }
        return $this->items;
    }

    /**
     * @param $product
     * @param $imageId
     * @param array $attributes
     * @return \Magento\Catalog\Block\Product\Image
     */
    public function getImage($product, $imageId, $attributes = [])
    {
        return $this->_imageBuilder->setProduct($product)
            ->setImageId($imageId)
            ->setAttributes($attributes)
            ->create();
    }

    /**
     * @param $price
     * @return float|string
     */
    public function getFormatedPrice($price)
    {
        return $this->_priceHelper->currency($price, true, false);
    }

    /**
     * Get block cache life time
     *
     * @return int|null
     */
    protected function getCacheLifetime()
    {
        return 86400;
    }
}