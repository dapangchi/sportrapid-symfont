<?php

namespace SnapRapid\ApiBundle\Repository;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ODM\MongoDB\Query\Builder;
use SnapRapid\ApiBundle\Repository\Base\PersistableDocumentRepository;
use SnapRapid\Core\Model\Base\PersistentModel;
use SnapRapid\Core\Model\User;
use SnapRapid\Core\Repository\UserRepositoryInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class UserRepository extends PersistableDocumentRepository implements UserRepositoryInterface
{
    use Traits\PageableEntity;

    /**
     * @var EncoderFactoryInterface
     */
    private $encoderFactory;

    /**
     * @var EventSubscriber
     */
    private $blameableSubscriber;

    /**
     * @param ObjectManager           $documentManager
     * @param EncoderFactoryInterface $encoderFactory
     * @param EventSubscriber         $blameableSubscriber
     */
    public function __construct(
        ObjectManager $documentManager,
        EncoderFactoryInterface $encoderFactory,
        EventSubscriber $blameableSubscriber
    ) {
        $this->encoderFactory      = $encoderFactory;
        $this->blameableSubscriber = $blameableSubscriber;

        parent::__construct($documentManager);
    }

    /**
     * @param PersistentModel $user
     *
     * @return bool|void
     */
    public function save(PersistentModel $user)
    {
        $this->updatePassword($user);

        parent::save($user);
    }

    /**
     * Update the password if required
     *
     * @param User $user
     */
    public function updatePassword(User $user)
    {
        if (0 !== strlen($password = $user->getPlainPassword())) {
            $encoder = $this->encoderFactory->getEncoder($user);
            $user->setPassword($encoder->encodePassword($password, $user->getSalt()));
            $user->eraseCredentials();
        }
    }

    /**
     * Get results query builder
     *
     * @param array $filters
     * @param array $sorting
     *
     * @return Builder
     */
    public function getResultsQueryBuilder(array $filters = [], array $sorting = [])
    {
        /** @var Builder $qb */
        $qb = $this->documentRepository->createQueryBuilder($this->getEntityName());

        // add filters
        foreach ($filters as $field => $value) {
            $qb->addAnd(
                $qb->expr()
                    ->field($field)
                    ->equals(new \MongoRegex('/.*'.$value.'.*/i'))
            );
        }

        // set ordering
        foreach ($sorting as $field => $order) {
            $qb->sort($field, $order);
        }

        return $qb;
    }

    /**
     * Get all active users
     *
     * @return User[]
     */
    public function getAllEnabled()
    {
        $qb    = $this->documentRepository->createQueryBuilder('u');
        $users = $qb
            ->where(
                $qb->expr()->eq('u.enabled', ':enabled')
            )
            ->setParameter(':enabled', true)
            ->getQuery()
            ->execute();

        return $users;
    }

    /**
     * Remove the blameable event subscriber
     */
    public function detachBlameableSubscriber()
    {
        $this->documentManager->getEventManager()->removeEventSubscriber($this->blameableSubscriber);
    }

    protected function getEntityName()
    {
        return 'SnapRapidApiBundle:User';
    }
}
