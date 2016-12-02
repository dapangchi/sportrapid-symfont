<?php

namespace SnapRapid\ApiBundle\Form;

use SnapRapid\Core\Model\Platform;
use SnapRapid\Core\Model\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompanyType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('contactName')
            ->add('contactEmail')
            ->add('contactPhone')
            ->add(
                'members',
                'collection',
                [
                    'type'               => new CompanyMemberType(),
                    'allow_add'          => true,
                    'allow_extra_fields' => true,
                ]
            );

        if (in_array(User::ROLE_ADMIN, $options['roles'])) {
            $builder
                ->add(
                    'coverageTypes',
                    'choice',
                    [
                        'choices'           => [
                            'Social'  => Platform::COVERAGE_TYPE_SOCIAL,
                            'Digital' => Platform::COVERAGE_TYPE_DIGITAL,
                        ],
                        'choices_as_values' => true,
                        'multiple'          => true,
                    ]
                )
                ->add('enabled')
                ->add('maxMembers')
                ->add(
                    'labels',
                    'document',
                    [
                        'class'    => 'SnapRapidApiBundle:Label',
                        'multiple' => true,
                    ]
                )
                ->add(
                    'topics',
                    'document',
                    [
                        'class'    => 'SnapRapidApiBundle:Topic',
                        'multiple' => true,
                    ]
                )
                ->add(
                    'events',
                    'document',
                    [
                        'class'    => 'SnapRapidApiBundle:Event',
                        'multiple' => true,
                    ]
                );
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'         => 'SnapRapid\Core\Model\Company',
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
        return 'company_form';
    }
}
