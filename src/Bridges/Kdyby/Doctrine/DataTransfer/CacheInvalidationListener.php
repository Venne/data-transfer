<?php

namespace Venne\Bridges\Kdyby\Doctrine\DataTransfer;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Nette\Caching\Cache;

class CacheInvalidationListener implements \Doctrine\Common\EventSubscriber
{

	/** @var \Nette\Caching\Cache */
	private $cache;

	/**
	 * @param \Nette\Caching\Cache $cache
	 */
	public function __construct(Cache $cache)
	{
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
	 * Array of events.
	 *
	 * @return array
	 */
	public function getSubscribedEvents()
	{
		return array(
			Events::onFlush,
		);
	}

	/**
	 * @param \Doctrine\ORM\Event\OnFlushEventArgs $eventArgs
	 */
	public function onFlush(OnFlushEventArgs $eventArgs)
	{
		$em = $eventArgs->getEntityManager();
		$uow = $em->getUnitOfWork();
		$entities = array();

		foreach ($uow->getScheduledEntityInsertions() as $entity) {
			$class = $em->getClassMetadata(get_class($entity))->name;
			$entities[$class][] = $uow->getSingleIdentifierValue($entity);
		}

		foreach ($uow->getScheduledEntityUpdates() as $entity) {
			$class = $em->getClassMetadata(get_class($entity))->name;
			$entities[$class][] = $uow->getSingleIdentifierValue($entity);
		}

		foreach ($uow->getScheduledEntityDeletions() as $entity) {
			$class = $em->getClassMetadata(get_class($entity))->name;
			$entities[$class][] = $uow->getSingleIdentifierValue($entity);
		}

		foreach ($entities as $class => $ids) {
			foreach ($ids as $id) {
				$this->cache->clean($this->getDependencies($class, $id));
			}
		}

	}

	/**
	 * @param string $class
	 * @param mixed $primaryKey
	 * @return mixed[]
	 */
	protected function getDependencies($class, $primaryKey)
	{
		return array(
			Cache::TAGS => array(Helpers::formatCacheTag($class, $primaryKey)),
		);
	}

}
