<?php

namespace SnapRapid\ApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Hateoas\Configuration\Route;
use Hateoas\Representation\Factory\PagerfantaFactory;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Class TopicController
 *
 * @Rest\Route("/topic")
 */
class TopicController extends BaseController
{
    /**
     * Get a collection of topics.
     *
     * @ApiDoc(
     *   section = "Topic",
     *   description = "Get a collection of topics",
     *   statusCodes = {
     *     200 = "Topic collection found and returned",
     *     403 = "Not authorised",
     *     401 = "Not authenticated"
     *   }
     * )
     *
     * @Rest\Get("", name="get_topic_collection")
     *
     * @Rest\QueryParam(
     *   name = "filter",
     *   array = true,
     *   description = "Filters to apply"
     * )
     * @Rest\QueryParam(
     *   name = "sorting",
     *   array = true,
     *   description = "Order in which to return results"
     * )
     * @Rest\QueryParam(
     *   name = "count",
     *   requirements = "\d+",
     *   default = 25,
     *   description = "Number of topics to return"
     * )
     * @Rest\QueryParam(
     *   name = "page",
     *   requirements = "\d+",
     *   default = 1,
     *   description = "Page number"
     * )
     * @Rest\View(serializerGroups={"Default", "TopicList"})
     *
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param ParamFetcherInterface $paramFetcher
     *
     * @return array
     */
    public function getTopicCollectionAction(ParamFetcherInterface $paramFetcher)
    {
        $pager = $this->get('topic_repository')->getResultsPager(
            $paramFetcher->get('filter'),
            $paramFetcher->get('sorting'),
            $paramFetcher->get('count'),
            $paramFetcher->get('page')
        );

        $pagerFactory = new PagerfantaFactory();

        return $pagerFactory->createRepresentation(
            $pager,
            new Route('get_topic_collection', $paramFetcher->all())
        );
    }
}
