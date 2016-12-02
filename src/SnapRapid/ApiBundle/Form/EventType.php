<?php

namespace SnapRapid\ApiBundle\Form;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $event = $builder->getForm()->getData();

        $builder
            ->add('name')
            ->add('dateRangeType')
            ->add(
                'dateRangeStart',
                'date',
                [
                    'widget' => 'single_text',
                ]
            )
            ->add(
                'dateRangeEnd',
                'date',
                [
                    'widget' => 'single_text',
                ]
            )
            ->add(
                'parent',
                'document',
                [
                    'class'         => 'SnapRapidApiBundle:Event',
                    'query_builder' => function (DocumentRepository $dr) use ($event) {
                        if ($event && $event->getId()) {
                            return $dr->createQueryBuilder('e')
                                ->field('id')->notEqual($event->getId());
                        } else {
                            return $dr->createQueryBuilder('e');
                        }
                    },
                ]
            )
            ->add(
                'topics',
                'document',
                [
                    'class'    => 'SnapRapidApiBundle:Topic',
                    'multiple' => true,
                ]
            );

        // set missing relation fields to null manually as otherwise they get ignored
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $formEvent) {
                $eventObject = $formEvent->getData();
                $form        = $formEvent->getForm();
                if (!$form->get('parent')->isSubmitted()) {
                    $eventObject->setParent(null);
                }
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
                'data_class'         => 'SnapRapid\Core\Model\Event',
                'allow_extra_fields' => true,
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'event_form';
    }
}
