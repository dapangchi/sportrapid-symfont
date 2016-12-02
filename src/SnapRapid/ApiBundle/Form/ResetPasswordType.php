<?php

namespace SnapRapid\ApiBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResetPasswordType extends AbstractType
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
                    'property_path'  => 'passwordResetToken',
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
                'validation_groups' => ['ResetPassword'],
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
