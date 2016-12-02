<?php

namespace SnapRapid\ApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Hateoas\Configuration\Route;
use Hateoas\Representation\Factory\PagerfantaFactory;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Class LabelController
 *
 * @Rest\Route("/label")
 */
class LabelController extends BaseController
{
    /**
     * Get a collection of labels.
     *
     * @ApiDoc(
     *   section = "Label",
     *   description = "Get a collection of labels",
     *   statusCodes = {
     *     200 = "Label collection found and returned",
     *     403 = "Not authorised",
     *     401 = "Not authenticated"
     *   }
     * )
     *
     * @Rest\Get("", name="get_label_collection")
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
     *   description = "Number of labels to return"
     * )
     * @Rest\QueryParam(
     *   name = "page",
     *   requirements = "\d+",
     *   default = 1,
     *   description = "Page number"
     * )
     * @Rest\View(serializerGroups={"Default", "LabelList"})
     *
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param ParamFetcherInterface $paramFetcher
     *
     * @return array
     */
    public function getLabelCollectionAction(ParamFetcherInterface $paramFetcher)
    {
        $pager = $this->get('label_repository')->getResultsPager(
            $paramFetcher->get('filter'),
            $paramFetcher->get('sorting') ?: ['name' => 'asc'],
            $paramFetcher->get('count'),
            $paramFetcher->get('page')
        );

        $pagerFactory = new PagerfantaFactory();

        return $pagerFactory->createRepresentation(
            $pager,
            new Route('get_label_collection', $paramFetcher->all())
        );
    }
}
