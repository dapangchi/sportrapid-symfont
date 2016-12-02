<?php

namespace SnapRapid\ApiBundle\Form;

use SnapRapid\ApiBundle\Form\DataTransformer\IdentityToEntityTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SimpleEntityType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer(
            new IdentityToEntityTransformer(
                $options['repository'],
                $options['repositoryMethod'],
                $options['identifierMethod']
            )
        );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'compound'         => false,
                'repository'       => null,
                'identifierMethod' => 'getId',
                'repositoryMethod' => 'getMultiple',
            ]
        );

        $resolver->setRequired(['repository']);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'simple_entity';
    }
}
