<?php

namespace SnapRapid\ApiBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use JMS\Serializer\SerializationContext;
use SnapRapid\ApiBundle\Security\User\SecurityUser;
use SnapRapid\Core\Model\User;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;

class BaseController extends FOSRestController
{
    /**
     * Helper function to return the Core User from the SecurityUser
     *
     * @return User
     */
    public function getUser()
    {
        $user = parent::getUser();

        if (get_class($user) == 'SnapRapid\ApiBundle\Security\User\SecurityUser') {
            return $user->getUser();
        } else {
            return $user;
        }
    }

    /**
     * Get the security user
     *
     * @return SecurityUser
     */
    public function getSecurityUser()
    {
        return parent::getUser();
    }

    /**
     * Set the serializer groups for a view
     *
     * @param View  $view
     * @param array $groups
     */
    protected function setSerializerGroups(View $view, array $groups)
    {
        $context = SerializationContext::create();
        $context->setGroups($groups);
        $view->setSerializationContext($context);
    }

    /**
     * Get the serializer groups from the annotation configuration
     *
     * @return array
     */
    protected function getSerializerGroupsFromAnnotations()
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();

        /** @var \FOS\RestBundle\Controller\Annotations\View $configuration */
        $configuration = $request->attributes->get('_view');
        $groups        = $configuration->getSerializerGroups();

        return $groups;
    }

    /**
     * Create a nameless form suitable for API posts/puts/patches
     *
     * @param FormTypeInterface|string $type
     * @param mixed                    $data
     * @param array                    $options
     *
     * @return FormInterface
     */
    protected function createApiForm($type, $data = null, array $options = [])
    {
        return $this->container->get('form.factory')->createNamed('', $type, $data, $options);
    }
}
