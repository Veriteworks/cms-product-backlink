<?php

namespace MageSuite\CmsProductBacklink\DataProviders;

class ProductsAndPagesProvider
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \MageSuite\ContentConstructorFrontend\Service\CmsPageRenderer
     */
    protected $cmsPageRenderer;

    /**
     * @var \MageSuite\CmsProductBacklink\Model\PagesRepository
     */
    protected $pagesRepository;

    /**
     * @var \MageSuite\CmsProductBacklink\Helper\Data
     */
    protected $dataHelper;

    protected $componentsWithProducts = ['product-carousel', 'product-grid'];

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \MageSuite\ContentConstructorFrontend\Service\CmsPageRenderer $cmsPageRenderer,
        \MageSuite\CmsProductBacklink\Model\PagesRepository $pagesRepository,
        \MageSuite\CmsProductBacklink\Helper\Data $dataHelper
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->cmsPageRenderer = $cmsPageRenderer;
        $this->pagesRepository = $pagesRepository;
        $this->dataHelper = $dataHelper;
    }

    public function getProductsIdsAssociatedWithPages($storeId, $pageId)
    {
        if($pageId){
            $page = $this->pagesRepository->getPageById($pageId, $storeId);
            $pages = $page ? [$page] : null;
        } else {
            $pages = $this->pagesRepository->getPagesByStoreId($storeId);
        }

        if(empty($pages)){
            return null;
        }

        $this->cmsPageRenderer->setTheme($storeId);

        $productsIdsAssociatedWithPages = [];

        foreach($pages as $page){
            $components = $this->cmsPageRenderer->getComponentsFromXml($page->getLayoutUpdateXml());

            if(empty($components)){
                continue;
            }

            $productIds = $this->getProductIdsFromComponents($components);

            if(empty($productIds)){
                continue;
            }

            $productsIdsAssociatedWithPages[$page->getId()] = $productIds;
        }

        return $productsIdsAssociatedWithPages;
    }

    protected function getProductIdsFromComponents($components)
    {
        $productIds = [];

        foreach($components as $component){
            if(!$this->isComponentWithProducts($component['type'])){
                continue;
            }

            $componentBlock = $this->cmsPageRenderer->getComponentBlock($component);

            //We need to render component to get product identities
            $componentBlock->_toHtml();

            $identities = $componentBlock->getIdentities();

            if(empty($identities)){
                continue;
            }

            $ids = $this->dataHelper->getProductIdsFromIdentities($identities);

            if(empty($ids)){
                continue;
            }

            $productIds = array_merge($productIds, $ids);
        }

        return array_unique($productIds);
    }

    private function isComponentWithProducts($componentType)
    {
        if(in_array($componentType, $this->componentsWithProducts)){
            return true;
        }

        return false;
    }
}