<?php

namespace LiteCQRS\Bus;

use LiteCQRS\DomainEvent;
use SplObjectStorage;
use Exception;

abstract class AbstractEventMessageBus implements EventMessageBus
{
    private $events;
    private $scheduledEvents;

    public function __construct()
    {
        $this->events          = new SplObjectStorage();
        $this->scheduledEvents = new SplObjectStorage();
    }

    public function publish($event)
    {
        $this->handle($event);
    }

    protected function handle($event)
    {
        $eventName  = new EventName($event);
        $services   = $this->getHandlers($eventName);

        foreach ($services as $service) {
            $this->invokeEventHandler($service, $event);
        }
    }

    protected function invokeEventHandler($service, $event)
    {
        try {
            $handler = new EventInvocationHandler($service);
            $handler->handle($event);
        } catch(Exception $e) {
            $this->handle(new EventExecutionFailed(array(
                "service"   => get_class($service),
                "exception" => $e,
                "event"     => $event,
            )));
        }
    }

    abstract protected function getHandlers(EventName $eventName);
}


