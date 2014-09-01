<?php

namespace Venne\DataTransfer;

interface Driver
{

	/**
	 * @param mixed $object
	 * @param string[] $keys
	 * @return mixed[]
	 */
	public function getValuesByObject($object, $keys);

	/**
	 * @param mixed $primaryKey
	 * @return mixed[]
	 */
	public function getObjectByPrimaryKey($primaryKey);

	/**
	 * @param mixed $object
	 * @return string[]
	 */
	public function getPrimaryKeyByObject($object);

	/**
	 * @param mixed $object
	 * @return mixed[]
	 */
	public function getCacheDependenciesByObject($object);

}
