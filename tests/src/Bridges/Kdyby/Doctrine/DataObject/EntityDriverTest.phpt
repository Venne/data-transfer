<?php

namespace VenneTests\Bridges\Kdyby\Doctrine\DataTransfer;

use Kdyby\Annotations\DI\AnnotationsExtension;
use Kdyby\Console\DI\ConsoleExtension;
use Kdyby\Doctrine\DI\OrmExtension;
use Kdyby\Events\DI\EventsExtension;
use Nette\Caching\Cache;
use Tester\Assert;
use Venne\Bridges\Kdyby\Doctrine\DataTransfer\EntityDriver;

require __DIR__ . '/../../../../bootstrap.php';
require __DIR__ . '/Article.php';

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class EntityDriverTest extends \Tester\TestCase
{

	/** @var \Kdyby\Doctrine\EntityManager */
	private $entityManager;

	/** @var \Venne\Bridges\Kdyby\Doctrine\DataTransfer\EntityDriver */
	private $driver;

	protected function setUp()
	{
		$configurator = new \Nette\Configurator;

		@mkdir(TEMP_DIR . '/log');
		@mkdir(TEMP_DIR . '/temp');

		$configurator->setDebugMode(true);
		$configurator->enableDebugger(TEMP_DIR . '/log');
		$configurator->setTempDirectory(TEMP_DIR . '/temp');
		$configurator->onCompile[] = function ($config, \Nette\DI\Compiler $compiler) {
			$compiler->addExtension('annotations', new AnnotationsExtension());
			$compiler->addExtension('console', new ConsoleExtension());
			$compiler->addExtension('events', new EventsExtension());
			$compiler->addExtension('doctrine', new OrmExtension());
		};

		$configurator->addConfig(__DIR__ . '/config/config.neon');
		$container = $configurator->createContainer();

		$this->entityManager = $container->getByType('Doctrine\\ORM\\EntityManager');
		$this->driver = new EntityDriver($this->entityManager);

		Assert::type('Doctrine\\ORM\\EntityManager', $this->entityManager);
		Assert::type('Venne\\Bridges\\Kdyby\\Doctrine\\DataTransfer\\EntityDriver', $this->driver);
	}

	public function testGetValuesByObject()
	{
		$entity = new Article();
		$entity->setId(1);
		$entity->setName('foo');

		Assert::equal(array('id' => 1, 'name' => 'foo'), $this->driver->getValuesByObject($entity, array('id', 'name')));
		Assert::equal(array('id' => 1, 'name' => 'foo'), $this->driver->getValuesByObject($entity, array('id', 'name', 'wrong')));
	}

	public function testGetPrimaryKeyByObject()
	{
		$entity = new Article();
		$entity->setId(1);
		$entity->setName('foo');

		Assert::same(
			array('class' => 'VenneTests\Bridges\Kdyby\Doctrine\DataTransfer\Article', 'primaryKey' => 1),
			$this->driver->getPrimaryKeyByObject($entity)
		);
	}

	public function testGetCacheDependenciesByObject()
	{
		$entity = new Article();
		$entity->setId(1);
		$entity->setName('foo');

		Assert::same(
			array(Cache::TAGS => array('VenneTests\Bridges\Kdyby\Doctrine\DataTransfer\Article#1')),
			$this->driver->getCacheDependenciesByObject($entity)
		);
	}

	public function testGetObjectByPrimaryKey()
	{
		$schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
		$classes = $this->entityManager->getMetadataFactory()->getAllMetadata();
		$schemaTool->createSchema($classes);

		$entity = new Article();
		$entity->setId(1);
		$entity->setName('foo');

		$this->entityManager->persist($entity);
		$this->entityManager->flush();

		Assert::equal(
			$entity,
			$this->driver->getObjectByPrimaryKey(array('class' => 'VenneTests\Bridges\Kdyby\Doctrine\DataTransfer\Article', 'primaryKey' => 1))
		);
		Assert::null(
			$this->driver->getObjectByPrimaryKey(array('class' => 'VenneTests\Bridges\Kdyby\Doctrine\DataTransfer\Article', 'primaryKey' => 2))
		);
	}

}

$testCase = new EntityDriverTest();
$testCase->run();
