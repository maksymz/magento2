<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ConfigurableProduct\Model\ResourceModel\Product;

use Magento\Catalog\Model\ResourceModel\Product\Relation;
use Magento\Framework\ObjectManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Tests Catalog Product Relation resource model.
 *
 * @see Relation
 */
class RelationTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var Relation
     */
    private $model;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->model = $this->objectManager->get(Relation::class);
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
    }

    /**
     * Tests that getRelationsByChildren will return parent products entity ids of child products entity ids.
     *
     * @magentoDataFixture Magento/ConfigurableProduct/_files/configurable_products.php
     */
    public function testGetRelationsByChildren(): void
    {
        $childSkusOfParentSkus = [
            'configurable' => ['simple_10', 'simple_20'],
            'configurable_12345' => ['simple_30', 'simple_40'],
        ];
        $configurableSkus = [
            'configurable',
            'configurable_12345',
            'simple_10',
            'simple_20',
            'simple_30',
            'simple_40',
        ];
        $configurableIdsOfSkus = [];

        $searchCriteria = $this->searchCriteriaBuilder->addFilter('sku', $configurableSkus, 'in')
            ->create();
        $configurableProducts = $this->productRepository->getList($searchCriteria)
            ->getItems();

        $childIds = [];

        foreach ($configurableProducts as $product) {
            $configurableIdsOfSkus[$product->getSku()] = $product->getId();

            if ($product->getTypeId() != 'configurable') {
                $childIds[] = $product->getId();
            }
        }

        $parentIdsOfChildIds = [];

        foreach ($childSkusOfParentSkus as $parentSku => $childSkus) {
            foreach ($childSkus as $childSku) {
                $childId = $configurableIdsOfSkus[$childSku];
                $parentIdsOfChildIds[$childId][] = $configurableIdsOfSkus[$parentSku];
            }
        }

        /**
         * Assert there are parent configurable products ids in result of getRelationsByChildren method
         * and they are related to child ids.
         */
        $result = $this->model->getRelationsByChildren($childIds);

        foreach ($childIds as $childId) {
            $this->assertArrayHasKey($childId, $result);
            $this->assertContains($result[$childId], $parentIdsOfChildIds[$childId]);
        }
    }
}
