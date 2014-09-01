<?php

namespace Venne\DataTransfer;

use Nette\Caching\Cache;
use Nette\InvalidArgumentException;
use Nette\Utils\Arrays;
use Nette\Utils\Callback;

class DataTransferManager extends \Nette\Object
{

	/** @var \Venne\DataTransfer\Driver */
	private $mapper;

	/** @var \Nette\Caching\Cache|null */
	private $cache;

	/**
	 * @param \Venne\DataTransfer\Driver $mapper
	 * @param \Nette\Caching\Cache|null $cache
	 */
	public function __construct(Driver $mapper, Cache $cache = null)
	{
		$this->mapper = $mapper;
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
	 * @param mixed|callable $values
	 * @param mixed $key
	 * @return \Venne\DataTransfer\DataTransferObject
	 */
	public function createObject($class, $values, $key = null)
	{
		$class = '\\' . trim($class, '\\');

		if (!class_exists($class)) {
			throw new InvalidArgumentException(sprintf('Class \'%s\' does not exist.', $class));
		}

		if (!is_subclass_of($class, 'Venne\DataTransfer\DataTransferObject')) {
			throw new InvalidArgumentException(sprintf('Class \'%s\' must inherit from \'Venne\DataTransfer\DataTransferObject\'.', $class));
		}

		if ($this->cache === null) {
			return new $class(function () use (& $values, $class) {
				$values = is_callable($values) ? Callback::invokeArgs($values) : $values;

				return $this->mapper->getValuesByObject($values, $class::getKeys());
			});
		}

		$primaryKey = $this->cache->load(
			$this->formatPrimaryKeysCacheKey($class, $key),
			function (& $dependencies) use (& $values) {
				$values = is_callable($values) ? Callback::invokeArgs($values, array(& $dependencies)) : $values;
				$dependencies = Arrays::mergeTree((array) $dependencies, $this->mapper->getCacheDependenciesByObject($values));

				return $this->mapper->getPrimaryKeyByObject($values);
			}
		);

		$loadedValues = $this->cache->load(
			$this->formatValuesCacheKey($class, $primaryKey),
			function (& $dependencies) use (& $values, $class) {
				$values = is_callable($values) ? Callback::invokeArgs($values, array(& $dependencies)) : $values;
				$dependencies = Arrays::mergeTree((array) $dependencies, $this->mapper->getCacheDependenciesByObject($values));

				return $this->mapper->getValuesByObject($values, $class::getKeys());
			}
		);

		return new $class($loadedValues);
	}

	/**
	 * @param string $class
	 * @param mixed|callable $rows
	 * @param mixed $key
	 * @return \Venne\DataTransfer\DataTransferObject[]
	 */
	public function createIterator($class, $rows, $key = null)
	{
		$class = '\\' . trim($class, '\\');

		if (!class_exists($class)) {
			throw new InvalidArgumentException(sprintf('Class \'%n\' does not exist.', $class));
		}

		if (!is_subclass_of($class, 'Venne\DataTransfer\DataTransferObject')) {
			throw new InvalidArgumentException(sprintf('Class \'%s\' must inherit from \'Venne\DataTransfer\DataTransferObject\'.', $class));
		}

		if ($this->cache === null) {
			return new DataTransferObjectIterator($class, function () use ($rows, $class) {
				$rows = is_callable($rows) ? Callback::invoke($rows) : $rows;
				$rowsData = array();

				foreach ($rows as $row) {
					$rowsData[] = $this->mapper->getValuesByObject($row, $class::getKeys());
				}

				return $rowsData;
			});
		}

		$primaryKeys = $this->cache->load(
			$this->formatPrimaryKeysCacheKey(sprintf('%s[]', $class), $key),
			function (&$dependencies) use (& $rows, $class) {
				$rows = is_callable($rows) ? Callback::invokeArgs($rows, array(& $dependencies)) : $rows;
				$primaryKeys = array();

				foreach ($rows as $row) {
					$primaryKeys[] = $this->mapper->getPrimaryKeyByObject($row);
					$dependencies = Arrays::mergeTree((array) $dependencies, $this->mapper->getCacheDependenciesByObject($row));
				}

				return $primaryKeys;
			}
		);

		$loadedValues = array();
		foreach ($primaryKeys as $index => $primaryKey) {
			$loadedValues[] = $this->cache->load(
				$this->formatValuesCacheKey($class, $primaryKey),
				function (& $dependencies) use (& $rows, $class, $index) {
					$rows = is_callable($rows) ? Callback::invokeArgs($rows, array(& $dependencies)) : $rows;
					$dependencies = Arrays::mergeTree((array) $dependencies, $this->mapper->getCacheDependenciesByObject($rows[$index]));

					return $this->mapper->getValuesByObject($rows[$index], $class::getKeys());
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
