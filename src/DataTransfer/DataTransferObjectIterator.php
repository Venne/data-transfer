<?php

namespace Venne\DataTransfer;

use Nette\Utils\Callback;

class DataTransferObjectIterator extends \Nette\Object implements \Iterator, \Countable, \Serializable
{

	/** @var string */
	private $class;

	/** @var mixed[] */
	private $rows;

	/** @var \Venne\DataTransfer\DataTransferObject[] */
	private $objects = array();

	/** @var int|null */
	private $count;

	/** @var int */
	private $position = 0;

	/**
	 * @param string $class
	 * @param mixed[]|callable $rows
	 * @param int|null $count
	 */
	public function __construct($class, $rows, $count = null)
	{
		if (!is_array($rows) && !is_callable($rows)) {
			throw new \Nette\InvalidArgumentException(sprintf('Rows must be array of values or callable array source, %s given.', gettype($rows)));
		}

		$this->class = '\\' . trim($class, '\\');;
		$this->rows = $rows;
		$this->count = $count;
	}

	/**
	 * @return string
	 */
	public static function getClassName()
	{
		return get_called_class();
	}

	private function loadRows()
	{
		if (is_callable($this->rows)) {
			$this->rows = Callback::invoke($this->rows);

			if (!is_array($this->rows)) {
				throw new \Nette\InvalidStateException(sprintf('Rows must be array of values or callable array source, %s given.', gettype($this->rows)));
			}
		}
	}

	private function loadAllRows()
	{
		$this->loadRows();

		foreach ($this->rows as $key => $row) {
			$this->createObject($key);
		}
	}

	/**
	 * @param int $key
	 */
	private function createObject($key)
	{
		if (!isset($this->objects[$key])) {
			$this->objects[$key] = new $this->class($this->rows[$key]);
		}
	}

	/**
	 * @return int
	 */
	public function count()
	{
		if ($this->count !== null) {
			return $this->count;
		}

		$this->loadRows();

		return count($this->rows);
	}

	/**
	 * @return \Venne\DataTransfer\DataTransferObject
	 */
	public function current()
	{
		$this->loadRows();
		$this->createObject($this->position);

		return $this->objects[$this->position];
	}

	public function next()
	{
		$this->position++;
	}

	/**
	 * @return int
	 */
	public function key()
	{
		return $this->position;
	}

	/**
	 * @return bool
	 */
	public function valid()
	{
		$this->loadRows();

		return isset($this->rows[$this->position]);
	}

	public function rewind()
	{
		$this->position = 0;
	}

	/**
	 * @return string
	 */
	public function serialize()
	{
		$this->loadAllRows();

		return serialize(array(
			$this->objects,
			$this->count,
		));
	}

	/**
	 * @param string $serialized
	 */
	public function unserialize($serialized)
	{
		$values = unserialize($serialized);

		$this->objects = $values[0];
		$this->count = $values[1];
	}

	/**
	 * @return \Venne\DataTransfer\DataTransferObject[]
	 */
	public function toArray()
	{
		return iterator_to_array($this);
	}

}
