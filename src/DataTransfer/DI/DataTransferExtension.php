<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Venne\DataTransfer\DI;

use Kdyby\Events\DI\EventsExtension;
use Venne\Bridges\Kdyby\Doctrine\DataTransfer\CacheInvalidationListener;
use Venne\Bridges\Kdyby\Doctrine\DataTransfer\EntityDriver;
use Venne\DataTransfer\DataTransferManager;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class DataTransferExtension extends \Nette\DI\CompilerExtension
{

	/** @var string */
	private $driverClass;

	/**
	 * @param string $driverClass
	 */
	public function setDriverClass($driverClass)
	{
		$this->driverClass = $driverClass;
	}

	/**
	 * @return string[]
	 */
	private function getDefaults()
	{
		return array(
			'driver' => null, // class name
			'cache' => array(
				'namespace' => 'DataTransfer',
			),
		);
	}

	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();
		$config = $this->getConfig($this->getDefaults());

		$container->addDefinition($this->prefix('cache'))
			->setClass('Nette\Caching\Cache', array(1 => $container::literal('$namespace')))
			->setParameters(array('namespace' => $config['cache']['namespace']))
			->setAutowired(false);

		$container->addDefinition($this->prefix('dataTransferManager'))
			->setClass(DataTransferManager::class, array(1 => $this->prefix('@cache')));

		$container->addDefinition($this->prefix('cacheInvalidationListener'))
			->setClass(CacheInvalidationListener::class, array($this->prefix('@cache')))
			->addTag(EventsExtension::TAG_SUBSCRIBER);
	}

	public function beforeCompile()
	{
		$container = $this->getContainerBuilder();
		$config = $this->getConfig($this->getDefaults());

		$container->addDefinition($this->prefix('driver'))
			->setClass($this->driverClass !== null ? $this->driverClass : $config['driver']);
	}

}
