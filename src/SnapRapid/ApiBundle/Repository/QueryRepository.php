<?php

namespace SnapRapid\ApiBundle\Repository;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ODM\MongoDB\Query\Builder;
use SnapRapid\ApiBundle\Repository\Base\ReadOnlyDocumentRepository;
use SnapRapid\Core\Manager\DashboardManager;
use SnapRapid\Core\Model\Company;
use SnapRapid\Core\Repository\QueryRepositoryInterface;

class QueryRepository extends ReadOnlyDocumentRepository implements QueryRepositoryInterface
{
    use Traits\PageableEntity;

    /**
     * @var DashboardManager
     */
    private $dashboardManager;

    /**
     * @param ObjectManager    $documentManager
     * @param DashboardManager $dashboardManager
     */
    public function __construct(ObjectManager $documentManager, DashboardManager $dashboardManager)
    {
        $this->dashboardManager = $dashboardManager;

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
            // handle company filter separately
            if ($field == 'company' && is_object($value) && get_class($value) == 'SnapRapid\Core\Model\Company') {
                /** @var Company $company */
                $company = $value;

                // get all topic ids for all events the company is associated with
                $topicIds = [];
                foreach ($company->getEvents() as $event) {
                    $topicIds = array_merge($topicIds, $this->dashboardManager->getMatchingTopicIds($company, $event));
                }
                $topicIds = array_unique($topicIds);

                // convert to mongo ids
                foreach ($topicIds as &$topicId) {
                    $topicId = new \MongoId($topicId);
                }

                $qb->addAnd(
                    $qb->expr()
                        ->field('topics')
                        ->in($topicIds)
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

    protected function getEntityName()
    {
        return 'SnapRapidApiBundle:Query';
    }
}
