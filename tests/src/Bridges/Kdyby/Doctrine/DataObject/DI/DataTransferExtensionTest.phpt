<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace VenneTests\Bridges\Kdyby\Doctrine\DataTransfer\DI;

use Kdyby\Annotations\DI\AnnotationsExtension;
use Kdyby\Console\DI\ConsoleExtension;
use Kdyby\Doctrine\DI\OrmExtension;
use Kdyby\Events\DI\EventsExtension;
use Nette\Configurator;
use Nette\DI\Compiler;
use Tester\Assert;
use Venne\Bridges\Kdyby\Doctrine\DataTransfer\CacheInvalidationListener;
use Venne\Bridges\Kdyby\Doctrine\DataTransfer\DI\DataTransferExtension as KdybyDataTransferExtension;
use Venne\DataTransfer\DI\DataTransferExtension;
use Venne\Packages\DI\PackagesExtension;

require __DIR__ . '/../../../../../bootstrap.php';
require __DIR__ . '/../Article.php';

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
			$compiler->addExtension('kdybyDataTransfer', new KdybyDataTransferExtension());
		};

		$configurator->addConfig(__DIR__ . '/config/config.neon');
		$container = $configurator->createContainer();

		Assert::type(CacheInvalidationListener::class, $container->getService('kdybyDataTransfer.cacheInvalidationListener'));
	}

}

$testCache = new DataTransferExtensionTest();
$testCache->run();
