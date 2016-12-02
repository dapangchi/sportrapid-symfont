<?php

namespace SnapRapid\ApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Hateoas\Configuration\Route;
use Hateoas\Representation\Factory\PagerfantaFactory;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use SnapRapid\Core\Model\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class EventController
 *
 * @Rest\Route("/event")
 */
class EventController extends BaseController
{
    /**
     * Create a new Event
     *
     * @ApiDoc(
     *   section = "Event",
     *   description = "Create a new Event",
     *   resource = true,
     *   input = { "class" = "event_form", "name" = "" },
     *   output = "SnapRapid\Core\Model\Event",
     *   statusCodes = {
     *     201 = "Event created successfully",
     *     400 = "Errors in the submitted form",
     *     401 = "Not authenticated",
     *     403 = "Not authorized",
     *     409 = "There is a conflict with an existing Event"
     *   }
     * )
     *
     * @Rest\Post("", name="create_event")
     * @Rest\RequestParam(
     *   name = "event",
     *   description = "Event form",
     *   array = true,
     *   strict = true
     * )
     * @Rest\View(serializerGroups={"Default", "EventEdit"}, serializerEnableMaxDepthChecks=true)
     *
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param Request $request
     *
     * @return View
     */
    public function createEventAction(Request $request)
    {
        $event = $this->get('event_manager')->createNewEvent();

        return $this->processEventForm($event, $request);
    }

    /**
     * Update an Event's details
     *
     * @ApiDoc(
     *   section = "Event",
     *   description = "Update an Event's details",
     *   resource = true,
     *   input = { "class" = "event_form", "name" = "" },
     *   output = "SnapRapid\Core\Model\Event",
     *   statusCodes = {
     *     200 = "Event updated",
     *     400 = "Errors in the submitted form",
     *     401 = "Not authenticated",
     *     403 = "User is not authorised to update this Event",
     *     404 = "Event not found"
     *   }
     * )
     *
     * @Rest\Patch("/{id}", requirements={"id": "[a-zA-Z0-9]+"}, name="update_event")
     * @Rest\View(serializerGroups={"Default", "EventEdit"}, serializerEnableMaxDepthChecks=true)
     *
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param Event   $event
     * @param Request $request
     *
     * @return View
     */
    public function updateEventAction(Event $event, Request $request)
    {
        return $this->processEventForm($event, $request);
    }

    /**
     * Get an Event by id
     *
     * @ApiDoc(
     *   section = "Event",
     *   description = "Get a event",
     *   resource = true,
     *   output = "SnapRapid\Core\Model\Event",
     *   statusCodes = {
     *     200 = "Event found and returned",
     *     401 = "Not authenticated",
     *     404 = "Event not found"
     *   }
     * )
     *
     * @Rest\Get("/{id}", requirements={"id": "[a-zA-Z0-9]+"}, name="get_event")
     * @Rest\QueryParam(
     *   name = "company",
     *   description = "Company request filtering (must be included for non-admin users)"
     * )
     * @Rest\View(serializerGroups={"Default"}, serializerEnableMaxDepthChecks=true)
     *
     * @Security("has_role('ROLE_USER')")
     *
     * @param Event                 $event
     * @param ParamFetcherInterface $paramFetcher
     *
     * @return View
     */
    public function getEventAction(Event $event, ParamFetcherInterface $paramFetcher)
    {
        $companyId = $paramFetcher->get('company');
        $isAdmin   = $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN');

        // non-admin users must include a company
        if (!$companyId && !$isAdmin) {
            throw new AccessDeniedException();
        }

        // load the company and check access
        $company = null;
        if ($companyId) {
            // the given company must exist
            $company = $this->get('company_repository')->get($companyId);
            if (!$company || !$company->isEnabled()) {
                throw new NotFoundHttpException();
            }

            // the user must be allowed to view this company
            if (!$isAdmin && !$company->isMember($this->getSecurityUser())) {
                throw new AccessDeniedException();
            }
        }

        // add child events
        $eventManager = $this->get('event_manager');
        $eventManager->addChildEvents($event, $company);

        // set up view
        $view = View::create();
        $view->setData($event);

        // set the serializer groups
        $serializerGroups = $this->getSerializerGroupsFromAnnotations();
        if ($isAdmin) {
            $serializerGroups[] = 'EventEdit';
        }
        $this->setSerializerGroups($view, $serializerGroups);

        return $view;
    }

    /**
     * Remove a Event
     *
     * @ApiDoc(
     *   section = "Event",
     *   description = "Remove a Event",
     *   statusCodes = {
     *     204 = "Event removed",
     *     401 = "Not authenticated",
     *     403 = "User is not authorised to remove this Event",
     *     404 = "Event not found"
     *   }
     * )
     *
     * @Rest\Delete("/{id}", requirements={"id": "[a-zA-Z0-9]+"}, name="remove_event")
     * @Rest\View
     *
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param Event $event
     */
    public function removeEventAction(Event $event)
    {
        $this->get('event_manager')->removeEvent($event);
    }

    /**
     * Get a collection of Events
     *
     * @ApiDoc(
     *   section = "Event",
     *   description = "Get a collection of Events",
     *   statusCodes = {
     *     200 = "Event collection found and returned",
     *     403 = "Not authorised",
     *     401 = "Not authenticated"
     *   }
     * )
     *
     * @Rest\Get("", name="get_event_collection")
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
     *   description = "Number of events to return"
     * )
     * @Rest\QueryParam(
     *   name = "page",
     *   requirements = "\d+",
     *   default = 1,
     *   description = "Page number"
     * )
     * @Rest\View(serializerGroups={"Default", "EventList"}, serializerEnableMaxDepthChecks=true)
     *
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param ParamFetcherInterface $paramFetcher
     *
     * @return array
     */
    public function getEventCollectionAction(ParamFetcherInterface $paramFetcher)
    {
        $pager = $this->get('event_repository')->getResultsPager(
            $paramFetcher->get('filter'),
            $paramFetcher->get('sorting'),
            $paramFetcher->get('count'),
            $paramFetcher->get('page')
        );

        $pagerFactory = new PagerfantaFactory();

        return $pagerFactory->createRepresentation(
            $pager,
            new Route('get_event_collection', $paramFetcher->all())
        );
    }

    /**
     * Process an Event form (create or update)
     *
     * @param Event   $event
     * @param Request $request
     *
     * @return View
     */
    private function processEventForm(Event $event, Request $request)
    {
        $isNew = $event->isNew();
        $form  = $this->createApiForm(
            'event_form',
            $event,
            [
                'method' => $isNew ? 'POST' : 'PATCH',
            ]
        );
        $form->handleRequest($request);

        if ($form->isValid()) {
            $eventManager = $this->get('event_manager');
            if ($isNew) {
                $eventManager->saveNewEvent($event);
                $eventManager->decorateEvent($event);

                return View::create(
                    $event,
                    201,
                    [
                        'Location' => $this->generateUrl(
                            'get_event',
                            ['id' => $event->getId()],
                            true
                        ),
                    ]
                );
            } else {
                $eventManager->updateEvent($event);
                $eventManager->decorateEvent($event);

                return View::create($event, 200);
            }
        }

        // form was not valid
        return View::create($form, 400);
    }
}
