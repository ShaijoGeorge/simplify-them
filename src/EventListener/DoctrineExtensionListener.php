<?php

namespace App\EventListener;

use Gedmo\Blameable\BlameableListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bundle\SecurityBundle\Security;

class DoctrineExtensionListener implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private BlameableListener $blameableListener
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();

        // If we have a logged-in user, use their identifier (e.g., email)
        if ($user) {
            $this->blameableListener->setUserValue($user);
        }
    }
}