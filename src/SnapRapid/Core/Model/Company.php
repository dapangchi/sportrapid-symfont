<?php

namespace SnapRapid\Core\Model;

use Gedmo\Blameable\Traits\Blameable;
use Gedmo\Timestampable\Traits\Timestampable;
use SnapRapid\ApiBundle\Security\User\SecurityUser;
use SnapRapid\Core\Model\Base\PersistentModel;
use SnapRapid\Core\Model\Collection\ArrayCollection;
use SnapRapid\Core\Model\Collection\Collection;

class Company extends PersistentModel
{
    use Timestampable,
        Blameable;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $contactName;

    /**
     * @var string
     */
    protected $contactEmail;

    /**
     * @var string
     */
    protected $contactPhone;

    /**
     * @var array
     */
    protected $coverageTypes = [];

    /**
     * @var int
     */
    protected $maxMembers = 5;

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @var Collection|CompanyMember[]
     */
    protected $members;

    /**
     * @var Collection|Label[]
     */
    protected $labels;

    /**
     * @var Collection|Topic[]
     */
    protected $topics;

    /**
     * @var Collection|Event[]
     */
    protected $events;

    /**
     * @var array
     */
    protected $eventStacks;

    public function __construct()
    {
        $this->members = new ArrayCollection();
        $this->labels  = new ArrayCollection();
        $this->topics  = new ArrayCollection();
        $this->events  = new ArrayCollection();
    }

    /**
     * Check if given SecurityUser is a member of this Company
     *
     * @param SecurityUser $securityUser
     *
     * @return bool
     */
    public function isMember(SecurityUser $securityUser)
    {
        foreach ($this->getUserMembers() as $member) {
            if ($member->isEnabled() && $member->getUser()->getId() == $securityUser->getUser()->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if given SecurityUser is an admin member of this Company
     *
     * @param SecurityUser $securityUser
     *
     * @return bool
     */
    public function isAdminMember(SecurityUser $securityUser)
    {
        foreach ($this->getUserMembers() as $member) {
            if ($member->isEnabled() && $member->getUser()->getId() == $securityUser->getUser()->getId()) {
                return $member->getIsAdmin();
            }
        }

        return false;
    }

    /**
     * @return Collection|CompanyMember[]
     */
    public function getMembers()
    {
        return $this->members;
    }

    /**
     * @param CompanyMember $member
     *
     * @return Company
     */
    public function addMember($member)
    {
        $this->members->add($member);

        return $this;
    }

    /**
     * @param CompanyMember $member
     *
     * @return Company
     */
    public function removeMember($member)
    {
        $this->members->removeElement($member);

        return $this;
    }

    /**
     * Get members who are users
     *
     * @return CompanyMember[]
     */
    public function getUserMembers()
    {
        $userMembers = new ArrayCollection();
        foreach ($this->getMembers() as $member) {
            if ($member->getUser()) {
                $userMembers->add($member);
            }
        }

        return $userMembers;
    }

    /**
     * Get members who are company admin users
     *
     * @return CompanyMember[]
     */
    public function getCompanyAdminUserMembers()
    {
        $companyAdminMembers = new ArrayCollection();
        foreach ($this->getMembers() as $member) {
            if ($member->getUser() && $member->getIsAdmin()) {
                $companyAdminMembers->add($member);
            }
        }

        return $companyAdminMembers;
    }

    /**
     * @return Collection|Label[]
     */
    public function getLabels()
    {
        return $this->labels;
    }

    /**
     * @param Label $label
     *
     * @return Company
     */
    public function addLabel($label)
    {
        $this->labels->add($label);

        return $this;
    }

    /**
     * @param Label $label
     *
     * @return Company
     */
    public function removeLabel($label)
    {
        $this->labels->removeElement($label);

        return $this;
    }

    /**
     * @return Collection|Topic[]
     */
    public function getTopics()
    {
        return $this->topics;
    }

    /**
     * @param Topic $topic
     *
     * @return Company
     */
    public function addTopic($topic)
    {
        $this->topics->add($topic);

        return $this;
    }

    /**
     * @param Topic $topic
     *
     * @return Company
     */
    public function removeTopic($topic)
    {
        $this->topics->removeElement($topic);

        return $this;
    }

    /**
     * @return Collection|Event[]
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * @param Event $event
     *
     * @return Company
     */
    public function addEvent($event)
    {
        $this->events->add($event);

        return $this;
    }

    /**
     * @param Event $event
     *
     * @return Company
     */
    public function removeEvent($event)
    {
        $this->events->removeElement($event);

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Company
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getContactName()
    {
        return $this->contactName;
    }

    /**
     * @param string $contactName
     *
     * @return Company
     */
    public function setContactName($contactName)
    {
        $this->contactName = $contactName;

        return $this;
    }

    /**
     * @return string
     */
    public function getContactEmail()
    {
        return $this->contactEmail;
    }

    /**
     * @param string $contactEmail
     *
     * @return Company
     */
    public function setContactEmail($contactEmail)
    {
        $this->contactEmail = $contactEmail;

        return $this;
    }

    /**
     * @return string
     */
    public function getContactPhone()
    {
        return $this->contactPhone;
    }

    /**
     * @param string $contactPhone
     *
     * @return Company
     */
    public function setContactPhone($contactPhone)
    {
        $this->contactPhone = $contactPhone;

        return $this;
    }

    /**
     * @return array
     */
    public function getCoverageTypes()
    {
        return $this->coverageTypes;
    }

    /**
     * @param array $coverageTypes
     *
     * @return Company
     */
    public function setCoverageTypes($coverageTypes)
    {
        $this->coverageTypes = $coverageTypes;

        return $this;
    }

    /**
     * @return int
     */
    public function getMaxMembers()
    {
        return $this->maxMembers;
    }

    /**
     * @param int $maxMembers
     *
     * @return Company
     */
    public function setMaxMembers($maxMembers)
    {
        $this->maxMembers = $maxMembers;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     *
     * @return Company
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return array
     */
    public function getEventStacks()
    {
        return $this->eventStacks;
    }

    /**
     * @param array $eventStacks
     */
    public function setEventStacks(array $eventStacks)
    {
        $this->eventStacks = $eventStacks;
    }
}
