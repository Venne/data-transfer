<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace VenneTests\DataTransfer\DI;

use Kdyby\Annotations\DI\AnnotationsExtension;
use Kdyby\Console\DI\ConsoleExtension;
use Kdyby\Doctrine\DI\OrmExtension;
use Kdyby\Events\DI\EventsExtension;
use Nette\Configurator;
use Nette\DI\Compiler;
use Tester\Assert;
use Venne\DataTransfer\DI\DataTransferExtension;
use Venne\DataTransfer\DataTransferManager;
use Venne\Packages\DI\PackagesExtension;

require __DIR__ . '/../../bootstrap.php';

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class DataTransferExtensionTest extends \Tester\TestCase
{

	public function testRegisterTypes()
	{
		$configurator = new Configurator();

		@mkdir(TEMP_DIR . '/log');
		@mkdir(TEMP_DIR . '/temp');

		$configurator->setDebugMode(true);
		$configurator->enableDebugger(TEMP_DIR . '/log');
		$configurator->setTempDirectory(TEMP_DIR . '/temp');

		$configurator->onCompile[] = function (Configurator $configurator, Compiler $compiler) {
			$compiler->addExtension('annotations', new AnnotationsExtension());
			$compiler->addExtension('console', new ConsoleExtension());
			$compiler->addExtension('events', new EventsExtension());
			$compiler->addExtension('doctrine', new OrmExtension());
			$compiler->addExtension('dataTransfer', new DataTransferExtension());
		};

		$configurator->addConfig(__DIR__ . '/config/config.neon');
		$container = $configurator->createContainer();

		Assert::type('VenneTests\DataTransfer\DI\Driver', $container->getService('dataTransfer.driver'));
		Assert::type(DataTransferManager::getClassName(), $container->getService('dataTransfer.dataTransferManager'));
		Assert::type('Nette\Caching\Cache', $container->getService('dataTransfer.cache'));
	}

}

class Driver implements \Venne\DataTransfer\Driver
{

	public function getValuesByObject($object, $keys)
	{
	}

	public function getObjectByPrimaryKey($primaryKey)
	{
	}

	public function getPrimaryKeyByObject($object)
	{
	}

	public function getCacheDependenciesByObject($object)
	{
	}

}

$testCache = new DataTransferExtensionTest();
$testCache->run();
