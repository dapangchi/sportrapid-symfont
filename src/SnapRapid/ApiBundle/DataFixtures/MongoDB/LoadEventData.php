<?php


use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use SnapRapid\Core\Model\Event;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadEventData implements FixtureInterface, ContainerAwareInterface
{
    private $events = [
        'Football'  => [
            'children' => [
                'English Premier League' => [
                    'children' => [
                        '2015/2016 Season' => [
                            'start'    => '2015-08-08',
                            'end'      => '2016-05-15',
                            'children' => [
                                'Everton v Arsenal'         => [
                                    'start' => '2016-03-19',
                                    'end'   => '2016-03-19',
                                ],
                                'Aston Villa v Southampton' => [
                                    'start' => '2016-04-23',
                                    'end'   => '2016-04-23',
                                ],
                            ],
                        ],
                    ],
                ],
                'FA Cup'                 => [
                    'children' => [
                        '2015/2016 Season' => [
                            'start'    => '2015-08-15',
                            'end'      => '2016-05-21',
                            'children' => [
                                'Final' => [
                                    'start' => '2016-05-21',
                                    'end'   => '2016-05-21',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'Tennis'    => [
            'children' => [
                '2016 ATP World Tour' => [
                    'start'    => '2016-01-04',
                    'end'      => '2016-11-27',
                    'children' => [
                        'Wimbledon' => [
                            'start'    => '2016-06-27',
                            'end'      => '2016-07-10',
                            'children' => [
                                'Women\'s R1: Johanna Konta v Venus Williams' => [
                                    'start' => '2016-06-27',
                                    'end'   => '2016-06-27',
                                ],
                                'Men\'s Final: Rafael Nadal v Roger Federer'  => [
                                    'start' => '2016-07-10',
                                    'end'   => '2016-07-10',
                                ],
                            ],
                        ],
                        'US Open'   => [
                            'start' => '2016-08-29',
                            'end'   => '2016-09-11',
                        ],
                    ],
                ],
            ],
        ],
        'Formula 1' => [
            'children' => [
                '2016 Season' => [
                    'start'    => '2016-03-10',
                    'end'      => '2016-12-10',
                    'children' => [
                        'Australian GP' => [
                            'start' => '2016-03-15',
                            'end'   => '2016-03-22',
                        ],
                        'Chinese GP'    => [
                            'start' => '2016-04-14',
                            'end'   => '2016-04-21',
                        ],
                    ],
                ],
            ],
        ],
    ];

    /**
     * @var ContainerInterface
     */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $eventManager = $this->container->get('event_manager');
        $saveEvent    = function ($eventName, $eventDetails, $parent = null) use (&$saveEvent, $eventManager) {
            $event = $eventManager->createNewEvent();
            $event->setName($eventName);

            if ($parent) {
                $event->setParent($parent);
            }

            if (isset($eventDetails['start']) && isset($eventDetails['end'])) {
                $event
                    ->setDateRangeType(Event::DATE_RANGE_TYPE_CUSTOM)
                    ->setDateRangeStart(new \DateTime($eventDetails['start']))
                    ->setDateRangeEnd(new \DateTime($eventDetails['end']));
            } else {
                $event->setDateRangeType(Event::DATE_RANGE_TYPE_THIS_MONTH);
            }

            $eventManager->saveNewEvent($event);

            if (isset($eventDetails['children'])) {
                foreach ($eventDetails['children'] as $childEventName => $childEventDetails) {
                    $saveEvent($childEventName, $childEventDetails, $event);
                }
            }
        };

        foreach ($this->events as $eventName => $eventDetails) {
            $saveEvent($eventName, $eventDetails);
        }
    }
}
