<?php

namespace Venne\Bridges\Kdyby\Doctrine\DataTransfer;

use Kdyby\Doctrine\Entities\BaseEntity;
use Kdyby\Doctrine\EntityManager;
use Nette\Caching\Cache;

class EntityDriver extends \Nette\Object implements \Venne\DataTransfer\Driver
{

	const ENTITY_CLASS = 'class';
	const PRIMARY_KEY = 'primaryKey';

	/** @var \Kdyby\Doctrine\EntityManager */
	private $entityManager;

	public function __construct(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
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

		foreach ($keys as $key) {
			if (isset($object->$key)) {
				$method = sprintf('get%s', ucfirst($key));
				$values[$key] = call_user_func(array($object, $method));
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
		$dao = $this->entityManager->getRepository($primaryKey[self::ENTITY_CLASS]);

		return $dao->find($primaryKey[self::PRIMARY_KEY]);
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
