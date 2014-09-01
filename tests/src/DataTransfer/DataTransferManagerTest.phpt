<?php

namespace VenneTests\DataTransfer;

use Nette\Caching\Cache;
use Nette\Caching\Storages\DevNullStorage;
use Nette\Caching\Storages\FileJournal;
use Nette\Caching\Storages\FileStorage;
use Tester\Assert;
use Venne\DataTransfer\DataTransferManager;

require __DIR__ . '/../bootstrap.php';

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class DataTransferManagerTest extends \Tester\TestCase
{

	public function testCreateObject()
	{
		$mapper = new Driver();
		$dataTransferManager = new DataTransferManager(
			$mapper,
			new Cache(new DevNullStorage(), 'dataTransfer')
		);

		for ($x = 1; $x <= 4; $x++) {
			$dataTransferObject = $dataTransferManager->createObject(DataTransfer::getClassName(), function () use ($mapper) {
				return $mapper->repository->find(1);
			});

			Assert::type('VenneTests\DataTransfer\DataTransfer', $dataTransferObject);
			Assert::equal(array('id' => 1, 'name' => md5(1)), $dataTransferObject->toArray());
			Assert::same($x, $mapper->repository->counter);
		}
	}

	public function testCreateObjectWithCache()
	{
		$mapper = new Driver();
		$dataTransferManager = new DataTransferManager(
			$mapper,
			new Cache(new FileStorage(TEMP_DIR, new FileJournal(TEMP_DIR)), 'dataTransfer')
		);

		for ($x = 1; $x <= 4; $x++) {
			$dataTransferObject = $dataTransferManager->createObject(DataTransfer::getClassName(), function () use ($mapper) {
				return $mapper->repository->find(1);
			});

			Assert::type('VenneTests\DataTransfer\DataTransfer', $dataTransferObject);
			Assert::equal(array('id' => 1, 'name' => md5(1)), $dataTransferObject->toArray());
			Assert::same(1, $mapper->repository->counter);
		}
	}

	public function testCreateIterator()
	{
		$mapper = new Driver();
		$dataTransferManager = new DataTransferManager(
			$mapper,
			null
		);

		for ($x = 1; $x <= 4; $x++) {
			$dataTransferObjectIterator = $dataTransferManager->createIterator(DataTransfer::getClassName(), function () use ($mapper) {
				return $mapper->repository->findAll();
			});

			Assert::type('Venne\DataTransfer\DataTransferObjectIterator', $dataTransferObjectIterator);
			foreach ($dataTransferObjectIterator as $key => $dataTransferObject) {
				Assert::same($key + 1, $dataTransferObject->id);
				Assert::equal(md5($key + 1), $dataTransferObject->name);
				Assert::equal(array('id' => $key + 1, 'name' => md5($key + 1)), $dataTransferObject->toArray());
			}
			Assert::same($x, $mapper->repository->counter);
		}
	}

	public function testCreateIteratorWithCache()
	{
		$mapper = new Driver();
		$dataTransferManager = new DataTransferManager(
			$mapper,
			new Cache(new FileStorage(TEMP_DIR, new FileJournal(TEMP_DIR)), 'dataTransfer')
		);

		for ($x = 1; $x <= 4; $x++) {
			$dataTransferObjectIterator = $dataTransferManager->createIterator(DataTransfer::getClassName(), function () use ($mapper) {
				return $mapper->repository->findAll();
			});

			Assert::type('Venne\DataTransfer\DataTransferObjectIterator', $dataTransferObjectIterator);
			foreach ($dataTransferObjectIterator as $key => $dataTransferObject) {
				Assert::same($key + 1, $dataTransferObject->id);
				Assert::equal(md5($key + 1), $dataTransferObject->name);
				Assert::equal(array('id' => $key + 1, 'name' => md5($key + 1)), $dataTransferObject->toArray());
			}
			Assert::same(1, $mapper->repository->counter);
		}

		for ($x = 1; $x <= 4; $x++) {
			$dataTransferObjectIterator = $dataTransferManager->createIterator(DataTransfer::getClassName(), function () use ($mapper) {
				return $mapper->repository->findAll();
			}, '1');

			Assert::type('Venne\DataTransfer\DataTransferObjectIterator', $dataTransferObjectIterator);
			foreach ($dataTransferObjectIterator as $key => $dataTransferObject) {
				Assert::same($key + 1, $dataTransferObject->id);
				Assert::equal(md5($key + 1), $dataTransferObject->name);
				Assert::equal(array('id' => $key + 1, 'name' => md5($key + 1)), $dataTransferObject->toArray());
			}
			Assert::same(2, $mapper->repository->counter);
		}
	}

}

/**
 * @property-read integer $id
 * @property-read string $name
 */
class DataTransfer extends \Venne\DataTransfer\DataTransferObject
{

}

class Entity
{

	/** @var int */
	private $id;

	/** @var string */
	private $name;

	/**
	 * @param int $id
	 * @param string $name
	 */
	public function __construct($id, $name)
	{
		$this->id = $id;
		$this->name = $name;
	}

	public function getId()
	{
		return $this->id;
	}

	public function getName()
	{
		return $this->name;
	}

}

class Driver implements \Venne\DataTransfer\Driver
{

	public $repository;

	public function __construct()
	{
		$this->repository = new Repository();
	}

	/**
	 * @param mixed $object
	 * @param string[] $keys
	 * @return mixed[]
	 */
	public function getValuesByObject($object, $keys)
	{
		return array(
			'id' => $object->getId(),
			'name' => $object->getName(),
		);
	}

	/**
	 * @param mixed $primaryKey
	 * @return mixed[]
	 */
	public function getObjectByPrimaryKey($primaryKey)
	{
		return $this->repository->find($primaryKey);
	}

	/**
	 * @param mixed $object
	 * @return string[]
	 */
	public function getPrimaryKeyByObject($object)
	{
		return array(
			'class' => get_class($object),
			'primaryKey' => $object->getId(),
		);
	}

	/**
	 * @param mixed $object
	 * @return mixed[]
	 */
	public function getCacheDependenciesByObject($object)
	{
		return array(
			Cache::TAGS => $this->getPrimaryKeyByObject($object),
		);
	}

}

class Repository
{

	public $counter = 0;

	private $entities = array();

	public function find($id)
	{
		$this->counter++;

		if (!isset($this->entities[$id])) {
			$this->entities[$id] = new Entity($id, md5($id));
		}

		return $this->entities[$id];
	}

	public function findAll()
	{
		$counter = $this->counter;

		$entities = array();
		$entities[] = $this->find(1);
		$entities[] = $this->find(2);
		$entities[] = $this->find(3);

		$this->counter = $counter + 1;

		return $entities;
	}

}

$testCase = new DataTransferManagerTest;
$testCase->run();
