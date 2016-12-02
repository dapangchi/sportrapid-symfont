<?php

namespace SnapRapid\ApiBundle\Repository;

use Doctrine\ODM\MongoDB\Query\Builder;
use SnapRapid\ApiBundle\Repository\Base\PersistableDocumentRepository;
use SnapRapid\Core\Model\Company;
use SnapRapid\Core\Model\CompanyMember;
use SnapRapid\Core\Model\User;
use SnapRapid\Core\Repository\CompanyRepositoryInterface;

class CompanyRepository extends PersistableDocumentRepository implements CompanyRepositoryInterface
{
    use Traits\PageableEntity;

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
     * Get all active companies
     *
     * @return Company[]
     */
    public function getAll()
    {
        $qb        = $this->documentRepository->createQueryBuilder($this->getEntityName());
        $companies = $qb
            ->sort('name', 'desc')
            ->getQuery()
            ->execute();

        return $companies;
    }

    /**
     * Get a list of company ids of which the given user is a member of
     *
     * @param User $user
     *
     * @return array
     */
    public function getMemberOfCompanyIds(User $user)
    {
        $rawCompanyIds = $this->documentManager->createQuery(
            'SELECT IDENTITY(m.company) AS companyId
            FROM SnapRapidApiBundle:CompanyMember m
            WHERE m.user = :user AND m.acceptedAt IS NOT NULL'
        )
            ->setParameter(':user', $user)
            ->getArrayResult();

        $companyIds = array_column($rawCompanyIds, 'companyId');

        return $companyIds;
    }

    /**
     * Get an array of company ids for a given name
     *
     * @param $searchString
     *
     * @return array
     */
    public function getCompanyIdsByName($searchString)
    {
        $ids = $this->documentRepository
            ->createQueryBuilder('a')
            ->select('a.id')
            ->where('LOWER(a.name) LIKE :search')
            ->setParameter('search', '%'.strtolower($searchString).'%')
            ->getQuery()
            ->getArrayResult();

        return array_column($ids, 'id');
    }

    /**
     * Get an company member object
     *
     * @param int $companyMemberId
     *
     * @return CompanyMember
     */
    public function getCompanyMemberById($companyMemberId)
    {
        return $this->documentManager
            ->getRepository('SnapRapidApiBundle:CompanyMember')
            ->find($companyMemberId);
    }

    /**
     * Get an company member objects with given email
     *
     * @param string $email
     *
     * @return CompanyMember
     */
    public function getCompanyMembersByEmail($email)
    {
        return $this->documentManager
            ->getRepository('SnapRapidApiBundle:CompanyMember')
            ->findBy(['email' => strtolower($email), 'user' => null]);
    }

    /**
     * Get a company member by invitation token
     *
     * @param $invitationToken
     *
     * @return CompanyMember
     */
    public function findMemberByInvitationToken($invitationToken)
    {
        return $this->documentManager
            ->getRepository('SnapRapidApiBundle:CompanyMember')
            ->findOneBy(['invitationToken' => $invitationToken]);
    }

    /**
     * @return string
     */
    protected function getEntityName()
    {
        return 'SnapRapidApiBundle:Company';
    }
}
