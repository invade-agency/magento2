<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eav\Test\Unit\Model\Entity\Collection;

/**
 * AbstractCollection test
 *
 * Test for AbstractCollection class
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AbstractCollectionTest extends \PHPUnit\Framework\TestCase
{
    const ATTRIBUTE_CODE = 'any_attribute';
    const ATTRIBUTE_ID_STRING = '15';
    const ATTRIBUTE_ID_INT = 15;

    /**
     * @var AbstractCollectionStub|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $model;

    /**
     * @var \Magento\Framework\Data\Collection\EntityFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $coreEntityFactoryMock;

    /**
     * @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $loggerMock;

    /**
     * @var \Magento\Framework\Data\Collection\Db\FetchStrategyInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fetchStrategyMock;

    /**
     * @var \Magento\Framework\Event\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventManagerMock;

    /**
     * @var \Magento\Eav\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    /**
     * @var \Magento\Framework\App\ResourceConnection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $coreResourceMock;

    /**
     * @var \Magento\Eav\Model\EntityFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityFactoryMock;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Helper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceHelperMock;

    /**
     * @var \Magento\Framework\Validator\UniversalFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $validatorFactoryMock;

    /**
     * @var \Magento\Framework\DB\Statement\Pdo\Mysql|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $statementMock;

    protected function setUp()
    {
        $this->coreEntityFactoryMock = $this->createMock(\Magento\Framework\Data\Collection\EntityFactory::class);
        $this->loggerMock = $this->createMock(\Psr\Log\LoggerInterface::class);
        $this->fetchStrategyMock = $this->createMock(
            \Magento\Framework\Data\Collection\Db\FetchStrategyInterface::class
        );
        $this->eventManagerMock = $this->createMock(\Magento\Framework\Event\ManagerInterface::class);
        $this->configMock = $this->createMock(\Magento\Eav\Model\Config::class);
        $this->coreResourceMock = $this->createMock(\Magento\Framework\App\ResourceConnection::class);
        $this->resourceHelperMock = $this->createMock(\Magento\Eav\Model\ResourceModel\Helper::class);
        $this->validatorFactoryMock = $this->createMock(\Magento\Framework\Validator\UniversalFactory::class);
        $this->entityFactoryMock = $this->createMock(\Magento\Eav\Model\EntityFactory::class);
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface|\PHPUnit_Framework_MockObject_MockObject */
        $connectionMock = $this->createMock(\Magento\Framework\DB\Adapter\Pdo\Mysql::class);
        $this->statementMock = $this->createPartialMock(\Magento\Framework\DB\Statement\Pdo\Mysql::class, ['fetch']);
        /** @var $selectMock \Magento\Framework\DB\Select|\PHPUnit_Framework_MockObject_MockObject */
        $selectMock = $this->createMock(\Magento\Framework\DB\Select::class);
        $this->coreEntityFactoryMock->expects(
            $this->any()
        )->method(
            'create'
        )->will(
            $this->returnCallback([$this, 'getMagentoObject'])
        );
        $connectionMock->expects($this->any())->method('select')->will($this->returnValue($selectMock));
        $connectionMock->expects($this->any())->method('query')->willReturn($this->statementMock);

        $this->coreResourceMock->expects(
            $this->any()
        )->method(
            'getConnection'
        )->will(
            $this->returnValue($connectionMock)
        );
        $entityMock = $this->createMock(\Magento\Eav\Model\Entity\AbstractEntity::class);
        $entityMock->expects($this->any())->method('getConnection')->will($this->returnValue($connectionMock));
        $entityMock->expects($this->any())->method('getDefaultAttributes')->will($this->returnValue([]));
        $entityMock->expects($this->any())->method('getLinkField')->willReturn('entity_id');

        $attributeMock = $this->createMock(\Magento\Eav\Model\Attribute::class);
        $attributeMock->expects($this->any())->method('isStatic')->willReturn(false);
        $attributeMock->expects($this->any())->method('getAttributeCode')->willReturn(self::ATTRIBUTE_CODE);
        $attributeMock->expects($this->any())->method('getBackendTable')->willReturn('eav_entity_int');
        $attributeMock->expects($this->any())->method('getBackendType')->willReturn('int');
        $attributeMock->expects($this->any())->method('getId')->willReturn(self::ATTRIBUTE_ID_STRING);

        $entityMock
            ->expects($this->any())
            ->method('getAttribute')
            ->with(self::ATTRIBUTE_CODE)
            ->willReturn($attributeMock);

        $this->configMock
            ->expects($this->any())
            ->method('getAttribute')
            ->with(null, self::ATTRIBUTE_CODE)
            ->willReturn($attributeMock);

        $this->validatorFactoryMock->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            'test_entity_model' // see \Magento\Eav\Test\Unit\Model\Entity\Collection\AbstractCollectionStub
        )->will(
            $this->returnValue($entityMock)
        );

        $this->model = new AbstractCollectionStub(
            $this->coreEntityFactoryMock,
            $this->loggerMock,
            $this->fetchStrategyMock,
            $this->eventManagerMock,
            $this->configMock,
            $this->coreResourceMock,
            $this->entityFactoryMock,
            $this->resourceHelperMock,
            $this->validatorFactoryMock,
            null
        );
    }

    public function tearDown()
    {
        $this->model = null;
    }

    /**
     * Test method \Magento\Eav\Model\Entity\Collection\AbstractCollection::load
     */
    public function testLoad()
    {
        $this->fetchStrategyMock
            ->expects($this->once())
            ->method('fetchAll')
            ->will($this->returnValue([['id' => 1, 'data_changes' => true], ['id' => 2]]));

        foreach ($this->model->getItems() as $item) {
            $this->assertFalse($item->getDataChanges());
        }
    }

    /**
     * @dataProvider getItemsDataProvider
     */
    public function testClear($values, $count)
    {
        $this->fetchStrategyMock->expects($this->once())->method('fetchAll')->will($this->returnValue($values));

        $testId = array_pop($values)['id'];
        $this->assertCount($count, $this->model->getItems());
        $this->assertNotNull($this->model->getItemById($testId));
        $this->model->clear();
        $this->assertNull($this->model->getItemById($testId));
    }

    /**
     * @dataProvider getItemsDataProvider
     */
    public function testRemoveAllItems($values, $count)
    {
        $this->fetchStrategyMock->expects($this->once())->method('fetchAll')->will($this->returnValue($values));

        $testId = array_pop($values)['id'];
        $this->assertCount($count, $this->model->getItems());
        $this->assertNotNull($this->model->getItemById($testId));
        $this->model->removeAllItems();
        $this->assertNull($this->model->getItemById($testId));
    }

    /**
     * @dataProvider getItemsDataProvider
     */
    public function testRemoveItemByKey($values, $count)
    {
        $this->fetchStrategyMock->expects($this->once())->method('fetchAll')->will($this->returnValue($values));

        $testId = array_pop($values)['id'];
        $this->assertCount($count, $this->model->getItems());
        $this->assertNotNull($this->model->getItemById($testId));
        $this->model->removeItemByKey($testId);
        $this->assertCount($count - 1, $this->model->getItems());
        $this->assertNull($this->model->getItemById($testId));
    }

    /**
     * @dataProvider getItemsDataProvider
     */
    public function testAttributeIdIsInt($values)
    {
        $this->resourceHelperMock->expects($this->any())->method('getLoadAttributesSelectGroups')->willReturn([]);
        $this->fetchStrategyMock->expects($this->any())->method('fetchAll')->will($this->returnValue($values));
        $selectMock = $this->coreResourceMock->getConnection()->select();
        $selectMock->expects($this->any())->method('from')->willReturn($selectMock);
        $selectMock->expects($this->any())->method('join')->willReturn($selectMock);
        $selectMock->expects($this->any())->method('where')->willReturn($selectMock);
        $selectMock->expects($this->any())->method('columns')->willReturn($selectMock);

        $this->model
            ->addAttributeToSelect(self::ATTRIBUTE_CODE)
            ->_loadEntities()
            ->_loadAttributes();

        $_selectAttributesActualValue = $this->readAttribute($this->model, '_selectAttributes');

        $this->assertAttributeEquals(
            [self::ATTRIBUTE_CODE => self::ATTRIBUTE_ID_STRING],
            '_selectAttributes',
            $this->model
        );
        $this->assertSame($_selectAttributesActualValue[self::ATTRIBUTE_CODE], self::ATTRIBUTE_ID_INT);
    }

    /**
     * @return array
     */
    public function getItemsDataProvider()
    {
        return [
            ['values' => [['id' => 1]], 'count' => 1],
            ['values' => [['id' => 1], ['id' => 2]], 'count' => 2],
            ['values' => [['id' => 2], ['id' => 3]], 'count' => 2]
        ];
    }

    /**
     * @return \Magento\Framework\DataObject
     */
    public function getMagentoObject()
    {
        return new \Magento\Framework\DataObject();
    }
}
