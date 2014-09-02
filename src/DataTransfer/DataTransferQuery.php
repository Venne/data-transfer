<?php

namespace Venne\DataTransfer;

use Nette\Caching\Cache;

class DataTransferQuery extends \Nette\Object
{

	/** @var \Venne\DataTransfer\DataTransferManager */
	private $manager;

	/** @var string */
	private $class;

	/** @var \Venne\DataTransfer\Driver */
	private $driver;

	/** @var \Nette\Caching\Cache|null */
	private $cache;

	/** @var bool */
	private $cacheEnable;

	/** @var mixed|null */
	private $cacheKey;

	/** @var mixed[] */
	private $cacheDependencies;

	/** @var mixed[]|callable */
	private $values;

	/**
	 * @param \Venne\DataTransfer\DataTransferManager $manager
	 * @param string $class
	 * @param \Venne\DataTransfer\Driver $driver
	 * @param mixed[]|callable $values
	 * @param \Nette\Caching\Cache $cache
	 * @param bool $cacheEnable
	 * @param mixed|null $cacheKey
	 * @param mixed[]|null $cacheDependencies
	 */
	public function __construct(
		DataTransferManager $manager,
		$class,
		Driver $driver,
		$values,
		Cache $cache = null,
		$cacheEnable = false,
		$cacheKey = null,
		$cacheDependencies = null
	)
	{
		$this->manager = $manager;
		$this->class = $class;
		$this->driver = $driver;
		$this->values = $values;
		$this->cache = $cache;
		$this->cacheEnable = $cacheEnable;
		$this->cacheKey = $cacheKey;
		$this->cacheDependencies = $cacheDependencies;
	}

	/**
	 * @return \Venne\DataTransfer\DataTransferObject
	 */
	public function fetch()
	{
		return $this->manager->fetchObject($this);
	}

	/**
	 * @return \Venne\DataTransfer\DataTransferObject[]
	 */
	public function fetchAll()
	{
		return $this->manager->fetchIterator($this);
	}

	/**
	 * @param string $class
	 * @return \Venne\DataTransfer\DataTransferQuery
	 */
	public function setClass($class)
	{
		return new static(
			$this->manager,
			$class,
			$this->driver,
			$this->values,
			$this->cache,
			$this->cacheEnable,
			$this->cacheKey,
			$this->cacheDependencies
		);
	}

	/**
	 * @param mixed|null $cacheKey
	 * @param mixed[]|null $cacheDependencies
	 * @return \Venne\DataTransfer\DataTransferQuery
	 */
	public function enableCache($cacheKey = null, $cacheDependencies = null)
	{
		return new static(
			$this->manager,
			$this->class,
			$this->driver,
			$this->values,
			$this->cache,
			true,
			$cacheKey,
			$cacheDependencies
		);
	}

	/**
	 * @return \Venne\DataTransfer\DataTransferQuery
	 */
	public function disableCache()
	{
		return new static(
			$this->manager,
			$this->class,
			$this->driver,
			$this->values,
			$this->cache
		);
	}

	/**
	 * @param \Nette\Caching\Cache $cache
	 * @return \Venne\DataTransfer\DataTransferQuery
	 */
	public function setCache(Cache $cache)
	{
		return new static(
			$this->manager,
			$this->class,
			$this->driver,
			$this->values,
			$cache,
			$this->cacheEnable,
			$this->cacheKey,
			$this->cacheDependencies
		);
	}

	/**
	 * @param \Venne\DataTransfer\Driver $driver
	 * @return \Venne\DataTransfer\DataTransferQuery
	 */
	public function setDriver(Driver $driver)
	{
		return new static(
			$this->manager,
			$this->class,
			$driver,
			$this->values,
			$this->cache,
			$this->cacheEnable,
			$this->cacheKey,
			$this->cacheDependencies
		);
	}

	/**
	 * @return string
	 */
	public function getClass()
	{
		return $this->class;
	}

	/**
	 * @return bool
	 */
	public function isCacheEnabled()
	{
		return $this->cacheEnable;
	}

	/**
	 * @return Cache|null
	 */
	public function getCache()
	{
		return $this->cache;
	}

	/**
	 * @return \mixed[]
	 */
	public function getCacheDependencies()
	{
		return $this->cacheDependencies;
	}

	/**
	 * @return mixed|null
	 */
	public function getCacheKey()
	{
		return $this->cacheKey;
	}

	/**
	 * @return Driver
	 */
	public function getDriver()
	{
		return $this->driver;
	}

	/**
	 * @return callable|\mixed[]
	 */
	public function getValues()
	{
		return $this->values;
	}

}
