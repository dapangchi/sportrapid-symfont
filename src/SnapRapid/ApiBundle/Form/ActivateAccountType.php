<?php

namespace SnapRapid\ApiBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivateAccountType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'password',
                'repeated',
                [
                    'property_path'   => 'plainPassword',
                    'invalid_message' => 'Your passwords do not match!',
                ]
            )
            ->add(
                'token',
                'text',
                [
                    'property_path'  => 'accountActivationToken',
                    'error_bubbling' => true,
                ]
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'        => 'SnapRapid\Core\Model\User',
                'validation_groups' => ['ActivateAccount'],
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return '';
    }
}
