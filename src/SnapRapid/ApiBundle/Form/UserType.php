<?php

namespace SnapRapid\ApiBundle\Form;

use SnapRapid\Core\Manager\UserManagerInterface;
use SnapRapid\Core\Model\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

class UserType extends AbstractType
{
    /**
     * @var UserManagerInterface
     */
    private $userManager;

    /**
     * @param UserManagerInterface $userManager
     */
    public function __construct(UserManagerInterface $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName')
            ->add('lastName')
            ->add('email')
            ->add(
                'password',
                'repeated',
                [
                    'property_path'   => 'plainPassword',
                    'invalid_message' => 'Your passwords do not match!',
                ]
            )
            ->add(
                'currentPassword',
                'password',
                [
                    'mapped'      => false,
                    'constraints' => new UserPassword(
                        [
                            'message' => 'Please enter your current password',
                            'groups'  => ['ChangePassword'],
                        ]
                    ),
                ]
            );

        if (in_array(User::ROLE_ADMIN, $options['roles'])) {
            $builder
                ->add('enabled')
                ->add(
                    'role',
                    'choice',
                    [
                        'choices' => [
                            'User'                       => User::ROLE_USER,
                            'Content Curator - Keywords' => User::ROLE_CONTENT_CURATOR_KEYWORDS,
                            'Content Curator - Logos'    => User::ROLE_CONTENT_CURATOR_LOGOS,
                            'Content Manager'            => User::ROLE_CONTENT_MANAGER,
                            'Admin'                      => User::ROLE_ADMIN,
                        ],
                        'choices_as_values' => true,
                        'mapped'            => false,
                    ]
                )
                ->add(
                    'apiAccessLabels',
                    'document',
                    [
                        'class'    => 'SnapRapidApiBundle:Label',
                        'multiple' => true,
                    ]
                )
                ->add(
                    'apiAccessDateRangeStart',
                    'date',
                    [
                        'widget' => 'single_text',
                    ]
                )
                ->add(
                    'apiAccessDateRangeEnd',
                    'date',
                    [
                        'widget' => 'single_text',
                    ]
                );
        }

        if (!count($options['roles'])) {
            $builder
                ->add(
                    'invitationToken',
                    'text',
                    [
                        'error_bubbling' => true,
                    ]
                );
        }

        // set canonical fields before validation
        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event) {
                $this->userManager->updateCanonicalFields($event->getData());
            }
        );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'         => 'SnapRapid\Core\Model\User',
                'validation_groups'  => ['Default', 'Register'],
                'allow_extra_fields' => true,
                'roles'              => [User::ROLE_USER],
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'user_form';
    }
}
