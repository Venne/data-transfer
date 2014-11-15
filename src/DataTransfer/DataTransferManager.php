<?php

namespace Venne\DataTransfer;

use Nette\Caching\Cache;
use Nette\InvalidArgumentException;
use Nette\Utils\Arrays;
use Nette\Utils\Callback;

class DataTransferManager extends \Nette\Object
{

	/** @var \Venne\DataTransfer\Driver */
	private $driver;

	/** @var \Nette\Caching\Cache|null */
	private $cache;

	/**
	 * @param \Venne\DataTransfer\Driver $driver
	 * @param \Nette\Caching\Cache|null $cache
	 */
	public function __construct(Driver $driver, Cache $cache = null)
	{
		$this->driver = $driver;
		$this->cache = $cache;
	}

	/**
	 * @return string
	 */
	public static function getClassName()
	{
		return get_called_class();
	}

	/**
	 * @param string $class
	 * @param mixed[]|callable $values
	 * @return \Venne\DataTransfer\DataTransferQuery
	 */
	public function createQuery($class, $values)
	{
		return new DataTransferQuery($this, $class, $this->driver, $values, $this->cache);
	}

	/**
	 * @param \Venne\DataTransfer\DataTransferQuery $query
	 * @return \Venne\DataTransfer\DataTransferObject
	 */
	public function fetchObject(DataTransferQuery $query)
	{
		$class = '\\' . trim($query->getClass(), '\\');
		$values = $query->getValues();

		if (!class_exists($class)) {
			throw new InvalidArgumentException(sprintf('Class \'%s\' does not exist.', $class));
		}

		if (!is_subclass_of($class, 'Venne\DataTransfer\DataTransferObject')) {
			throw new InvalidArgumentException(sprintf('Class \'%s\' must inherit from \'Venne\DataTransfer\DataTransferObject\'.', $class));
		}

		if (!$query->isCacheEnabled()) {
			return new $class(function () use (& $values, $class) {
				$values = is_callable($values) ? Callback::invokeArgs($values) : $values;

				return $this->driver->getValuesByObject($values, $class::getKeys());
			});
		}

		$cacheDependencies = $query->getCacheDependencies();
		$primaryKeysCacheKey = $this->formatPrimaryKeysCacheKey($class, $query->getCacheKey());

		$primaryKey = $this->cache->load(
			$primaryKeysCacheKey,
			function (& $dependencies) use (& $values, & $cacheDependencies) {
				$values = is_callable($values) ? Callback::invoke($values) : $values;
				$dependencies = Arrays::mergeTree((array) $cacheDependencies, $this->driver->getCacheDependenciesByObject($values));

				return $this->driver->getPrimaryKeyByObject($values);
			}
		);

		$loadedValues = $this->cache->load(
			array($this->formatValuesCacheKey($class, $primaryKey), $primaryKeysCacheKey),
			function (& $dependencies) use (& $values, & $cacheDependencies, $class) {
				$values = is_callable($values) ? Callback::invoke($values) : $values;
				$dependencies = Arrays::mergeTree((array) $cacheDependencies, $this->driver->getCacheDependenciesByObject($values));

				/** @var DataTransferObject $dto */
				$dto = new $class($this->driver->getValuesByObject($values, $class::getKeys()));
				return $dto->toArray();
			}
		);

		return new $class($loadedValues, true);
	}

	/**
	 * @param \Venne\DataTransfer\DataTransferQuery $query
	 * @return \Venne\DataTransfer\DataTransferObject[]
	 */
	public function fetchIterator(DataTransferQuery $query)
	{
		$class = '\\' . trim($query->getClass(), '\\');
		$rows = $query->getValues();

		if (!class_exists($class)) {
			throw new InvalidArgumentException(sprintf('Class \'%n\' does not exist.', $class));
		}

		if (!is_subclass_of($class, 'Venne\DataTransfer\DataTransferObject')) {
			throw new InvalidArgumentException(sprintf('Class \'%s\' must inherit from \'Venne\DataTransfer\DataTransferObject\'.', $class));
		}

		if (!$query->isCacheEnabled()) {
			return new DataTransferObjectIterator($class, function () use (& $rows, $class) {
				$rows = is_callable($rows) ? Callback::invoke($rows) : $rows;
				$rowsData = array();

				foreach ($rows as $row) {
					$rowsData[] = $this->driver->getValuesByObject($row, $class::getKeys());
				}

				return $rowsData;
			});
		}

		$cacheDependencies = $query->getCacheDependencies();
		$primaryKeysCacheKey = $this->formatPrimaryKeysCacheKey(sprintf('%s[]', $class), $query->getCacheKey());

		$primaryKeys = $this->cache->load(
			$primaryKeysCacheKey,
			function (&$dependencies) use (& $rows, & $cacheDependencies, $class) {
				$rows = is_callable($rows) ? Callback::invoke($rows) : $rows;
				$primaryKeys = array();

				foreach ($rows as $row) {
					$primaryKeys[] = $this->driver->getPrimaryKeyByObject($row);
					$dependencies = Arrays::mergeTree((array) $dependencies, $this->driver->getCacheDependenciesByObject($row));
				}

				return $primaryKeys;
			}
		);

		$loadedValues = array();
		foreach ($primaryKeys as $index => $primaryKey) {
			$loadedValues[] = $this->cache->load(
				array($this->formatValuesCacheKey($class, $primaryKey), $primaryKeysCacheKey),
				function (& $dependencies) use (& $rows, & $cacheDependencies, $class, $index) {
					$rows = is_callable($rows) ? Callback::invoke($rows) : $rows;
					$dependencies = Arrays::mergeTree((array) $dependencies, $this->driver->getCacheDependenciesByObject($rows[$index]));

					$row = $rows[$index];
					$row = is_callable($row) ? Callback::invoke($row) : $row;

					/** @var DataTransferObject $dto */
					$dto = new $class($this->driver->getValuesByObject($row, $class::getKeys()));
					return $dto->toArray();
				}
			);
		}

		return new DataTransferObjectIterator($class, $loadedValues);
	}

	/**
	 * @param string $class
	 * @param mixed $key
	 * @return string
	 */
	protected function formatPrimaryKeysCacheKey($class, $key)
	{
		return sprintf('[%s]%s', $class, serialize($key));
	}

	/**
	 * @param string $class
	 * @param string $primaryKey
	 * @return string
	 */
	protected function formatValuesCacheKey($class, $primaryKey)
	{
		return sprintf('values:[%s]%s', $class, serialize($primaryKey));
	}

}
