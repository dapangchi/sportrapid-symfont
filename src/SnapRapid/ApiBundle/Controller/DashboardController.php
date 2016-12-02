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
use SnapRapid\Core\Exception\InvalidArgumentsException;
use SnapRapid\Core\Model\Company;
use SnapRapid\Core\Model\Event;
use SnapRapid\Core\Model\Label;
use SnapRapid\Core\Model\Platform;
use SnapRapid\Core\Model\Post;
use SnapRapid\Core\Model\User;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class DashboardController
 *
 * @Rest\Route(
 *     "/dashboard/{companyId}/{labelId}/{eventId}/{coverageType}",
 *     requirements = {
 *         "companyId": "[a-zA-Z0-9]+",
 *         "labelId": "[a-zA-Z0-9]+",
 *         "eventId": "[a-zA-Z0-9]+",
 *         "coverageType": "social|digital",
 *     }
 * )
 * @ParamConverter("company", class="SnapRapidApiBundle:Company", options={"id" = "companyId"})
 * @ParamConverter("label", class="SnapRapidApiBundle:Label", options={"id" = "labelId"})
 * @ParamConverter("event", class="SnapRapidApiBundle:Event", options={"id" = "eventId"})
 * @Rest\QueryParam(
 *     name = "fromDate",
 *     requirements = "\d{4}-\d{2}-\d{2}",
 *     description = "From date",
 *     strict = true,
 *     nullable = false
 * )
 * @Rest\QueryParam(
 *     name = "toDate",
 *     requirements = "\d{4}-\d{2}-\d{2}",
 *     description = "To date",
 *     strict = true,
 *     nullable = false
 * )
 *
 * @Security("has_role('ROLE_ADMIN') || has_role('ROLE_USER') && company.isMember(user)")
 */
class DashboardController extends BaseController
{
    /**
     * Security check to verify that the company has access to the given label and event
     * and check that the dates are in the correct order
     *
     * @param Company   $company
     * @param Label     $label
     * @param Event     $event
     * @param string    $coverageType
     * @param \DateTime $fromDate
     * @param \DateTime $toDate
     */
    private function validateRequest(
        Company $company,
        Label $label,
        Event $event,
        $coverageType,
        \DateTime $fromDate,
        \DateTime $toDate
    ) {
        // check that the user and the company are allowed to view data for the requested label
        $canViewLabel = false;
        foreach ($company->getLabels() as $companyLabel) {
            if ($label->getId() == $companyLabel->getId()) {
                $canViewLabel = true;
                break;
            }
        }
        if (!$canViewLabel || !$this->get('event_manager')->canCompanyViewEvent($company, $event)) {
            throw new AccessDeniedException();
        }

        // check that the user and the company are allowed to view data for the requested coverage type
        if (!in_array($coverageType, $company->getCoverageTypes())) {
            throw new AccessDeniedException();
        }

        // check that the dates are in the correct order
        if ($fromDate > $toDate) {
            throw new InvalidArgumentsException();
        }
    }

    /**
     * Content stream
     *
     * @ApiDoc(
     *   section = "Dashboard",
     *   description = "Content stream",
     *   statusCodes = {
     *     200 = "Content stream returned",
     *     400 = "Invalid arguments",
     *     401 = "Not authenticated",
     *     403 = "User is not authorised to view this company or company is not authorised to view this event",
     *     404 = "Company or Event not found"
     *   },
     *   filters={
     *      {"name"="fromDate", "dataType"="string"},
     *      {"name"="toDate", "dataType"="string"}
     *   }
     * )
     *
     * @Rest\Get("/content-stream", name="dashboard_content_stream")
     * @Rest\Get("/content-stream-full", name="dashboard_content_stream_full")
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
     *   description = "Number of posts to return"
     * )
     * @Rest\QueryParam(
     *   name = "page",
     *   requirements = "\d+",
     *   default = 1,
     *   description = "Page number"
     * )
     *
     * @Rest\View(serializerGroups={"Default"})
     *
     * @param Company               $company
     * @param Label                 $label
     * @param Event                 $event
     * @param string                $coverageType
     * @param \DateTime             $fromDate
     * @param \DateTime             $toDate
     * @param ParamFetcherInterface $paramFetcher
     *
     * @return View
     */
    public function contentStreamAction(
        Company $company,
        Label $label,
        Event $event,
        $coverageType,
        \DateTime $fromDate,
        \DateTime $toDate,
        ParamFetcherInterface $paramFetcher
    ) {
        $this->validateRequest($company, $label, $event, $coverageType, $fromDate, $toDate);

        $filters = array_merge(
            $paramFetcher->get('filter'),
            [
                'label'        => $label,
                'topics'       => $this->get('dashboard_manager')->getMatchingTopicIds($company, $event),
                'coverageType' => $coverageType,
                'fromDate'     => $fromDate,
                'toDate'       => $toDate,
                'postType'     => [Post::POST_TYPE_IMAGE, Post::POST_TYPE_VIDEO, Post::POST_TYPE_BOTH],
            ]
        );

        $pager        = $this->get('post_repository')->getResultsPager(
            $filters,
            $paramFetcher->get('sorting') ?: ['publishedAt' => 'desc'],
            $paramFetcher->get('count'),
            $paramFetcher->get('page')
        );
        $pagerFactory = new PagerfantaFactory();

        return $pagerFactory->createRepresentation(
            $pager,
            new Route(
                'dashboard_content_stream',
                array_merge(
                    [
                        'companyId'    => $company->getId(),
                        'labelId'      => $label->getId(),
                        'eventId'      => $event->getId(),
                        'coverageType' => $coverageType,
                        'fromDate'     => $fromDate,
                        'toDate'       => $toDate,
                    ],
                    $paramFetcher->all()
                )
            )
        );
    }

    /**
     * Content plot
     *
     * @ApiDoc(
     *   section = "Dashboard",
     *   description = "Content plot",
     *   statusCodes = {
     *     200 = "Content plot returned",
     *     400 = "Invalid arguments",
     *     401 = "Not authenticated",
     *     403 = "User is not authorised to view this company or company is not authorised to view this event",
     *     404 = "Company or Event not found"
     *   },
     *   filters={
     *      {"name"="fromDate", "dataType"="string"},
     *      {"name"="toDate", "dataType"="string"}
     *   }
     * )
     *
     * @Rest\Get("/content-plot", name="dashboard_content_plot")
     *
     * @param Company   $company
     * @param Label     $label
     * @param Event     $event
     * @param string    $coverageType
     * @param \DateTime $fromDate
     * @param \DateTime $toDate
     *
     * @return View
     */
    public function contentPlotAction(
        Company $company,
        Label $label,
        Event $event,
        $coverageType,
        \DateTime $fromDate,
        \DateTime $toDate
    ) {
        $this->validateRequest($company, $label, $event, $coverageType, $fromDate, $toDate);

        return $this->get('dashboard_manager')->getContentPlotData(
            $company,
            $label,
            $event,
            $coverageType,
            $fromDate,
            $toDate
        );
    }

    /**
     * Media value
     *
     * @ApiDoc(
     *   section = "Dashboard",
     *   description = "Media value",
     *   statusCodes = {
     *     200 = "Media value data returned",
     *     400 = "Invalid arguments",
     *     401 = "Not authenticated",
     *     403 = "User is not authorised to view this company or company is not authorised to view this event",
     *     404 = "Company or Event not found"
     *   },
     *   filters={
     *      {"name"="fromDate", "dataType"="string"},
     *      {"name"="toDate", "dataType"="string"}
     *   }
     * )
     *
     * @Rest\Get(
     *     "/media-value/{imagesOrVideos}",
     *     requirements={"imagesOrVideos": "images|videos"},
     *     name="dashboard_media_value"
     * )
     *
     * @param Company   $company
     * @param Label     $label
     * @param Event     $event
     * @param string    $coverageType
     * @param \DateTime $fromDate
     * @param \DateTime $toDate
     * @param string    $imagesOrVideos
     *
     * @return View
     */
    public function mediaValueAction(
        Company $company,
        Label $label,
        Event $event,
        $coverageType,
        \DateTime $fromDate,
        \DateTime $toDate,
        $imagesOrVideos
    ) {
        $this->validateRequest($company, $label, $event, $coverageType, $fromDate, $toDate);

        return $this->get('dashboard_manager')->getMediaValueData(
            $company,
            $label,
            $event,
            $coverageType,
            $fromDate,
            $toDate,
            $imagesOrVideos
        );
    }

    /**
     * Media exposure
     *
     * @ApiDoc(
     *   section = "Dashboard",
     *   description = "Media exposure",
     *   statusCodes = {
     *     200 = "Media exposure data returned",
     *     400 = "Invalid arguments",
     *     401 = "Not authenticated",
     *     403 = "User is not authorised to view this company or company is not authorised to view this event",
     *     404 = "Company or Event not found"
     *   },
     *   filters={
     *      {"name"="fromDate", "dataType"="string"},
     *      {"name"="toDate", "dataType"="string"}
     *   }
     * )
     *
     * @Rest\Get("/media-exposure", name="dashboard_media_exposure")
     *
     * @param Company   $company
     * @param Label     $label
     * @param Event     $event
     * @param string    $coverageType
     * @param \DateTime $fromDate
     * @param \DateTime $toDate
     *
     * @return View
     */
    public function mediaExposureAction(
        Company $company,
        Label $label,
        Event $event,
        $coverageType,
        \DateTime $fromDate,
        \DateTime $toDate
    ) {
        $this->validateRequest($company, $label, $event, $coverageType, $fromDate, $toDate);

        return $this->get('dashboard_manager')->getMediaExposureData(
            $company,
            $label,
            $event,
            $coverageType,
            $fromDate,
            $toDate
        );
    }

    /**
     * Trending themes
     *
     * @ApiDoc(
     *   section = "Dashboard",
     *   description = "Trending themes",
     *   statusCodes = {
     *     200 = "Trending themes data returned",
     *     400 = "Invalid arguments",
     *     401 = "Not authenticated",
     *     403 = "User is not authorised to view this company or company is not authorised to view this event",
     *     404 = "Company or Event not found"
     *   },
     *   filters={
     *      {"name"="fromDate", "dataType"="string"},
     *      {"name"="toDate", "dataType"="string"}
     *   }
     * )
     *
     * @Rest\Get("/trending-themes", name="dashboard_trending_themes")
     *
     * @param Company   $company
     * @param Label     $label
     * @param Event     $event
     * @param string    $coverageType
     * @param \DateTime $fromDate
     * @param \DateTime $toDate
     *
     * @return View
     */
    public function trendingThemesAction(
        Company $company,
        Label $label,
        Event $event,
        $coverageType,
        \DateTime $fromDate,
        \DateTime $toDate
    ) {
        $this->validateRequest($company, $label, $event, $coverageType, $fromDate, $toDate);

        return $this->get('dashboard_manager')->getTrendingThemesData(
            $company,
            $label,
            $event,
            $coverageType,
            $fromDate,
            $toDate
        );
    }

    /**
     * Sentiment
     *
     * @ApiDoc(
     *   section = "Dashboard",
     *   description = "Sentiment",
     *   statusCodes = {
     *     200 = "Sentiment data returned",
     *     400 = "Invalid arguments",
     *     401 = "Not authenticated",
     *     403 = "User is not authorised to view this company or company is not authorised to view this event",
     *     404 = "Company or Event not found"
     *   },
     *   filters={
     *      {"name"="fromDate", "dataType"="string"},
     *      {"name"="toDate", "dataType"="string"}
     *   }
     * )
     *
     * @Rest\Get("/sentiment", name="dashboard_sentiment")
     *
     * @param Company   $company
     * @param Label     $label
     * @param Event     $event
     * @param string    $coverageType
     * @param \DateTime $fromDate
     * @param \DateTime $toDate
     *
     * @return View
     */
    public function sentimentAction(
        Company $company,
        Label $label,
        Event $event,
        $coverageType,
        \DateTime $fromDate,
        \DateTime $toDate
    ) {
        $this->validateRequest($company, $label, $event, $coverageType, $fromDate, $toDate);

        return $this->get('dashboard_manager')->getSentimentData(
            $company,
            $label,
            $event,
            $coverageType,
            $fromDate,
            $toDate
        );
    }

    /**
     * Top sources
     *
     * @ApiDoc(
     *   section = "Dashboard",
     *   description = "Top sources",
     *   statusCodes = {
     *     200 = "Top sources data returned",
     *     400 = "Invalid arguments",
     *     401 = "Not authenticated",
     *     403 = "User is not authorised to view this company or company is not authorised to view this event",
     *     404 = "Company or Event not found"
     *   },
     *   filters={
     *      {"name"="fromDate", "dataType"="string"},
     *      {"name"="toDate", "dataType"="string"}
     *   }
     * )
     *
     * @Rest\Get("/top-sources", name="dashboard_top_sources")
     *
     * @param Company   $company
     * @param Label     $label
     * @param Event     $event
     * @param string    $coverageType
     * @param \DateTime $fromDate
     * @param \DateTime $toDate
     *
     * @return View
     */
    public function topSourcesAction(
        Company $company,
        Label $label,
        Event $event,
        $coverageType,
        \DateTime $fromDate,
        \DateTime $toDate
    ) {
        $this->validateRequest($company, $label, $event, $coverageType, $fromDate, $toDate);

        return $this->get('dashboard_manager')->getTopSourcesData(
            $company,
            $label,
            $event,
            $coverageType,
            $fromDate,
            $toDate
        );
    }

    /**
     * Impressions vs engagement
     *
     * @ApiDoc(
     *   section = "Dashboard",
     *   description = "Impressions vs engagement",
     *   statusCodes = {
     *     200 = "Impressions vs engagement data returned",
     *     400 = "Invalid arguments",
     *     401 = "Not authenticated",
     *     403 = "User is not authorised to view this company or company is not authorised to view this event",
     *     404 = "Company or Event not found"
     *   },
     *   filters={
     *      {"name"="fromDate", "dataType"="string"},
     *      {"name"="toDate", "dataType"="string"}
     *   }
     * )
     *
     * @Rest\Get(
     *     "/impressions-vs-engagement/{imagesOrVideos}",
     *     requirements={"imagesOrVideos": "images|videos"},
     *     name="dashboard_impressions_vs_engagement"
     * )
     *
     * @param Company   $company
     * @param Label     $label
     * @param Event     $event
     * @param string    $coverageType
     * @param \DateTime $fromDate
     * @param \DateTime $toDate
     * @param string    $imagesOrVideos
     *
     * @return View
     */
    public function impressionsVsEngagementAction(
        Company $company,
        Label $label,
        Event $event,
        $coverageType,
        \DateTime $fromDate,
        \DateTime $toDate,
        $imagesOrVideos
    ) {
        $this->validateRequest($company, $label, $event, $coverageType, $fromDate, $toDate);

        return $this->get('dashboard_manager')->getImpressionsVsEngagementData(
            $company,
            $label,
            $event,
            $coverageType,
            $fromDate,
            $toDate,
            $imagesOrVideos
        );
    }

    /**
     * Most viewed videos
     *
     * @ApiDoc(
     *   section = "Dashboard",
     *   description = "Most viewed videos",
     *   statusCodes = {
     *     200 = "Most viewed videos data returned",
     *     400 = "Invalid arguments",
     *     401 = "Not authenticated",
     *     403 = "User is not authorised to view this company or company is not authorised to view this event",
     *     404 = "Company or Event not found"
     *   },
     *   filters={
     *      {"name"="fromDate", "dataType"="string"},
     *      {"name"="toDate", "dataType"="string"}
     *   }
     * )
     *
     * @Rest\Get("/most-viewed-videos", name="dashboard_most_viewed_videos")
     *
     * @param Company   $company
     * @param Label     $label
     * @param Event     $event
     * @param string    $coverageType
     * @param \DateTime $fromDate
     * @param \DateTime $toDate
     *
     * @return View
     */
    public function mostViewedVideosAction(
        Company $company,
        Label $label,
        Event $event,
        $coverageType,
        \DateTime $fromDate,
        \DateTime $toDate
    ) {
        $this->validateRequest($company, $label, $event, $coverageType, $fromDate, $toDate);

        return $this->get('dashboard_manager')->getMostViewedVideosData(
            $company,
            $label,
            $event,
            $coverageType,
            $fromDate,
            $toDate
        );
    }

    /**
     * Most powerful media
     *
     * @ApiDoc(
     *   section = "Dashboard",
     *   description = "Most powerful media",
     *   statusCodes = {
     *     200 = "Most powerful media data returned",
     *     400 = "Invalid arguments",
     *     401 = "Not authenticated",
     *     403 = "User is not authorised to view this company or company is not authorised to view this event",
     *     404 = "Company or Event not found"
     *   },
     *   filters={
     *      {"name"="fromDate", "dataType"="string"},
     *      {"name"="toDate", "dataType"="string"}
     *   }
     * )
     *
     * @Rest\Get(
     *     "/most-powerful/{imagesOrVideos}",
     *     requirements={"imagesOrVideos": "images|videos"},
     *     name="dashboard_most_powerful_all_platforms"
     * )
     * @Rest\Get(
     *     "/most-powerful/{imagesOrVideos}/{platformId}",
     *     requirements={"imagesOrVideos": "images|videos", "platformId": "[a-zA-Z0-9]+"},
     *     name="dashboard_most_powerful"
     * )
     * @ParamConverter("platform", class="SnapRapidApiBundle:Platform", options={"id" = "platformId"})
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
     * @return View
     */
    public function mostPowerfulMediaAction(
        Company $company,
        Label $label,
        Event $event,
        $coverageType,
        \DateTime $fromDate,
        \DateTime $toDate,
        $imagesOrVideos,
        Platform $platform = null
    ) {
        $this->validateRequest($company, $label, $event, $coverageType, $fromDate, $toDate);

        return $this->get('dashboard_manager')->getMostPowerfulMediaData(
            $company,
            $label,
            $event,
            $coverageType,
            $fromDate,
            $toDate,
            $imagesOrVideos,
            $platform
        );
    }
}
