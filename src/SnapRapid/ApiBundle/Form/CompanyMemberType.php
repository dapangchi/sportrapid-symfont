<?php

namespace SnapRapid\ApiBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompanyMemberType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['is_new']) {
            $builder
                ->add('email');
        } else {
            $builder
                ->add('enabled')
                ->add('isAdmin');
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'         => 'SnapRapid\Core\Model\CompanyMember',
                'allow_extra_fields' => true,
                'is_new'             => false,
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'company_member_form';
    }
}
