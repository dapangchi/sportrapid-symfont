<?php

namespace SnapRapid\ApiBundle\Repository;

use Doctrine\ODM\MongoDB\Query\Builder;
use Gedmo\Tree\Document\MongoDB\Repository\MaterializedPathRepository;
use SnapRapid\ApiBundle\Repository\Base\PersistableDocumentRepository;
use SnapRapid\Core\Model\Event;
use SnapRapid\Core\Repository\EventRepositoryInterface;

class EventRepository extends PersistableDocumentRepository implements EventRepositoryInterface
{
    use Traits\PageableEntity;

    /**
     * @var MaterializedPathRepository
     */
    protected $documentRepository;

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
        $qb = $this->documentRepository->createQueryBuilder();

        // add filters
        foreach ($filters as $field => $value) {
            // handle the valid-parents-for filter separately
            if ($field == 'validParentsFor' && $value) {
                $child = $this->get($value);
                if ($child) {
                    // get children
                    $childrenQb = $this->documentRepository->getChildrenQueryBuilder(
                        $child,
                        false,
                        'name',
                        'asc',
                        true
                    );
                    $children   = $childrenQb->select('id')->getQuery()->execute();

                    // build list of node ids that would not be valid parents of this event
                    $invalidParentIds = [];
                    foreach ($children as $child) {
                        $invalidParentIds[] = $child->getId();
                    }

                    // exclude these from the list
                    if (count($invalidParentIds)) {
                        $qb->addAnd(
                            $qb->expr()
                                ->field('id')
                                ->notIn($invalidParentIds)
                        );
                    }
                }
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
     * Get a list of all the events that are descended from this event
     *
     * @param Event $event
     *
     * @return array
     */
    public function getDescendantEventIds(Event $event)
    {
        // get children
        $childrenQb = $this->documentRepository->getChildrenQueryBuilder($event);
        $children   = $childrenQb->select('id')->getQuery()->execute();

        // build list of node ids
        $descendantIds = [];
        foreach ($children as $child) {
            $descendantIds[] = $child->getId();
        }

        return $descendantIds;
    }

    /**
     * Get a collection of the direct children of this event
     *
     * @param Event $event
     *
     * @return array|mixed
     */
    public function getChildEvents(Event $event)
    {
        $qb = $this->documentRepository->getChildrenQueryBuilder($event);
        $qb
            ->refresh(true)
            ->field('parent')
            ->references($event);

        $children = $qb->getQuery()->execute()->toArray();

        return array_values($children);
    }

    protected function getEntityName()
    {
        return 'SnapRapidApiBundle:Event';
    }
}
