<?php

namespace SnapRapid\ApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Hateoas\Configuration\Route;
use Hateoas\Representation\Factory\PagerfantaFactory;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class QueryController
 *
 * @Rest\Route("/query")
 */
class QueryController extends BaseController
{
    /**
     * Get a collection of queries.
     *
     * @ApiDoc(
     *   section = "Query",
     *   description = "Get a collection of queries",
     *   statusCodes = {
     *     200 = "Query collection found and returned",
     *     403 = "Not authorised",
     *     401 = "Not authenticated"
     *   }
     * )
     *
     * @Rest\Get("", name="get_query_collection")
     *
     * @Rest\QueryParam(
     *   name = "company",
     *   requirements = "[a-zA-Z0-9]+",
     *   description = "Company ID to restrict results to"
     * )
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
     *   description = "Number of queries to return"
     * )
     * @Rest\QueryParam(
     *   name = "page",
     *   requirements = "\d+",
     *   default = 1,
     *   description = "Page number"
     * )
     * @Rest\View(serializerGroups={"Default", "QueryList"})
     *
     * @Security("has_role('ROLE_USER')")
     *
     * @param ParamFetcherInterface $paramFetcher
     *
     * @return array
     */
    public function getQueryCollectionAction(ParamFetcherInterface $paramFetcher)
    {
        $filter = $paramFetcher->get('filter');

        // add company to the filters
        $companyId = $paramFetcher->get('company');
        $isAdmin   = $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN');
        if ($companyId) {
            // load company
            $company = $this->get('company_repository')->get($companyId);
            if (!$company) {
                throw new NotFoundHttpException();
            }

            // check that the user is allowed to view this company
            if (!$isAdmin && !$company->isMember($this->getSecurityUser())) {
                throw new AccessDeniedException();
            }

            // add company filter
            $filter['company'] = $company;
        } elseif (!$isAdmin) {
            // only admins can see the full list of queries
            throw new AccessDeniedException();
        }

        $pager = $this->get('query_repository')->getResultsPager(
            $filter,
            $paramFetcher->get('sorting'),
            $paramFetcher->get('count'),
            $paramFetcher->get('page')
        );

        $pagerFactory = new PagerfantaFactory();

        return $pagerFactory->createRepresentation(
            $pager,
            new Route('get_query_collection', $paramFetcher->all())
        );
    }
}
