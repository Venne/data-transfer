<?php

namespace Venne\DataTransfer;

use Nette\Reflection\ClassType;
use Nette\Utils\Callback;
use Nette\Utils\Strings;
use Nette\Utils\Validators;

abstract class DataTransferObject extends \Nette\Object
{

	const META_READ = 'read';

	const META_WRITE = 'write';

	const META_TYPE = 'type';

	/** @var mixed[][][] */
	private static $metadata = array();

	/** @var mixed[]|callable */
	private $values;

	/** @var mixed[] */
	private $validItems = array();

	/**
	 * @param mixed[]|callable $values
	 * @param bool $valuesAreValid
	 */
	public function __construct($values, $valuesAreValid = false)
	{
		if (!is_array($values) && !is_callable($values)) {
			throw new \Nette\InvalidArgumentException(sprintf('Values must be array or callable array source, %s given.', gettype($values)));
		}

		if ($valuesAreValid) {
			$this->validItems = $values;
		} else {
			$this->values = $values;
		}
	}

	/**
	 * @return string
	 */
	public static function getClassName()
	{
		return get_called_class();
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function &__get($name)
	{
		$value = $this->getValue($name);

		return $value;
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	protected function getValue($name)
	{
		$this->loadValues();

		if (array_key_exists($name, $this->validItems)) {
			return $this->validItems[$name];
		}

		if (!static::hasKey($name)) {
			throw new \Nette\MemberAccessException(sprintf('Cannot read property %s::$%s.', get_class($this), $name));
		}

		$metadata = self::getMetadata();
		if (!$metadata[$name][self::META_READ]) {
			throw new \Nette\MemberAccessException(sprintf('Property %s::$%s is not readable.', get_class($this), $name));
		}

		$method = sprintf('get%s', ucfirst($name));
		if (method_exists($this, $method)) {
			$value = call_user_func(array($this, $method));
		} else {
			$value = $this->getRawValue($name);
		}

		return $this->validItems[$name] = $this->checkValue($name, $value);
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	protected function getRawValue($name)
	{
		return array_key_exists($name, $this->values) ? $this->values[$name] : null;
	}

	private function loadValues()
	{
		if (is_callable($this->values)) {
			$this->values = Callback::invoke($this->values);

			if (!is_array($this->values)) {
				throw new \Nette\InvalidStateException(sprintf('Values must be array or callable array source, %s given.', gettype($this->values)));
			}
		}
	}

	/**
	 * @return mixed[]
	 */
	public function toArray()
	{
		$values = array();

		foreach (self::getKeys() as $key) {
			$values[$key] = $this->getValue($key);
		}

		return $values;
	}

	/**
	 * @param string $key
	 * @return boolean
	 */
	public static function hasKey($key)
	{
		$metadata = self::getMetadata();

		return isset($metadata[$key]);
	}

	/**
	 * @return string[]
	 */
	public static function getKeys()
	{
		return array_keys(self::getMetadata());
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @return mixed
	 */
	private function checkValue($name, $value)
	{
		if (!$this->isValueValid($name, $value)) {
			$metadata = self::getMetadata();

			throw new \Nette\InvalidStateException(sprintf('Property %s::$%s contains wrong type [%s]. Expected is [%s].', get_called_class(), $name, is_object($value) ? get_class($value) : gettype($value), $metadata[$name][self::META_TYPE]));
		}

		return $value;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @return boolean
	 */
	private function isValueValid($name, $value)
	{
		$metadata = self::getMetadata();

		if ($metadata[$name][self::META_TYPE] === null) {
			return true;
		} else {
			$types = explode('|', $metadata[$name][self::META_TYPE]);
			foreach ($types as $key => $type) {
				$typeLength = strlen($type);
				if ($typeLength > 2 && $type[$typeLength - 2] === '[' && $type[$typeLength - 1] === ']') {
					$types[$key] = 'array';
				}
			}

			return Validators::is($value, implode('|', $types));
		}
	}

	/**
	 * @return mixed[][]
	 */
	private static function getMetadata()
	{
		$className = get_called_class();

		if (!isset(self::$metadata[$className])) {
			self::$metadata[$className] = array();
			$classReflection = new ClassType($className);
			$annotations = $classReflection->getAnnotations();

			self::$metadata[$className] += self::parseMetadataFromAnnotation($annotations, 'property', true, true);
			self::$metadata[$className] += self::parseMetadataFromAnnotation($annotations, 'property-read', true, false);
			self::$metadata[$className] += self::parseMetadataFromAnnotation($annotations, 'property-write', false, true);

		}

		return self::$metadata[$className];
	}

	/**
	 * @param \Nette\Reflection\IAnnotation[] $annotations
	 * @param string $name
	 * @param boolean $read
	 * @param boolean $write
	 * @return mixed[]
	 */
	private static function parseMetadataFromAnnotation($annotations, $name, $read = false, $write = false)
	{
		$metadata = array();

		if (isset($annotations[$name])) {
			foreach ($annotations[$name] as $annotation) {
				$annotationArray = explode(' ', $annotation);

				if (isset($annotationArray[1])) {
					if (Strings::startsWith($annotationArray[0], '$')) {
						$key = substr($annotationArray[0], 1);
						$type = null;
					} elseif (Strings::startsWith($annotationArray[1], '$')) {
						$key = substr($annotationArray[1], 1);
						$type = $annotationArray[0];
					} else {
						throw new \Nette\InvalidStateException(sprintf('Bad annotation format: \'@%s %s\'', $name, $annotation));
					}

					$metadata[$key] = array(
						self::META_READ => $read,
						self::META_WRITE => $write,
						self::META_TYPE => $type,
					);
				}
			}
		}

		return $metadata;
	}

	/**
	 * @return string
	 */
	public function serialize()
	{
		$this->loadValues();

		return serialize($this->getValues());
	}

	/**
	 * @param string $serialized
	 */
	public function unserialize($serialized)
	{
		$this->values = unserialize($serialized);
	}

}
