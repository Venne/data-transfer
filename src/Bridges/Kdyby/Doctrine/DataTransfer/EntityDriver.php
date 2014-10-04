<?php

namespace Venne\Bridges\Kdyby\Doctrine\DataTransfer;

use Kdyby\Doctrine\Entities\BaseEntity;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\MemberAccessException;
use Nette\Caching\Cache;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class EntityDriver extends \Nette\Object implements \Venne\DataTransfer\Driver
{

	const ENTITY_CLASS = 'class';
	const PRIMARY_KEY = 'primaryKey';

	/** @var \Kdyby\Doctrine\EntityManager */
	private $entityManager;

	/** @var \Symfony\Component\PropertyAccess\PropertyAccessorInterface */
	private $propertyAccessor;

	public function __construct(EntityManager $entityManager, PropertyAccessorInterface $propertyAccessor = null)
	{
		$this->entityManager = $entityManager;
		$this->propertyAccessor = $propertyAccessor !== null
			? $propertyAccessor
			: new PropertyAccessor(true, true);
	}

	/**
	 * @return string
	 */
	public static function getClassName()
	{
		return get_called_class();
	}

	/**
	 * @param \Kdyby\Doctrine\Entities\BaseEntity $object
	 * @param string[] $keys
	 * @return mixed[]
	 */
	public function getValuesByObject($object, $keys)
	{
		$values = array();
		$metadata = $this->entityManager->getClassMetadata(get_class($object));

		foreach ($metadata->getFieldNames() as $columnName) {
			try {
				if ($this->propertyAccessor->isReadable($object, $columnName)) {
					$values[$columnName] = $this->propertyAccessor->getValue($object, $columnName);
				}
			} catch (MemberAccessException $e) {

			}
		}

		foreach ($metadata->getAssociationMappings() as $association) {
			$columnName = $association['fieldName'];

			try {
				if ($this->propertyAccessor->isReadable($object, $columnName)) {
					$values[$columnName] = $this->propertyAccessor->getValue($object, $columnName);
				}
			} catch (MemberAccessException $e) {

			}
		}

		return $values;
	}

	/**
	 * @param mixed $primaryKey
	 * @return mixed[]
	 */
	public function getObjectByPrimaryKey($primaryKey)
	{
		$repository = $this->entityManager->getRepository($primaryKey[self::ENTITY_CLASS]);

		return $repository->find($primaryKey[self::PRIMARY_KEY]);
	}

	/**
	 * @param mixed $object
	 * @return string[]
	 */
	public function getPrimaryKeyByObject($object)
	{
		return array(
			self::ENTITY_CLASS => $this->entityManager->getClassMetadata(get_class($object))->name,
			self::PRIMARY_KEY => $this->entityManager->getUnitOfWork()->getSingleIdentifierValue($object),
		);
	}

	/**
	 * @param mixed $object
	 * @return mixed[]
	 */
	public function getCacheDependenciesByObject($object)
	{
		$primaryKey = $this->getPrimaryKeyByObject($object);

		return array(
			Cache::TAGS => array(
				Helpers::formatCacheTag(get_class($object), $primaryKey[self::PRIMARY_KEY])
			),
		);
	}

}
