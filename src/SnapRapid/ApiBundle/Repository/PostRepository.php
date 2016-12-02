<?php

namespace SnapRapid\ApiBundle\Repository;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ODM\MongoDB\Query\Builder;
use SnapRapid\ApiBundle\Repository\Base\ReadOnlyDocumentRepository;
use SnapRapid\Core\Manager\DashboardManager;
use SnapRapid\Core\Model\Platform;
use SnapRapid\Core\Repository\PostRepositoryInterface;

class PostRepository extends ReadOnlyDocumentRepository implements PostRepositoryInterface
{
    use Traits\PageableEntity;

    /**
     * @var DashboardManager
     */
    private $dashboardManager;

    /**
     * @var string
     */
    private $webPlatformId;

    /**
     * @param ObjectManager    $documentManager
     * @param DashboardManager $dashboardManager
     * @param string           $webPlatformId
     */
    public function __construct(ObjectManager $documentManager, DashboardManager $dashboardManager, $webPlatformId)
    {
        $this->dashboardManager = $dashboardManager;
        $this->webPlatformId    = $webPlatformId;

        parent::__construct($documentManager);
    }

    /**
     * Get results query builder
     *
     * @param array $filters
     * @param array $sorting
     *
     * @return Builder
     */
    public function getResultsQueryBuilder(array $filters = [], array $sorting = [])
    {
        /** @var Builder $qb */
        $qb = $this->documentRepository->createQueryBuilder($this->getEntityName());

        // add filters
        foreach ($filters as $field => $value) {
            if ($field == 'label') {
                $qb->addAnd(
                    $qb->expr()
                        ->field('verified')
                        ->equals(new \MongoId($value->getId()))
                );
            } elseif ($field == 'topics') {
                foreach ($value as &$topicId) {
                    $topicId = new \MongoId($topicId);
                }
                $qb->addAnd(
                    $qb->expr()
                        ->field('topics')
                        ->in($value)
                );
            } elseif ($field == 'coverageType') {
                // add coverage match
                // note: not using the simpler $ne operator as this prevents mongo from using the index on this field
                if ($value == Platform::COVERAGE_TYPE_SOCIAL) {
                    $expr = $qb->expr()
                        ->field('platform')
                        ->in($this->dashboardManager->getSocialPlatformIds());
                } else {
                    $expr = $qb->expr()
                        ->field('platform')
                        ->equals(new \MongoId($this->webPlatformId));
                }
                $qb->addAnd($expr);
            } elseif ($field == 'fromDate') {
                $qb->addAnd(
                    $qb->expr()
                        ->field('publishedAt')
                        ->gte(new \MongoDate($value->getTimestamp()))
                );
            } elseif ($field == 'toDate') {
                $toDate = clone $value;
                $toDate->add(new \DateInterval('P1D'));
                $qb->addAnd(
                    $qb->expr()
                        ->field('publishedAt')
                        ->lte(new \MongoDate($toDate->getTimestamp()))
                );
            } elseif ($field == 'postType') {
                $qb->addAnd(
                    $qb->expr()
                        ->field('postType')
                        ->in($value)
                );
            } else {
                $qb->addAnd(
                    $qb->expr()
                        ->field($field)
                        ->equals(new \MongoRegex('/.*'.$value.'.*/i'))
                );
            }
        }

        // set ordering
        foreach ($sorting as $field => $order) {
            $qb->sort($field, $order);
        }

        return $qb;
    }

    /**
     * @return array
     */
    protected function getPrimeFields()
    {
        return ['author'];
    }

    protected function getEntityName()
    {
        return 'SnapRapidApiBundle:Post';
    }
}
