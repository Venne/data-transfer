<?php

/**
 * This file is part of the Venne:CMS (https://github.com/Venne)
 *
 * Copyright (c) 2011, 2012 Josef Kříž (http://www.josef-kriz.cz)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Venne\Bridges\Kdyby\Doctrine\DataTransfer\DI;

use Kdyby\Events\DI\EventsExtension;
use Venne\Bridges\Kdyby\Doctrine\DataTransfer\CacheInvalidationListener;

/**
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class DataTransferExtension extends \Nette\DI\CompilerExtension
{

	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();

		foreach ($this->compiler->getExtensions() as $extension) {
			if ($extension instanceof \Venne\DataTransfer\DI\DataTransferExtension) {
				$cache = $extension->prefix('@cache');
				break;
			}
		}

		if (!isset($cache)) {
			throw new \Nette\Utils\AssertionException('Please register the required Venne\DataTransfer\DI\DataTransferExtension to Compiler.');
		}

		$container->addDefinition($this->prefix('cacheInvalidationListener'))
			->setClass(CacheInvalidationListener::getClassName(), array($cache))
			->addTag(EventsExtension::TAG_SUBSCRIBER);
	}

}
