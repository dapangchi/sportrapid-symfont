<?php

namespace SnapRapid\Core\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Predis\Client;
use SnapRapid\ApiBundle\Repository\EventRepository;
use SnapRapid\Core\Model\Company;
use SnapRapid\Core\Model\Event;
use SnapRapid\Core\Model\Label;
use SnapRapid\Core\Model\Platform;
use SnapRapid\Core\Model\Post;
use SnapRapid\Core\Repository\EventRepositoryInterface;

class DashboardManager implements DashboardManagerInterface
{
    const CACHE_ENABLED                          = false;
    const STORE_KEY_SOCIAL_PLATFORM_IDS          = 'social_platform_ids';
    const STORE_PREFIX_COMPANY_KEYS              = 'company_cache_keys:';
    const STORE_PREFIX_EVENT_KEYS                = 'event_cache_keys:';
    const STORE_PREFIX_TOPIC_IDS                 = 'topic_ids:';
    const STORE_PREFIX_CONTENT_PLOT              = 'content_plot:';
    const STORE_PREFIX_MEDIA_VALUE               = 'media_value:';
    const STORE_PREFIX_MEDIA_EXPOSURE            = 'media_exposure:';
    const STORE_PREFIX_TRENDING_THEMES           = 'trending_themes:';
    const STORE_PREFIX_SENTIMENT                 = 'sentiment:';
    const STORE_PREFIX_TOP_SOURCES               = 'top_sources:';
    const STORE_PREFIX_IMPRESSIONS_VS_ENGAGEMENT = 'impressions_vs_engagement:';
    const STORE_PREFIX_MOST_POWERFUL_MEDIA       = 'most_powerful_media:';
    const STORE_PREFIX_MOST_VIEWED_VIDEOS        = 'most_viewed_videos:';

    /**
     * @var DocumentManager
     */
    protected $documentManager;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Client
     */
    protected $redis;

    /**
     * @var EventRepository
     */
    private $eventRepository;

    /**
     * @var string
     */
    private $webPlatformId;

    /**
     * @param ObjectManager            $documentManager
     * @param Connection               $connection
     * @param Client                   $redis
     * @param EventRepositoryInterface $eventRepository
     * @param string                   $webPlatformId
     */
    public function __construct(
        ObjectManager $documentManager,
        Connection $connection,
        Client $redis,
        EventRepositoryInterface $eventRepository,
        $webPlatformId
    ) {
        $this->documentManager = $documentManager;
        $this->connection      = $connection;
        $this->redis           = $redis;
        $this->eventRepository = $eventRepository;
        $this->webPlatformId   = $webPlatformId;
    }

    /**
     * Get the topic ids relevant to a given company and event
     *
     * For now we are ignoring the company's topics
     *
     * @param Company $company
     * @param Event   $event
     *
     * @return array
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function getMatchingTopicIds(Company $company, Event $event)
    {
        $cacheKey = self::STORE_PREFIX_TOPIC_IDS.
            $company->getId().
            $event->getId();

        if (self::CACHE_ENABLED && $this->redis->exists($cacheKey)) {
            $topicIds = $this->redis->smembers($cacheKey);
        } else {
            $topicIds        = [];
            $eventCollection = $this->documentManager->getDocumentCollection('SnapRapidApiBundle:Event');
            $topicCollection = $this->documentManager->getDocumentCollection('SnapRapidApiBundle:Topic');

            // get the event ids of all descendants
            $eventIds = [$event->getId()];
            $eventIds = array_merge($eventIds, $this->eventRepository->getDescendantEventIds($event));
            foreach ($eventIds as &$eventId) {
                $eventId = new \MongoId($eventId);
            }

            // get the topics for all the relevant events
            $builder = $eventCollection->createAggregationBuilder();
            $builder->match()->field('_id')->in($eventIds);
            $builder->unwind('$topics');
            $builder->project()->excludeIdField()->includeFields(['topics']);
            $cursor      = $builder->execute();
            $eventTopics = iterator_to_array($cursor);
            foreach ($eventTopics as $eventTopic) {
                $topicIds[(string) $eventTopic['topics']] = $eventTopic['topics'];
            }

            // get descendant topics
            $parentIds = $topicIds;
            while (count($parentIds)) {
                $builder = $topicCollection->createAggregationBuilder();
                $builder->match()->field('_id')->in($parentIds);
                $builder->unwind('$children');
                $builder->project()->excludeIdField()->includeFields(['children']);
                $cursor      = $builder->execute();
                $childTopics = iterator_to_array($cursor);
                $childIds    = [];
                foreach ($childTopics as $childTopic) {
                    $childIds[(string) $childTopic['children']] = $childTopic['children'];
                }
                $newIds    = array_diff_key($childIds, $topicIds);
                $topicIds  = array_merge($topicIds, $newIds);
                $parentIds = $newIds;
            }

            // convert to strings
            $topicIds = array_keys($topicIds);
            sort($topicIds);

            $this->addCacheItem($cacheKey, $topicIds, false, $company, $event);
        }

        return $topicIds;
    }

    /**
     * Content plot
     *
     * @param Company   $company
     * @param Label     $label
     * @param Event     $event
     * @param string    $coverageType
     * @param \DateTime $fromDate
     * @param \DateTime $toDate
     *
     * @return array
     */
    public function getContentPlotData(
        Company $company,
        Label $label,
        Event $event,
        $coverageType,
        \DateTime $fromDate,
        \DateTime $toDate
    ) {
        $cacheKey = self::STORE_PREFIX_CONTENT_PLOT.
            $company->getId().
            $label->getId().
            $event->getId().
            $coverageType.
            $fromDate->format('U').
            $toDate->format('U');

        if (self::CACHE_ENABLED && $this->redis->exists($cacheKey)) {
            $data = json_decode($this->redis->get($cacheKey), true);
        } else {
            $postCollection = $this->documentManager->getDocumentCollection('SnapRapidApiBundle:Post');
            $allData        = [];

            foreach (['images', 'videos'] as $imagesOrVideos) {
                $pipeline = [
                    [
                        '$match' => array_merge(
                            $this->getPostMatch($company, $label, $event, $coverageType, $fromDate, $toDate),
                            [
                                'post_type'     => [
                                    '$in' => [
                                        $imagesOrVideos == 'images' ? Post::POST_TYPE_IMAGE : Post::POST_TYPE_VIDEO,
                                        Post::POST_TYPE_BOTH,
                                    ],
                                ],
                                'valuation.lid' => [
                                    '$eq' => new \MongoId($label->getId()),
                                ],
                            ]
                        ),
                    ],
                    [
                        '$sort' => [
                            'published_at' => 1,
                        ],
                    ],
                    [
                        '$project' => [
                            'published_at' => [
                                '$dateToString' => [
                                    'format' => '%Y-%m-%d %H:%M',
                                    'date'   => '$published_at',
                                ],
                            ],
                            'url'          => 1,
                            'images'       => [
                                '$slice' => ['$images', 1],
                            ],
                            'videos'       => [
                                '$slice' => ['$videos', 1],
                            ],
                            'valuation'    => [
                                '$filter' => [
                                    'input' => '$valuation',
                                    'as'    => 'valuation',
                                    'cond'  => [
                                        '$eq' => [
                                            '$$valuation.lid',
                                            new \MongoId($label->getId()),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        '$unwind' => '$valuation',
                    ],
                    [
                        '$unwind' => [
                            'path'                       => '$images',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path'                       => '$videos',
                            'preserveNullAndEmptyArrays' => true,
                        ],
                    ],
                    [
                        '$project' => [
                            'published_at' => 1,
                            'url'          => 1,
                            'value'        => '$valuation.value',
                            'image_thumb'  => '$images.url',
                            'video_thumb'  => '$videos.thumbnail',
                        ],
                    ],
                ];

                $allData[$imagesOrVideos] = $postCollection->aggregate($pipeline)->toArray();
            }

            if (count($allData['images']) || count($allData['videos'])) {
                // build empty data response
                $data = [
                    'images'     => $this->fillMissingDataEntries([], array_fill(0, 8, []), $fromDate, $toDate),
                    'videos'     => $this->fillMissingDataEntries([], array_fill(0, 8, []), $fromDate, $toDate),
                    'maxValue'   => 0,
                    'minValue'   => 0,
                    'totalValue' => [
                        'images' => 0,
                        'videos' => 0,
                    ],
                ];

                // cache timestamps
                $timestamps = [
                    strtotime('00:00'),
                    strtotime('03:00'),
                    strtotime('06:00'),
                    strtotime('09:00'),
                    strtotime('12:00'),
                    strtotime('15:00'),
                    strtotime('18:00'),
                    strtotime('21:00'),
                    strtotime('23:59'),
                ];

                // put posts into correct bins
                foreach (['images', 'videos'] as $imagesOrVideos) {
                    foreach ($allData[$imagesOrVideos] as $post) {
                        // convert id to string
                        $post['id'] = bin2hex($post['_id']->bin);
                        unset($post['_id']);

                        // put post in correct segment of correct date
                        $publishedAt = new \DateTime($post['published_at']);
                        $dateKey     = $publishedAt->format('Y-m-d');
                        $time        = strtotime($publishedAt->format('H:i'));
                        if ($time >= $timestamps[0] && $time < $timestamps[1]) {
                            $timeKey = 0;
                        } elseif ($time >= $timestamps[1] && $time < $timestamps[2]) {
                            $timeKey = 1;
                        } elseif ($time >= $timestamps[2] && $time < $timestamps[3]) {
                            $timeKey = 2;
                        } elseif ($time >= $timestamps[3] && $time < $timestamps[4]) {
                            $timeKey = 3;
                        } elseif ($time >= $timestamps[4] && $time < $timestamps[5]) {
                            $timeKey = 4;
                        } elseif ($time >= $timestamps[5] && $time < $timestamps[6]) {
                            $timeKey = 5;
                        } elseif ($time >= $timestamps[6] && $time < $timestamps[7]) {
                            $timeKey = 6;
                        } elseif ($time >= $timestamps[7] && $time <= $timestamps[8]) {
                            $timeKey = 7;
                        }
                        $data[$imagesOrVideos][$dateKey][$timeKey][] = $post;

                        // update total and max/min vals
                        $data['totalValue'][$imagesOrVideos] += $post['value'];
                        $data['maxValue'] = max($data['maxValue'], $post['value']);
                        $data['minValue'] = min($data['minValue'], $post['value']);
                    }
                }

                // sort posts by value
                foreach (['images', 'videos'] as $imagesOrVideos) {
                    foreach ($data[$imagesOrVideos] as &$dateData) {
                        foreach ($dateData as &$segmentData) {
                            usort(
                                $segmentData,
                                function ($a, $b) {
                                    return $a['value'] < $b['value'] ? -1 : 1;
                                }
                            );
                        }
                    }
                }
            } else {
                $data = [];
            }

            $this->addCacheItem($cacheKey, $data, true);
        }

        return $data;
    }

    /**
     * Media value
     *
     * @param Company   $company
     * @param Label     $label
     * @param Event     $event
     * @param string    $coverageType
     * @param \DateTime $fromDate
     * @param \DateTime $toDate
     * @param string    $imagesOrVideos
     *
     * @return array
     */
    public function getMediaValueData(
        Company $company,
        Label $label,
        Event $event,
        $coverageType,
        \DateTime $fromDate,
        \DateTime $toDate,
        $imagesOrVideos
    ) {
        $cacheKey = self::STORE_PREFIX_MEDIA_VALUE.
            $company->getId().
            $label->getId().
            $event->getId().
            $coverageType.
            $fromDate->format('U').
            $toDate->format('U').
            $imagesOrVideos;

        if (self::CACHE_ENABLED && $this->redis->exists($cacheKey)) {
            $data = json_decode($this->redis->get($cacheKey), true);
        } else {
            $postCollection = $this->documentManager->getDocumentCollection('SnapRapidApiBundle:Post');

            // build aggregation pipeline
            $pipeline = [
                [
                    '$match' => array_merge(
                        $this->getPostMatch($company, $label, $event, $coverageType, $fromDate, $toDate),
                        [
                            'post_type'     => [
                                '$in' => [
                                    $imagesOrVideos == 'images' ? Post::POST_TYPE_IMAGE : Post::POST_TYPE_VIDEO,
                                    Post::POST_TYPE_BOTH,
                                ],
                            ],
                            'valuation.lid' => [
                                '$eq' => new \MongoId($label->getId()),
                            ],
                        ]
                    ),
                ],
                [
                    '$project' => [
                        'published_at' => 1,
                        'valuation'    => [
                            '$filter' => [
                                'input' => '$valuation',
                                'as'    => 'valuation',
                                'cond'  => [
                                    '$eq' => ['$$valuation.lid', new \MongoId($label->getId())],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    '$unwind' => '$valuation',
                ],
                [
                    '$group' => [
                        '_id'   => [
                            '$dateToString' => [
                                'format' => '%Y-%m-%d',
                                'date'   => '$published_at',
                            ],
                        ],
                        'count' => [
                            '$sum' => 1,
                        ],
                        'value' => [
                            '$sum' => '$valuation.value',
                        ],
                    ],
                ],
                [
                    '$sort' => [
                        '_id' => 1,
                    ],
                ],
            ];

            $results = $postCollection->aggregate($pipeline)->toArray();

            // change results to required frontend format
            $data = [];
            foreach ($results as &$result) {
                $date = $result['_id'];
                unset($result['_id']);
                $data[$date] = $result;
            }

            if (count($data)) {
                $data = $this->fillMissingDataEntries(
                    $data,
                    [
                        'count' => 0,
                        'value' => 0,
                    ],
                    $fromDate,
                    $toDate
                );
            }
            $this->addCacheItem($cacheKey, $data, true);
        }

        return $data;
    }

    /**
     * Media exposure
     *
     * @param Company   $company
     * @param Label     $label
     * @param Event     $event
     * @param string    $coverageType
     * @param \DateTime $fromDate
     * @param \DateTime $toDate
     *
     * @return array
     */
    public function getMediaExposureData(
        Company $company,
        Label $label,
        Event $event,
        $coverageType,
        \DateTime $fromDate,
        \DateTime $toDate
    ) {
        $cacheKey = self::STORE_PREFIX_MEDIA_EXPOSURE.
            $company->getId().
            $label->getId().
            $event->getId().
            $coverageType.
            $fromDate->format('U').
            $toDate->format('U');

        if (self::CACHE_ENABLED && $this->redis->exists($cacheKey)) {
            $data = json_decode($this->redis->get($cacheKey), true);
        } else {
            // collate results with 2 queries, one with label, one without label
            $postCollection                  = $this->documentManager
                ->getDocumentCollection('SnapRapidApiBundle:Post');
            $postSummaryStatisticsCollection = $this->documentManager
                ->getDocumentCollection('SnapRapidApiBundle:PostSummaryStatistics');
            $results                         = [];

            // get label data
            $postMatch = $this->getPostMatch($company, $label, $event, $coverageType, $fromDate, $toDate);
            foreach (['images', 'videos'] as $imagesOrVideos) {
                $postMatch['post_type'] = [
                    '$in' => [
                        $imagesOrVideos == 'images' ? Post::POST_TYPE_IMAGE : Post::POST_TYPE_VIDEO,
                        Post::POST_TYPE_BOTH,
                    ],
                ];

                // build aggregation pipeline
                $pipeline = [
                    [
                        '$match' => $postMatch,
                    ],
                    [
                        '$project' => [
                            'published_at' => 1,
                        ],
                    ],
                    [
                        '$group' => [
                            '_id'                                => [
                                '$dateToString' => [
                                    'format' => '%Y-%m-%d',
                                    'date'   => '$published_at',
                                ],
                            ],
                            'num_'.$imagesOrVideos.'_with_label' => [
                                '$sum' => 1,
                            ],
                        ],
                    ],
                    [
                        '$sort' => [
                            '_id' => 1,
                        ],
                    ],
                ];

                $results['with_label'][$imagesOrVideos] = $postCollection->aggregate($pipeline)->toArray();
            }

            // only fetch without-label data if we have some with-label data to display
            if (count($results['with_label']['images']) || count($results['with_label']['videos'])) {
                // get all data
                $postMatch         = $this->getPostMatch($company, $label, $event, $coverageType, $fromDate, $toDate);
                $postMatch['date'] = $postMatch['published_at'];
                unset($postMatch['published_at']);
                unset($postMatch['verified']);
                foreach (['images', 'videos'] as $imagesOrVideos) {
                    $postMatch['post_type'] = [
                        '$in' => [
                            $imagesOrVideos == 'images' ? Post::POST_TYPE_IMAGE : Post::POST_TYPE_VIDEO,
                            Post::POST_TYPE_BOTH,
                        ],
                    ];

                    $pipeline = [
                        [
                            '$match' => $postMatch,
                        ],
                        [
                            '$group' => [
                                '_id'                         => [
                                    '$dateToString' => [
                                        'format' => '%Y-%m-%d',
                                        'date'   => '$date',
                                    ],
                                ],
                                'num_'.$imagesOrVideos.'_all' => [
                                    '$sum' => '$count',
                                ],
                            ],
                        ],
                        [
                            '$sort' => [
                                '_id' => 1,
                            ],
                        ],
                    ];

                    $results['all'][$imagesOrVideos] = $postSummaryStatisticsCollection
                        ->aggregate($pipeline)
                        ->toArray();
                }
            } else {
                $results['all']['images'] = [];
                $results['all']['videos'] = [];
            }

            // combine results
            $rawData = [];
            foreach (['all', 'with_label'] as $key) {
                foreach (['images', 'videos'] as $imagesOrVideos) {
                    foreach ($results[$key][$imagesOrVideos] as &$result) {
                        $date = $result['_id'];
                        unset($result['_id']);
                        if (isset($rawData[$date])) {
                            $rawData[$date] = array_merge($rawData[$date], $result);
                        } else {
                            $rawData[$date] = $result;
                        }
                    }
                }
            }
            ksort($rawData);

            // change results to required frontend format
            $data           = [];
            $requiredFields = ['num_images_all', 'num_videos_all', 'num_images_with_label', 'num_videos_with_label'];
            foreach ($rawData as $date => $datum) {
                foreach ($requiredFields as $requiredField) {
                    if (!isset($datum[$requiredField])) {
                        $datum[$requiredField] = 0;
                    }
                }
                $data[$date] = $datum;
            }

            if (count($data)) {
                $data = $this->fillMissingDataEntries(
                    $data,
                    [
                        'num_images_all'        => 0,
                        'num_videos_all'        => 0,
                        'num_images_with_label' => 0,
                        'num_videos_with_label' => 0,
                    ],
                    $fromDate,
                    $toDate
                );
            }

            $this->addCacheItem($cacheKey, $data, true);
        }

        return $data;
    }

    /**
     * Trending themes
     *
     * @param Company   $company
     * @param Label     $label
     * @param Event     $event
     * @param string    $coverageType
     * @param \DateTime $fromDate
     * @param \DateTime $toDate
     *
     * @return array
     */
    public function getTrendingThemesData(
        Company $company,
        Label $label,
        Event $event,
        $coverageType,
        \DateTime $fromDate,
        \DateTime $toDate
    ) {
        $cacheKey = self::STORE_PREFIX_TRENDING_THEMES.
            $company->getId().
            $label->getId().
            $event->getId().
            $coverageType.
            $fromDate->format('U').
            $toDate->format('U');

        if (self::CACHE_ENABLED && $this->redis->exists($cacheKey)) {
            $data = json_decode($this->redis->get($cacheKey), true);
        } else {
            $postCollection = $this->documentManager->getDocumentCollection('SnapRapidApiBundle:Post');

            // build aggregation pipeline
            $pipeline = [
                [
                    '$match' => $this->getPostMatch($company, $label, $event, $coverageType, $fromDate, $toDate),
                ],
                [
                    '$project' => [
                        '_id'  => 0,
                        'tags' => $coverageType == Platform::COVERAGE_TYPE_DIGITAL ? '$web_content.key_phrases' : 1,
                    ],
                ],
                [
                    '$unwind' => '$tags',
                ],
                [
                    '$group' => [
                        '_id'   => '$tags',
                        'count' => [
                            '$sum' => 1,
                        ],
                    ],
                ],
                [
                    '$sort' => [
                        'count' => -1,
                    ],
                ],
                [
                    '$limit' => 50,
                ],
            ];

            $data = $postCollection->aggregate($pipeline)->toArray();

            // scale counts to give sizes in the range of [1, 10]
            if (count($data)) {
                $maxCount = (int) $data[0]['count'];
                $minCount = (int) end($data)['count'];
                $scale    = $maxCount - $minCount ?: 1;
                foreach ($data as &$datum) {
                    $datum = [
                        'word' => $datum['_id'],
                        'size' => ceil(($datum['count'] - $minCount) / $scale * 9) + 1,
                    ];
                }
            }

            $this->addCacheItem($cacheKey, $data, true);
        }

        return $data;
    }

    /**
     * Sentiment
     *
     * @param Company   $company
     * @param Label     $label
     * @param Event     $event
     * @param string    $coverageType
     * @param \DateTime $fromDate
     * @param \DateTime $toDate
     *
     * @return array
     */
    public function getSentimentData(
        Company $company,
        Label $label,
        Event $event,
        $coverageType,
        \DateTime $fromDate,
        \DateTime $toDate
    ) {
        $cacheKey = self::STORE_PREFIX_SENTIMENT.
            $company->getId().
            $label->getId().
            $event->getId().
            $coverageType.
            $fromDate->format('U').
            $toDate->format('U');

        if (self::CACHE_ENABLED && $this->redis->exists($cacheKey)) {
            $data = json_decode($this->redis->get($cacheKey), true);
        } else {
            $postCollection     = $this->documentManager->getDocumentCollection('SnapRapidApiBundle:Post');
            $sentimentFieldName = $coverageType == Platform::COVERAGE_TYPE_SOCIAL ? 'sentiment' : 'web_content.sentiment';

            // build aggregation pipeline
            $pipeline = [
                [
                    '$match' => array_merge(
                        $this->getPostMatch($company, $label, $event, $coverageType, $fromDate, $toDate),
                        [
                            $sentimentFieldName => [
                                '$exists' => true,
                            ],
                        ]
                    ),
                ],
                [
                    '$project' => [
                        'published_at' => 1,
                        'is_positive'  => [
                            '$cond' => [
                                'if'   => [
                                    '$and' => [
                                        [
                                            '$lte' => [
                                                '$'.$sentimentFieldName,
                                                1,
                                            ],
                                        ],
                                        [
                                            '$gt' => [
                                                '$'.$sentimentFieldName,
                                                .33,
                                            ],
                                        ],
                                    ],
                                ],
                                'then' => 1,
                                'else' => 0,
                            ],
                        ],
                        'is_neutral'   => [
                            '$cond' => [
                                'if'   => [
                                    '$and' => [
                                        [
                                            '$lte' => [
                                                '$'.$sentimentFieldName,
                                                .33,
                                            ],
                                        ],
                                        [
                                            '$gte' => [
                                                '$'.$sentimentFieldName,
                                                -.33,
                                            ],
                                        ],
                                    ],
                                ],
                                'then' => 1,
                                'else' => 0,
                            ],
                        ],
                        'is_negative'  => [
                            '$cond' => [
                                'if'   => [
                                    '$and' => [
                                        [
                                            '$lt' => [
                                                '$'.$sentimentFieldName,
                                                -0.33,
                                            ],
                                        ],
                                        [
                                            '$gte' => [
                                                '$'.$sentimentFieldName,
                                                -1,
                                            ],
                                        ],
                                    ],
                                ],
                                'then' => 1,
                                'else' => 0,
                            ],
                        ],
                    ],
                ],
                [
                    '$group' => [
                        '_id'          => [
                            '$dateToString' => [
                                'format' => '%Y-%m-%d',
                                'date'   => '$published_at',
                            ],
                        ],
                        'num_positive' => [
                            '$sum' => '$is_positive',
                        ],
                        'num_neutral'  => [
                            '$sum' => '$is_neutral',
                        ],
                        'num_negative' => [
                            '$sum' => '$is_negative',
                        ],
                    ],
                ],
                [
                    '$sort' => [
                        '_id' => 1,
                    ],
                ],
            ];

            $results = $postCollection->aggregate($pipeline)->toArray();

            // change results to required frontend format
            $data = [];
            foreach ($results as &$result) {
                $date = $result['_id'];
                unset($result['_id']);
                $data[$date] = $result;
            }

            if (count($data)) {
                $data = $this->fillMissingDataEntries(
                    $data,
                    [
                        'num_positive' => 0,
                        'num_neutral'  => 0,
                        'num_negative' => 0,
                    ],
                    $fromDate,
                    $toDate
                );
            }

            $this->addCacheItem($cacheKey, $data, true);
        }

        return $data;
    }

    /**
     * Top sources
     *
     * @param Company   $company
     * @param Label     $label
     * @param Event     $event
     * @param string    $coverageType
     * @param \DateTime $fromDate
     * @param \DateTime $toDate
     *
     * @return array
     */
    public function getTopSourcesData(
        Company $company,
        Label $label,
        Event $event,
        $coverageType,
        \DateTime $fromDate,
        \DateTime $toDate
    ) {
        $cacheKey = self::STORE_PREFIX_TOP_SOURCES.
            $company->getId().
            $label->getId().
            $event->getId().
            $coverageType.
            $fromDate->format('U').
            $toDate->format('U');

        if (self::CACHE_ENABLED && $this->redis->exists($cacheKey)) {
            $data = json_decode($this->redis->get($cacheKey), true);
        } else {
            $postCollection = $this->documentManager->getDocumentCollection('SnapRapidApiBundle:Post');

            // build aggregation pipeline
            $pipeline = [
                [
                    '$match' => array_merge(
                        $this->getPostMatch($company, $label, $event, $coverageType, $fromDate, $toDate),
                        [
                            'valuation.lid' => [
                                '$eq' => new \MongoId($label->getId()),
                            ],
                        ]
                    ),
                ],
                [
                    '$project' => [
                        $coverageType == Platform::COVERAGE_TYPE_SOCIAL ? 'author_id' : 'source' => 1,
                        'valuation'                                                              => [
                            '$filter' => [
                                'input' => '$valuation',
                                'as'    => 'valuation',
                                'cond'  => [
                                    '$eq' => [
                                        '$$valuation.lid',
                                        new \MongoId($label->getId()),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    '$unwind' => '$valuation',
                ],
                [
                    '$group' => [
                        '_id'   => '$'.($coverageType == Platform::COVERAGE_TYPE_SOCIAL ? 'author_id' : 'source'),
                        'value' => [
                            '$sum' => '$valuation.value',
                        ],
                    ],
                ],
            ];

            if ($coverageType == Platform::COVERAGE_TYPE_SOCIAL) {
                array_push(
                    $pipeline,
                    [
                        '$lookup' => [
                            'from'         => 'authors',
                            'localField'   => '_id',
                            'foreignField' => '_id',
                            'as'           => 'author',
                        ],
                    ],
                    [
                        '$unwind' => '$author',
                    ],
                    [
                        '$project' => [
                            '_id'         => 0,
                            'value'       => 1,
                            'name'        => '$author.name',
                            'screen_name' => '$author.screen_name',
                            'platform'    => '$author.platform',
                        ],
                    ]
                );
            } else {
                array_push(
                    $pipeline,
                    [
                        '$project' => [
                            '_id'   => 0,
                            'value' => 1,
                            'name'  => '$_id',
                        ],
                    ]
                );
            }

            $pipeline[] = [
                '$sort' => [
                    'value' => -1,
                ],
            ];

            $results = $postCollection->aggregate($pipeline)->toArray();

            // take the top 6 and lump the rest together under "others"
            $data      = [];
            $limit     = 6;
            $numOthers = 0;
            $others    = [
                'author' => 'Others',
                'value'  => 0,
            ];
            foreach ($results as &$result) {
                $result['value']    = round($result['value']);
                $result['platform'] = (string) $result['platform'];
                $result['author']   = isset($result['name']) && $result['name']
                    ? $result['name']
                    : $result['screen_name'];
                unset($result['name']);
                unset($result['screen_name']);
                if (count($data) < $limit) {
                    $data[] = $result;
                } else {
                    $others['value'] += $result['value'];
                    ++$numOthers;
                }
            }
            if ($numOthers && $others['value']) {
                $others['author'] = 'Others ('.number_format($numOthers).')';
                $data[]           = $others;
            }

            $this->addCacheItem($cacheKey, $data, true);
        }

        return $data;
    }

    /**
     * Impressions vs engagement
     *
     * @param Company   $company
     * @param Label     $label
     * @param Event     $event
     * @param string    $coverageType
     * @param \DateTime $fromDate
     * @param \DateTime $toDate
     * @param string    $imagesOrVideos
     *
     * @return array
     */
    public function getImpressionsVsEngagementData(
        Company $company,
        Label $label,
        Event $event,
        $coverageType,
        \DateTime $fromDate,
        \DateTime $toDate,
        $imagesOrVideos
    ) {
        // currently not in use for digital coverage
        if ($coverageType == Platform::COVERAGE_TYPE_DIGITAL) {
            return [
                'impressions' => [],
                'engagement'  => [],
            ];
        }

        $cacheKey = self::STORE_PREFIX_IMPRESSIONS_VS_ENGAGEMENT.
            $company->getId().
            $label->getId().
            $event->getId().
            $coverageType.
            $fromDate->format('U').
            $toDate->format('U').
            $imagesOrVideos;

        if (self::CACHE_ENABLED && $this->redis->exists($cacheKey)) {
            $data = json_decode($this->redis->get($cacheKey), true);
        } else {
            $postCollection = $this->documentManager->getDocumentCollection('SnapRapidApiBundle:Post');

            // build aggregation pipeline - impressions
            $pipeline = [
                [
                    '$match' => array_merge(
                        $this->getPostMatch($company, $label, $event, $coverageType, $fromDate, $toDate),
                        [
                            'post_type' => [
                                '$in' => [
                                    $imagesOrVideos == 'images' ? Post::POST_TYPE_IMAGE : Post::POST_TYPE_VIDEO,
                                    Post::POST_TYPE_BOTH,
                                ],
                            ],
                        ]
                    ),
                ],
                [
                    '$lookup' => [
                        'from'         => 'authors',
                        'localField'   => 'author_id',
                        'foreignField' => '_id',
                        'as'           => 'author',
                    ],
                ],
                [
                    '$unwind' => '$author',
                ],
                [
                    '$project' => [
                        'published_at' => 1,
                        'url'          => 1,
                        'medias'       => 1,
                        'images'       => [
                            '$slice' => ['$images', 1],
                        ],
                        'videos'       => [
                            '$slice' => ['$videos', 1],
                        ],
                        'impressions'  => '$author.statistics.followers',
                    ],
                ],
                [
                    '$unwind' => [
                        'path'                       => '$images',
                        'preserveNullAndEmptyArrays' => true,
                    ],
                ],
                [
                    '$unwind' => [
                        'path'                       => '$videos',
                        'preserveNullAndEmptyArrays' => true,
                    ],
                ],
                [
                    '$sort' => [
                        'published_at' => 1,
                    ],
                ],
                [
                    '$unwind' => '$medias',
                ],
                [
                    '$group' => [
                        '_id'         => '$medias',
                        'url'         => [
                            '$first' => '$url',
                        ],
                        'image_thumb' => [
                            '$first' => '$images.url',
                        ],
                        'video_thumb' => [
                            '$first' => '$videos.thumbnail',
                        ],
                        'impressions' => [
                            '$sum' => '$impressions',
                        ],
                    ],
                ],
                [
                    '$sort' => [
                        'impressions' => -1,
                    ],
                ],
                [
                    '$limit' => 50,
                ],
                [
                    '$lookup' => [
                        'from'         => 'medias',
                        'localField'   => '_id',
                        'foreignField' => '_id',
                        'as'           => 'media',
                    ],
                ],
                [
                    '$unwind' => '$media',
                ],
                [
                    '$unwind' => '$media.locations',
                ],
                [
                    '$lookup' => [
                        'from'         => 'media_locations',
                        'localField'   => 'media.locations.loc_id',
                        'foreignField' => '_id',
                        'as'           => 'media_location',
                    ],
                ],
                [
                    '$unwind' => '$media_location',
                ],
                [
                    '$match' => [
                        'media.visual_labels.lid' => [
                            '$eq' => new \MongoId($label->getId()),
                        ],
                        'media_location.alive'    => [
                            '$eq' => true,
                        ],
                    ],
                ],
                [
                    '$group' => [
                        '_id'            => '$_id',
                        'url'            => [
                            '$first' => '$url',
                        ],
                        'image_thumb'    => [
                            '$first' => '$image_thumb',
                        ],
                        'video_thumb'    => [
                            '$first' => '$video_thumb',
                        ],
                        'media_location' => [
                            '$first' => '$media_location.url',
                        ],
                        'impressions'    => [
                            '$first' => '$impressions',
                        ],
                    ],
                ],
                [
                    '$sort' => [
                        'impressions' => -1,
                    ],
                ],
                [
                    '$limit' => 20,
                ],
                [
                    '$project' => [
                        '_id'            => 0,
                        'url'            => 1,
                        'image_thumb'    => 1,
                        'video_thumb'    => 1,
                        'media_location' => 1,
                        'impressions'    => 1,
                    ],
                ],
            ];

            $impressionsData = $postCollection->aggregate($pipeline)->toArray();

            // build aggregation pipeline - engagement
            $pipeline = [
                [
                    '$match' => array_merge(
                        $this->getPostMatch($company, $label, $event, $coverageType, $fromDate, $toDate),
                        [
                            'post_type' => [
                                '$in' => [
                                    $imagesOrVideos == 'images' ? Post::POST_TYPE_IMAGE : Post::POST_TYPE_VIDEO,
                                    Post::POST_TYPE_BOTH,
                                ],
                            ],
                        ]
                    ),
                ],
                [
                    '$project' => [
                        'published_at' => 1,
                        'url'          => 1,
                        'medias'       => 1,
                        'images'       => [
                            '$slice' => ['$images', 1],
                        ],
                        'videos'       => [
                            '$slice' => ['$videos', 1],
                        ],
                        'engagement'   => [
                            '$sum' => ['$statistics.likes', '$statistics.comments', '$statistics.shares'],
                        ],
                    ],
                ],
                [
                    '$unwind' => [
                        'path'                       => '$images',
                        'preserveNullAndEmptyArrays' => true,
                    ],
                ],
                [
                    '$unwind' => [
                        'path'                       => '$videos',
                        'preserveNullAndEmptyArrays' => true,
                    ],
                ],
                [
                    '$sort' => [
                        'published_at' => 1,
                    ],
                ],
                [
                    '$unwind' => '$medias',
                ],
                [
                    '$group' => [
                        '_id'         => '$medias',
                        'url'         => [
                            '$first' => '$url',
                        ],
                        'image_thumb' => [
                            '$first' => '$images.url',
                        ],
                        'video_thumb' => [
                            '$first' => '$videos.thumbnail',
                        ],
                        'engagement'  => [
                            '$sum' => '$engagement',
                        ],
                    ],
                ],
                [
                    '$sort' => [
                        'engagement' => -1,
                    ],
                ],
                [
                    '$limit' => 50,
                ],
                [
                    '$lookup' => [
                        'from'         => 'medias',
                        'localField'   => '_id',
                        'foreignField' => '_id',
                        'as'           => 'media',
                    ],
                ],
                [
                    '$unwind' => '$media',
                ],
                [
                    '$unwind' => '$media.locations',
                ],
                [
                    '$lookup' => [
                        'from'         => 'media_locations',
                        'localField'   => 'media.locations.loc_id',
                        'foreignField' => '_id',
                        'as'           => 'media_location',
                    ],
                ],
                [
                    '$unwind' => '$media_location',
                ],
                [
                    '$match' => [
                        'media.visual_labels.lid' => [
                            '$eq' => new \MongoId($label->getId()),
                        ],
                        'media_location.alive'    => [
                            '$eq' => true,
                        ],
                    ],
                ],
                [
                    '$group' => [
                        '_id'            => '$_id',
                        'url'            => [
                            '$first' => '$url',
                        ],
                        'image_thumb'    => [
                            '$first' => '$image_thumb',
                        ],
                        'video_thumb'    => [
                            '$first' => '$video_thumb',
                        ],
                        'media_location' => [
                            '$first' => '$media_location.url',
                        ],
                        'engagement'     => [
                            '$first' => '$engagement',
                        ],
                    ],
                ],
                [
                    '$sort' => [
                        'engagement' => -1,
                    ],
                ],
                [
                    '$limit' => 20,
                ],
                [
                    '$project' => [
                        '_id'            => 0,
                        'url'            => 1,
                        'image_thumb'    => 1,
                        'video_thumb'    => 1,
                        'media_location' => 1,
                        'engagement'     => 1,
                    ],
                ],
            ];

            $engagementData = $postCollection->aggregate($pipeline)->toArray();

            $data = [
                'impressions' => $impressionsData,
                'engagement'  => $engagementData,
            ];

            $this->addCacheItem($cacheKey, $data, true);
        }

        return $data;
    }

    /**
     * Most viewed videos
     *
     * @param Company   $company
     * @param Label     $label
     * @param Event     $event
     * @param string    $coverageType
     * @param \DateTime $fromDate
     * @param \DateTime $toDate
     *
     * @return array
     */
    public function getMostViewedVideosData(
        Company $company,
        Label $label,
        Event $event,
        $coverageType,
        \DateTime $fromDate,
        \DateTime $toDate
    ) {
        $cacheKey = self::STORE_PREFIX_MOST_VIEWED_VIDEOS.
            $company->getId().
            $label->getId().
            $event->getId().
            $coverageType.
            $fromDate->format('U').
            $toDate->format('U');

        if (self::CACHE_ENABLED && $this->redis->exists($cacheKey)) {
            $data = json_decode($this->redis->get($cacheKey), true);
        } else {
            $postCollection = $this->documentManager->getDocumentCollection('SnapRapidApiBundle:Post');
            $viewsFieldName = $coverageType == Platform::COVERAGE_TYPE_SOCIAL ? 'views' : 'reach';

            // build aggregation pipeline
            $pipeline = [
                [
                    '$match' => array_merge(
                        $this->getPostMatch($company, $label, $event, $coverageType, $fromDate, $toDate),
                        [
                            'post_type'                   => [
                                '$in' => [Post::POST_TYPE_VIDEO, Post::POST_TYPE_BOTH],
                            ],
                            'statistics.'.$viewsFieldName => [
                                '$gt' => 0,
                            ],
                        ]
                    ),
                ],
                [
                    '$project' => [
                        'published_at' => 1,
                        'url'          => 1,
                        'medias'       => 1,
                        'images'       => [
                            '$slice' => ['$images', 1],
                        ],
                        'videos'       => [
                            '$slice' => ['$videos', 1],
                        ],
                        'views'        => '$statistics.'.$viewsFieldName,
                    ],
                ],
                [
                    '$unwind' => [
                        'path'                       => '$images',
                        'preserveNullAndEmptyArrays' => true,
                    ],
                ],
                [
                    '$unwind' => [
                        'path'                       => '$videos',
                        'preserveNullAndEmptyArrays' => true,
                    ],
                ],
                [
                    '$sort' => [
                        'published_at' => 1,
                    ],
                ],
                [
                    '$unwind' => '$medias',
                ],
                [
                    '$group' => [
                        '_id'         => '$medias',
                        'url'         => [
                            '$first' => '$url',
                        ],
                        'image_thumb' => [
                            '$first' => '$images.url',
                        ],
                        'video_thumb' => [
                            '$first' => '$videos.thumbnail',
                        ],
                        'views'       => [
                            '$sum' => '$views',
                        ],
                    ],
                ],
                [
                    '$sort' => [
                        'views' => -1,
                    ],
                ],
                [
                    '$limit' => 20,
                ],
                [
                    '$project' => [
                        '_id'         => 0,
                        'url'         => 1,
                        'image_thumb' => 1,
                        'video_thumb' => 1,
                        'views'       => 1,
                    ],
                ],
            ];

            $data = $postCollection->aggregate($pipeline)->toArray();

            // if digital then scale the views (reach) to 3%
            // todo: parametrise this
            if ($coverageType == Platform::COVERAGE_TYPE_DIGITAL) {
                foreach ($data as &$datum) {
                    $datum['views'] = round($datum['views'] * 0.03);
                }
            }

            $this->addCacheItem($cacheKey, $data, true);
        }

        return $data;
    }

    /**
     * Most powerful media
     *
     * @param Company   $company
     * @param Label     $label
     * @param Event     $event
     * @param string    $coverageType
     * @param \DateTime $fromDate
     * @param \DateTime $toDate
     * @param string    $imagesOrVideos
     * @param Platform  $platform
     *
     * @return array
     */
    public function getMostPowerfulMediaData(
        Company $company,
        Label $label,
        Event $event,
        $coverageType,
        \DateTime $fromDate,
        \DateTime $toDate,
        $imagesOrVideos,
        Platform $platform = null
    ) {
        $cacheKey = self::STORE_PREFIX_MOST_POWERFUL_MEDIA.
            $company->getId().
            $label->getId().
            $event->getId().
            $coverageType.
            $fromDate->format('U').
            $toDate->format('U').
            $imagesOrVideos.
            ($platform ? $platform->getId() : '');

        if (self::CACHE_ENABLED && $this->redis->exists($cacheKey)) {
            $data = json_decode($this->redis->get($cacheKey), true);
        } else {
            $postCollection = $this->documentManager->getDocumentCollection('SnapRapidApiBundle:Post');

            // build aggregation pipeline
            $match = array_merge(
                $this->getPostMatch($company, $label, $event, $coverageType, $fromDate, $toDate),
                [
                    'post_type' => [
                        '$in' => [
                            $imagesOrVideos == 'images' ? Post::POST_TYPE_IMAGE : Post::POST_TYPE_VIDEO,
                            Post::POST_TYPE_BOTH,
                        ],
                    ],
                ]
            );

            // add platform match if requested
            if ($platform) {
                $match['platform'] = [
                    '$eq' => new \MongoId($platform->getId()),
                ];
            }

            $pipeline = [
                [
                    '$match' => $match,
                ],
            ];

            if ($coverageType == Platform::COVERAGE_TYPE_SOCIAL) {
                array_push(
                    $pipeline,
                    [
                        '$lookup' => [
                            'from'         => 'authors',
                            'localField'   => 'author_id',
                            'foreignField' => '_id',
                            'as'           => 'author',
                        ],
                    ],
                    [
                        '$unwind' => '$author',
                    ]
                );
            }

            array_push(
                $pipeline,
                [
                    '$project' => [
                        'published_at' => 1,
                        'url'          => 1,
                        'platform'     => 1,
                        'medias'       => 1,
                        'images'       => [
                            '$slice' => ['$images', 1],
                        ],
                        'videos'       => [
                            '$slice' => ['$videos', 1],
                        ],
                        'valuation'    => [
                            '$filter' => [
                                'input' => '$valuation',
                                'as'    => 'valuation',
                                'cond'  => [
                                    '$eq' => [
                                        '$$valuation.lid',
                                        new \MongoId($label->getId()),
                                    ],
                                ],
                            ],
                        ],
                        'impressions'  => $coverageType == Platform::COVERAGE_TYPE_SOCIAL
                            ? '$author.statistics.followers'
                            : '$statistics.reach',
                        'engagement'   => [
                            '$sum' => ['$statistics.likes', '$statistics.comments', '$statistics.shares'],
                        ],
                        'views'        => '$statistics.views',
                    ],
                ],
                [
                    '$unwind' => [
                        'path'                       => '$images',
                        'preserveNullAndEmptyArrays' => true,
                    ],
                ],
                [
                    '$unwind' => [
                        'path'                       => '$videos',
                        'preserveNullAndEmptyArrays' => true,
                    ],
                ],
                [
                    '$unwind' => '$valuation',
                ],
                [
                    '$sort' => [
                        'valuation.value' => -1,
                    ],
                ],
                [
                    '$limit' => 100,
                ],
                [
                    '$match' => [
                        'valuation.value' => [
                            '$gt' => 0,
                        ],
                    ],
                ],
                [
                    '$unwind' => '$medias',
                ],
                [
                    '$lookup' => [
                        'from'         => 'medias',
                        'localField'   => 'medias',
                        'foreignField' => '_id',
                        'as'           => 'media',
                    ],
                ],
                [
                    '$match' => [
                        'media.visual_labels.lid' => [
                            '$eq' => new \MongoId($label->getId()),
                        ],
                    ],
                ],
                [
                    '$group' => [
                        '_id'         => [
                            '_id'      => '$medias',
                            'platform' => '$platform',
                        ],
                        'url'         => [
                            '$first' => '$url',
                        ],
                        'image_thumb' => [
                            '$first' => '$images.url',
                        ],
                        'video_thumb' => [
                            '$first' => '$videos.thumbnail',
                        ],
                        'value'       => [
                            '$sum' => '$valuation.value',
                        ],
                        'impressions' => [
                            '$sum' => '$impressions',
                        ],
                        'engagement'  => [
                            '$sum' => '$engagement',
                        ],
                        'views'       => [
                            '$sum' => '$views',
                        ],
                    ],
                ],
                [
                    '$sort' => [
                        'value' => -1,
                    ],
                ],
                [
                    '$group' => [
                        '_id'         => '$_id._id',
                        'url'         => [
                            '$first' => '$url',
                        ],
                        'platforms'   => [
                            '$push' => [
                                'id'  => '$_id.platform',
                                'url' => '$url',
                            ],
                        ],
                        'image_thumb' => [
                            '$first' => '$image_thumb',
                        ],
                        'video_thumb' => [
                            '$first' => '$video_thumb',
                        ],
                        'value'       => [
                            '$sum' => '$value',
                        ],
                        'impressions' => [
                            '$sum' => '$impressions',
                        ],
                        'engagement'  => [
                            '$sum' => '$engagement',
                        ],
                        'views'       => [
                            '$sum' => '$views',
                        ],
                    ],
                ],
                [
                    '$sort' => [
                        'value' => -1,
                    ],
                ],
                [
                    '$limit' => 20,
                ],
                [
                    '$lookup' => [
                        'from'         => 'medias',
                        'localField'   => '_id',
                        'foreignField' => '_id',
                        'as'           => 'media',
                    ],
                ],
                [
                    '$unwind' => '$media',
                ],
                [
                    '$unwind' => '$media.locations',
                ],
                [
                    '$lookup' => [
                        'from'         => 'media_locations',
                        'localField'   => 'media.locations.loc_id',
                        'foreignField' => '_id',
                        'as'           => 'media_location',
                    ],
                ],
                [
                    '$match' => [
                        'media_location.alive' => [
                            '$eq' => true,
                        ],
                    ],
                ],
                [
                    '$group' => [
                        '_id'            => '$_id',
                        'url'            => [
                            '$first' => '$url',
                        ],
                        'platforms'      => [
                            '$first' => '$platforms',
                        ],
                        'image_thumb'    => [
                            '$first' => '$image_thumb',
                        ],
                        'video_thumb'    => [
                            '$first' => '$video_thumb',
                        ],
                        'media_location' => [
                            '$first' => '$media_location.url',
                        ],
                        'value'          => [
                            '$first' => '$value',
                        ],
                        'impressions'    => [
                            '$first' => '$impressions',
                        ],
                        'engagement'     => [
                            '$first' => '$engagement',
                        ],
                        'views'          => [
                            '$first' => '$views',
                        ],
                    ],
                ],
                [
                    '$sort' => [
                        'value' => -1,
                    ],
                ],
                [
                    '$limit' => 10,
                ],
                [
                    '$unwind' => '$media_location',
                ]
            );

            $data = $postCollection->aggregate($pipeline)->toArray();

            // remove binary post id and convert platform mongo ids to strings
            foreach ($data as &$datum) {
                unset($datum['_id']);
                foreach ($datum['platforms'] as &$platform) {
                    $platform['id'] = (string) $platform['id'];
                }

                // if digital then scale the impressions (reach) to 3%
                // todo: parametrise this
                if ($coverageType == Platform::COVERAGE_TYPE_DIGITAL) {
                    $datum['impressions'] = round($datum['impressions'] * 0.03);
                }
            }

            $this->addCacheItem($cacheKey, $data, true);
        }

        return $data;
    }

    /**
     * Get an array of social platform ids, ie, all the platforms except the "web" platform
     *
     * @return array
     */
    public function getSocialPlatformIds()
    {
        $cacheKey = self::STORE_KEY_SOCIAL_PLATFORM_IDS;

        if (self::CACHE_ENABLED && $this->redis->exists($cacheKey)) {
            $platformIds = $this->redis->smembers($cacheKey);
        } else {
            $platformCollection = $this->documentManager->getDocumentCollection('SnapRapidApiBundle:Platform');
            $qb                 = $platformCollection->createQueryBuilder();
            $qb->select('id');
            $qb->field('_id')->notEqual(new \MongoId($this->webPlatformId));
            $cursor      = $qb->getQuery()->execute();
            $platformIds = array_keys(iterator_to_array($cursor));

            $this->addCacheItem($cacheKey, $platformIds);
        }

        // convert string ids to mongo ids
        foreach ($platformIds as &$platformId) {
            $platformId = new \MongoId($platformId);
        }

        return $platformIds;
    }

    /**
     * Build the post pipeline match - adds the filters on label (verified), topics, published dates and post types
     *
     * @param Company   $company
     * @param Label     $label
     * @param Event     $event
     * @param string    $coverageType
     * @param \DateTime $fromDate
     * @param \DateTime $toDate
     *
     * @return array
     */
    protected function getPostMatch(
        Company $company,
        Label $label,
        Event $event,
        $coverageType,
        \DateTime $fromDate,
        \DateTime $toDate
    ) {
        // get topic ids
        $topicIds = $this->getMatchingTopicIds($company, $event);
        foreach ($topicIds as &$topicId) {
            $topicId = new \MongoId($topicId);
        }

        // add one day to the end date to ensure we get the results posted on the toDate
        $toDate = clone $toDate;
        $toDate->add(new \DateInterval('P1D'));

        // set up post match
        $postMatch = [
            'verified'     => [
                '$eq' => new \MongoId($label->getId()),
            ],
            'topics'       => [
                '$in' => $topicIds,
            ],
            'published_at' => [
                '$gte' => new \MongoDate($fromDate->getTimestamp()),
                '$lte' => new \MongoDate($toDate->getTimestamp()),
            ],
            'post_type'    => [
                '$in' => [Post::POST_TYPE_IMAGE, Post::POST_TYPE_VIDEO, Post::POST_TYPE_BOTH],
            ],
        ];

        // add coverage match
        // note: not using the simpler $ne operator as this prevents mongo from using the index on this field
        if ($coverageType == Platform::COVERAGE_TYPE_SOCIAL) {
            $platformIds           = $this->getSocialPlatformIds();
            $postMatch['platform'] = [
                '$in' => $platformIds,
            ];
        } else {
            $postMatch['platform'] = [
                '$eq' => new \MongoId($this->webPlatformId),
            ];
        }

        return $postMatch;
    }

    /**
     * Add a cache item to redis
     * Add the key to the list of company and/or event cache keys to facilitate cache-invalidation
     *
     * @param string       $cacheKey
     * @param mixed        $data
     * @param bool         $encodeData
     * @param Company|null $company
     * @param Event|null   $event
     * @param \DateTime    $maxDate
     */
    protected function addCacheItem(
        $cacheKey,
        array $data,
        $encodeData = false,
        Company $company = null,
        Event $event = null,
        \DateTime $maxDate = null
    ) {
        if (count($data)) {
            $this->redis->del($cacheKey);
            if ($encodeData) {
                $this->redis->set($cacheKey, json_encode($data));
            } else {
                $this->redis->sadd($cacheKey, $data);
            }

            if ($company) {
                $this->redis->sadd(self::STORE_PREFIX_COMPANY_KEYS.$company->getId(), [$cacheKey]);
            }
            if ($event) {
                $this->redis->sadd(self::STORE_PREFIX_EVENT_KEYS.$event->getId(), [$cacheKey]);
            }

            if ($maxDate) {
                // if max date for the date range is in the future then set cache to 1 hour
                if ($maxDate > new \DateTime()) {
                    $this->redis->expire($cacheKey, 3600);
                } else {
                    // else we can let it sit a bit longer; 4 hours
                    $this->redis->expire($cacheKey, 14400);
                }
            } else {
                // not date-dependant so use default expiry of 1 day
                $this->redis->expire($cacheKey, 86400);
            }
        }
    }

    /**
     * Fill missing data entries
     *
     * Ensures every date between the from and to dates have an entry as defined by $emptyEntry
     *
     * @param           $data
     * @param           $emptyEntry
     * @param \DateTime $fromDate
     * @param \DateTime $toDate
     *
     * @return mixed
     */
    protected function fillMissingDataEntries($data, $emptyEntry, \DateTime $fromDate, \DateTime $toDate)
    {
        // fill missing data
        $itDate = clone $fromDate;
        while ($itDate <= $toDate) {
            $dateKey = $itDate->format('Y-m-d');
            if (!isset($data[$dateKey])) {
                $data[$dateKey] = $emptyEntry;
            }
            $itDate->add(new \DateInterval('P1D'));
        }
        ksort($data);

        return $data;
    }
}
