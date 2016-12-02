<?php

namespace SnapRapid\ApiBundle\Form\DataTransformer;

use Doctrine\Common\Collections\Collection;
use SnapRapid\Core\Model\Collection\ArrayCollection;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class IdentityToEntityTransformer implements DataTransformerInterface
{
    private $repository;
    private $repositoryMethod;
    private $identifierMethod;

    /**
     * @param object $repository
     * @param string $repositoryMethod
     * @param string $identifierMethod
     */
    public function __construct($repository, $repositoryMethod, $identifierMethod)
    {
        $this->repository       = $repository;
        $this->repositoryMethod = $repositoryMethod;
        $this->identifierMethod = $identifierMethod;
    }

    /**
     * @param object|array $entities
     *
     * @return string
     */
    public function transform($entities)
    {
        if (!$entities) {
            return;
        }

        $single   = !is_array($entities) && !is_a($entities, Collection::class);
        $entities = $single ? [$entities] : $entities;

        $identifiers = [];
        foreach ($entities as $entity) {
            $identifiers[] = (string) call_user_func([$entity, $this->identifierMethod]);
        }

        return $single ? $identifiers[0] : $identifiers;
    }

    /**
     * @param mixed $ids
     *
     * @return null|object|void
     */
    public function reverseTransform($ids)
    {
        $single = !is_array($ids);

        $ids = array_filter((array) $ids);
        if (!count($ids)) {
            return;
        }

        $entities = call_user_func([$this->repository, $this->repositoryMethod], $ids);

        if (count($entities) != count($ids)) {
            $missing = implode(', ', array_diff($ids, $entities));
            throw new TransformationFailedException(
                sprintf('The entities identified by "%s" are non-existent', $missing)
            );
        }

        return $single ? $entities[0] : new ArrayCollection($entities);
    }
}
