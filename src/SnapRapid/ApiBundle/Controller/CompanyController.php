<?php

namespace SnapRapid\ApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Hateoas\Configuration\Route;
use Hateoas\Representation\Factory\PagerfantaFactory;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use SnapRapid\Core\Exception\InvalidEntityException;
use SnapRapid\Core\Model\Company;
use SnapRapid\Core\Model\CompanyMember;
use SnapRapid\Core\Model\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CompanyController
 *
 * @Rest\Route("/company")
 */
class CompanyController extends BaseController
{
    /**
     * Create a new Company
     *
     * @ApiDoc(
     *   section = "Company",
     *   description = "Create a new Company",
     *   resource = true,
     *   input = { "class" = "company_form", "name" = "" },
     *   output = "SnapRapid\Core\Model\Company",
     *   statusCodes = {
     *     201 = "Company created successfully",
     *     400 = "Errors in the submitted form",
     *     401 = "Not authenticated",
     *     403 = "Not authorized",
     *     409 = "There is a conflict with an existing Company"
     *   }
     * )
     *
     * @Rest\Post("", name="create_company")
     * @Rest\RequestParam(
     *   name = "company",
     *   description = "Company form",
     *   array = true,
     *   strict = true
     * )
     * @Rest\View(serializerGroups={"Default", "CompanyEdit"})
     *
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param Request $request
     *
     * @return View
     */
    public function createCompanyAction(Request $request)
    {
        $company = $this->get('company_manager')->createNewCompany($this->getUser());

        return $this->processCompanyForm($company, $request, $this->getUser());
    }

    /**
     * Update an Company's details
     *
     * @ApiDoc(
     *   section = "Company",
     *   description = "Update an Company's details",
     *   resource = true,
     *   input = { "class" = "company_form", "name" = "" },
     *   output = "SnapRapid\Core\Model\Company",
     *   statusCodes = {
     *     200 = "Company updated",
     *     400 = "Errors in the submitted form",
     *     401 = "Not authenticated",
     *     403 = "User is not authorised to update this Company",
     *     404 = "Company not found"
     *   }
     * )
     *
     * @Rest\Patch("/{id}", requirements={"id": "[a-zA-Z0-9]+"}, name="update_company")
     * @Rest\View(serializerGroups={"Default", "CompanyEdit"})
     *
     * @Security("has_role('ROLE_ADMIN') || has_role('ROLE_USER') && company.isAdminMember(user)")
     *
     * @param Company $company
     * @param Request $request
     *
     * @return View
     */
    public function updateCompanyAction(Company $company, Request $request)
    {
        return $this->processCompanyForm($company, $request, $this->getUser());
    }

    /**
     * Get an Company by id
     *
     * @ApiDoc(
     *   section = "Company",
     *   description = "Get a company",
     *   resource = true,
     *   output = "SnapRapid\Core\Model\Company",
     *   statusCodes = {
     *     200 = "Company found and returned",
     *     401 = "Not authenticated",
     *     404 = "Company not found"
     *   }
     * )
     *
     * @Rest\Get("/{id}", requirements={"id": "[a-zA-Z0-9]+"}, name="get_company")
     * @Rest\View(serializerGroups={"Default"})
     *
     * @Security("has_role('ROLE_ADMIN') || has_role('ROLE_USER') && company.isMember(user)")
     *
     * @param Company $company
     *
     * @return View
     */
    public function getCompanyAction(Company $company)
    {
        $view = View::create();

        // decorate the company
        $companyManager = $this->get('company_manager');
        $companyManager->decorateCompany($company);
        $view->setData($company);

        // set the serializer groups
        $serializerGroups = $this->getSerializerGroupsFromAnnotations();
        if ($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')
            || $company->isAdminMember($this->getSecurityUser())
        ) {
            $serializerGroups[] = 'CompanyEdit';
        }
        $this->setSerializerGroups($view, $serializerGroups);

        return $view;
    }

    /**
     * Remove a Company
     *
     * @ApiDoc(
     *   section = "Company",
     *   description = "Remove a Company",
     *   statusCodes = {
     *     204 = "Company removed",
     *     401 = "Not authenticated",
     *     403 = "User is not authorised to remove this Company",
     *     404 = "Company not found"
     *   }
     * )
     *
     * @Rest\Delete("/{id}", requirements={"id": "[a-zA-Z0-9]+"}, name="remove_company")
     * @Rest\View
     *
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param Company $company
     */
    public function removeCompanyAction(Company $company)
    {
        $this->get('company_manager')->removeCompany($company, $this->getUser());
    }

    /**
     * Get a collection of Companies
     *
     * @ApiDoc(
     *   section = "Company",
     *   description = "Get a collection of Companies",
     *   statusCodes = {
     *     200 = "Company collection found and returned",
     *     403 = "Not authorised",
     *     401 = "Not authenticated"
     *   }
     * )
     *
     * @Rest\Get("", name="get_company_collection")
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
     *   description = "Number of companies to return"
     * )
     * @Rest\QueryParam(
     *   name = "page",
     *   requirements = "\d+",
     *   default = 1,
     *   description = "Page number"
     * )
     * @Rest\QueryParam(
     *   name = "paginated",
     *   requirements = "true|false",
     *   default = "true",
     *   description = "Return a paginator or not"
     * )
     * @Rest\View(serializerGroups={"Default", "CompanyList"}, serializerEnableMaxDepthChecks=true)
     *
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param ParamFetcherInterface $paramFetcher
     *
     * @return array
     */
    public function getCompanyCollectionAction(ParamFetcherInterface $paramFetcher)
    {
        if ($paramFetcher->get('paginated') == 'true') {
            $pager = $this->get('company_repository')->getResultsPager(
                $paramFetcher->get('filter'),
                $paramFetcher->get('sorting'),
                $paramFetcher->get('count'),
                $paramFetcher->get('page')
            );

            $pagerFactory = new PagerfantaFactory();

            return $pagerFactory->createRepresentation(
                $pager,
                new Route('get_company_collection', $paramFetcher->all())
            );
        } else {
            $companyManager = $this->get('company_manager');
            $companies      = [];
            foreach (iterator_to_array(
                         $this->get('company_repository')->getAll()
                     ) as $company) {
                $companies[] = $companyManager->decorateCompany($company);
            }

            return $companies;
        }
    }

    /**
     * Add a member to an Company
     *
     * @ApiDoc(
     *   section = "Company",
     *   description = "Add a member",
     *   input = { "class" = "company_member_form", "name" = "" },
     *   statusCodes = {
     *     201 = "Member added to Company",
     *     400 = "Errors in the submitted member form",
     *     401 = "Not authenticated",
     *     403 = "User is not authorised to update this Company",
     *     404 = "Company not found"
     *   }
     * )
     *
     * @Rest\Post("/{id}/member", requirements={"id": "[a-zA-Z0-9]+"}, name="create_company_member")
     * @Rest\View(serializerGroups={"Default", "CompanyEdit"})
     *
     * @Security("has_role('ROLE_ADMIN') || has_role('ROLE_USER') && company.isAdminMember(user)")
     *
     * @param Company $company
     * @param Request $request
     *
     * @return View
     */
    public function createCompanyMemberAction(Company $company, Request $request)
    {
        $companyMember = new CompanyMember();
        $companyMember->setCompany($company);

        return $this->processCompanyMemberForm($companyMember, $request);
    }

    /**
     * Update a member within an Company.
     * Used to accept or confirm the member into the company or update details.
     *
     * @ApiDoc(
     *   section = "Company",
     *   description = "Update a member",
     *   statusCodes = {
     *     200 = {
     *       "Member details updated",
     *       "Member confirmed"
     *     },
     *     400 = "Errors in the submitted member form",
     *     401 = "Not authenticated",
     *     403 = "User is not authorised to update this Company",
     *     404 = {
     *       "Company not found",
     *       "Member not found"
     *     }
     *   }
     * )
     *
     * @Rest\Patch(
     *   "/{id}/member/{memberId}",
     *   requirements={"id": "[a-zA-Z0-9]+", "memberId": "[a-zA-Z0-9]+"},
     *   name="update_company_member"
     * )
     * @ParamConverter("companyMember", class="SnapRapidApiBundle:CompanyMember", options={"id" = "memberId"})
     * @Rest\View(serializerGroups={"Default", "CompanyEdit"})
     *
     * @Security("has_role('ROLE_ADMIN') || has_role('ROLE_USER') && company.isAdminMember(user)")
     *
     * @param Company       $company
     * @param CompanyMember $companyMember
     * @param Request       $request
     *
     * @return View
     */
    public function updateCompanyMemberAction(Company $company, CompanyMember $companyMember, Request $request)
    {
        return $this->processCompanyMemberForm($companyMember, $request);
    }

    /**
     * Remove a member from an Company
     *
     * @ApiDoc(
     *   section = "Company",
     *   description = "Remove a member",
     *   statusCodes = {
     *     204 = "Member removed",
     *     400 = "Could not remove member",
     *     401 = "User is not authorised to update this Company",
     *     404 = {
     *       "Company not found",
     *       "Member not found"
     *     }
     *   }
     * )
     *
     * @Rest\Delete(
     *   "/{id}/member/{memberId}",
     *   requirements={"id": "[a-zA-Z0-9]+", "memberId": "[a-zA-Z0-9]+"},
     *   name="remove_company_member"
     * )
     * @ParamConverter("companyMember", class="SnapRapidApiBundle:CompanyMember", options={"id" = "memberId"})
     * @Rest\View
     *
     * @Security("has_role('ROLE_ADMIN') || has_role('ROLE_USER') && company.isAdminMember(user)")
     *
     * @param Company       $company
     * @param CompanyMember $companyMember
     *
     * @return View
     */
    public function removeCompanyMemberAction(Company $company, CompanyMember $companyMember)
    {
        $companyManager = $this->get('company_manager');
        try {
            $companyManager->removeCompanyMember($companyMember);
        } catch (InvalidEntityException $e) {
            return View::create(
                [
                    'error' => $e->getMessage(),
                ],
                400
            );
        }
    }

    /**
     * Resend invitation email
     *
     * @ApiDoc(
     *   section = "Company",
     *   description = "Resend member invitation email",
     *   statusCodes = {
     *     204 = "Invitation email resent",
     *     404 = "Company or company member not found"
     *   }
     * )
     *
     * @Rest\Post(
     *   "/{id}/member/{memberId}/resend-invitation",
     *   requirements={"id": "[a-zA-Z0-9]+", "memberId": "[a-zA-Z0-9]+"},
     *   name="resend_invite_company_member"
     * )
     * @ParamConverter("companyMember", class="SnapRapidApiBundle:CompanyMember", options={"id" = "memberId"})
     *
     * @Security("has_role('ROLE_ADMIN') || has_role('ROLE_USER') && company.isAdminMember(user)")
     *
     * @param Company       $company
     * @param CompanyMember $companyMember
     *
     * @return View
     */
    public function resendCompanyMemberInvitationAction(Company $company, CompanyMember $companyMember)
    {
        $companyManager = $this->get('company_manager');
        $companyManager->resendInvitation($companyMember);

        return View::create(null, 204);
    }

    /**
     * Process an Company form (create or update)
     *
     * @param Company $company
     * @param Request $request
     * @param User    $activeUser
     *
     * @return View
     */
    private function processCompanyForm(Company $company, Request $request, User $activeUser)
    {
        $isNew = $company->isNew();
        if (!$isNew) {
            $oldCompany = clone $company;
        }
        $form = $this->createApiForm(
            'company_form',
            $company,
            [
                'method' => $isNew ? 'POST' : 'PATCH',
                'roles'  => $activeUser->getRoles(),
            ]
        );
        $form->handleRequest($request);

        if ($form->isValid()) {
            $companyManager = $this->get('company_manager');
            if ($isNew) {
                $companyManager->saveNewCompany($company, $this->getUser());
                $companyManager->decorateCompany($company);

                return View::create(
                    $company,
                    201,
                    [
                        'Location' => $this->generateUrl(
                            'get_company',
                            ['id' => $company->getId()],
                            true
                        ),
                    ]
                );
            } else {
                $companyManager->updateCompany($company, $oldCompany, $this->getUser());
                $companyManager->decorateCompany($company);

                return View::create($company, 200);
            }
        }

        // form was not valid
        return View::create($form, 400);
    }

    /**
     * Process an Company member form (create or update)
     *
     * @param CompanyMember $companyMember
     * @param Request       $request
     *
     * @return View
     */
    private function processCompanyMemberForm(CompanyMember $companyMember, Request $request)
    {
        $isNew = $companyMember->isNew();
        $form  = $this->createApiForm(
            'company_member_form',
            $companyMember,
            [
                'method'            => $isNew ? 'POST' : 'PATCH',
                'validation_groups' => [$isNew ? 'AddMember' : 'Default'],
                'is_new'            => $isNew,
            ]
        );
        $form->handleRequest($request);

        if ($form->isValid()) {
            $companyManager = $this->get('company_manager');
            if ($isNew) {
                $companyManager->saveNewCompanyMember($companyMember, $this->getUser());

                return View::create(null, 201);
            } else {
                $companyManager->updateCompanyMember($companyMember, $this->getUser());

                return View::create($companyMember, 200);
            }
        }

        // form was not valid
        return View::create($form, 400);
    }
}
