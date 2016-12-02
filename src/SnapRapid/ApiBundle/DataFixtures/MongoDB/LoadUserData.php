<?php


use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use SnapRapid\Core\Model\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadUserData implements FixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $userManager    = $this->container->get('user_manager');
        $userRepository = $this->container->get('user_repository');

        $user = $userManager->createNewUser();
        $user
            ->setEmail('tomilett@instantiate.co.uk')
            ->setFirstName('Tom')
            ->setLastName('Ilett')
            ->setPlainPassword('xxxxxx')
            ->setRoles([User::ROLE_ADMIN]);
        $userManager->updateCanonicalFields($user);
        $userRepository->save($user);

        $user = $userManager->createNewUser();
        $user
            ->setEmail('laurent@snaprapid.com')
            ->setFirstName('Laurent')
            ->setLastName('Decamp')
            ->setPlainPassword('xxxxxx')
            ->setRoles([User::ROLE_ADMIN]);
        $userManager->updateCanonicalFields($user);
        $userRepository->save($user);

        $user = $userManager->createNewUser();
        $user
            ->setEmail('russell@snaprapid.com')
            ->setFirstName('Russell')
            ->setLastName('Glenister')
            ->setPlainPassword('xxxxxx')
            ->setRoles([User::ROLE_ADMIN]);
        $userManager->updateCanonicalFields($user);
        $userRepository->save($user);

        $user = $userManager->createNewUser();
        $user
            ->setEmail('lisneifild@gmail.com')
            ->setFirstName('Lisa')
            ->setLastName('Neifild')
            ->setPlainPassword('xxxxxx')
            ->setRoles([User::ROLE_ADMIN]);
        $userManager->updateCanonicalFields($user);
        $userRepository->save($user);
    }
}
