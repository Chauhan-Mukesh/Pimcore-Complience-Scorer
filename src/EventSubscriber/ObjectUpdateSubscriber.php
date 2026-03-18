<?php

declare(strict_types=1);

namespace CauhanMukesh\PimcoreMarketReadinessShieldBundle\EventSubscriber;

use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Messenger\CalculateScoreMessage;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Listens to the Pimcore DataObject postUpdate event and dispatches an async
 * CalculateScoreMessage so that readiness scores are recalculated in the background.
 *
 * This subscriber uses the current Pimcore 2025.4+ event API — no deprecated methods.
 */
final class ObjectUpdateSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectEvents::POST_UPDATE => 'onPostUpdate',
        ];
    }

    public function onPostUpdate(DataObjectEvent $event): void
    {
        $object = $event->getObject();

        if (!$object instanceof Concrete) {
            return;
        }

        $objectId = $object->getId();

        if (null === $objectId) {
            return;
        }

        $this->messageBus->dispatch(new CalculateScoreMessage($objectId));
    }
}
