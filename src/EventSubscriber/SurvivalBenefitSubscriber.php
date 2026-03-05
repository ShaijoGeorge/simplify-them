<?php

namespace App\EventSubscriber;

use App\Entity\Policy;
use App\Service\SurvivalBenefitGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to EasyAdmin's AfterEntityPersistedEvent for Policy entities.
 *
 * When a new Policy is saved and it is a Money Back plan, this subscriber
 * triggers automatic generation of all SurvivalBenefit rows.
 */
class SurvivalBenefitSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SurvivalBenefitGeneratorService $generator,
        private EntityManagerInterface $entityManager,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            AfterEntityPersistedEvent::class => 'onPolicyCreated',
        ];
    }

    public function onPolicyCreated(AfterEntityPersistedEvent $event): void
    {
        $entity = $event->getEntityInstance();

        if (!$entity instanceof Policy) {
            return;
        }

        $benefits = $this->generator->generateForPolicy($entity);

        if (count($benefits) > 0) {
            $this->entityManager->flush();
        }
    }
}
