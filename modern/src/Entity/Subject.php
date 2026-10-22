<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation\Groups;

/**
 * Class Subject
 */
#[ORM\Entity]
#[ORM\Table]
#[ORM\Index(name: 'slug', columns: ['slug'])]
class Subject
{
    /**
     * @var integer
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 255)]
    private $title;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 255)]
    private $slug;

    /**
     * @var bool
     */
    #[ORM\Column(type: 'boolean', name: 'is_mathnat')]
    private $isMathOrNatScience;

    /**
     * @var Person[]|Collection
     */
    #[ORM\ManyToMany(targetEntity: \App\Entity\Person::class, mappedBy: 'subjects')]
    #[Groups(['Subject'])]
    private $associatedPersons;

    /**
     * @var Aspect[]|Collection
     */
    #[ORM\ManyToMany(targetEntity: \App\Entity\Aspect::class, mappedBy: 'subjects')]
    #[Groups(['Subject'])]
    private $associatedAspects;

    /**
     * @var SubjectGroup[]|Collection
     */
    #[ORM\ManyToMany(targetEntity: \App\Entity\SubjectGroup::class, mappedBy: 'subjects')]
    #[Groups(['Subject'])]
    private $subjectGroups;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->associatedPersons = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Subject
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Add associatedPerson
     *
     * @param \App\Entity\Person $associatedPerson
     *
     * @return Subject
     */
    public function addAssociatedPerson(\App\Entity\Person $associatedPerson)
    {
        $this->associatedPersons[] = $associatedPerson;

        return $this;
    }

    /**
     * Remove associatedPerson
     *
     * @param \App\Entity\Person $associatedPerson
     */
    public function removeAssociatedPerson(\App\Entity\Person $associatedPerson)
    {
        $this->associatedPersons->removeElement($associatedPerson);
    }

    /**
     * Get associatedPersons
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAssociatedPersons()
    {
        return $this->associatedPersons;
    }

    /**
     * Set slug
     *
     * @param string $slug
     *
     * @return Subject
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Add associatedAspect
     *
     * @param \App\Entity\Aspect $associatedAspect
     *
     * @return Subject
     */
    public function addAssociatedAspect(\App\Entity\Aspect $associatedAspect)
    {
        $this->associatedAspects[] = $associatedAspect;

        return $this;
    }

    /**
     * Remove associatedAspect
     *
     * @param \App\Entity\Aspect $associatedAspect
     */
    public function removeAssociatedAspect(\App\Entity\Aspect $associatedAspect)
    {
        $this->associatedAspects->removeElement($associatedAspect);
    }

    /**
     * Get associatedAspects
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAssociatedAspects()
    {
        return $this->associatedAspects;
    }

    /**
     * Add subjectGroup
     *
     * @param \App\Entity\SubjectGroup $subjectGroup
     *
     * @return Subject
     */
    public function addSubjectGroup(\App\Entity\SubjectGroup $subjectGroup)
    {
        $this->subjectGroups[] = $subjectGroup;

        return $this;
    }

    /**
     * Remove subjectGroup
     *
     * @param \App\Entity\SubjectGroup $subjectGroup
     */
    public function removeSubjectGroup(\App\Entity\SubjectGroup $subjectGroup)
    {
        $this->subjectGroups->removeElement($subjectGroup);
    }

    /**
     * Get subjectGroups
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSubjectGroups()
    {
        return $this->subjectGroups;
    }

    /**
     * @return boolean
     */
    public function isMathOrNatScience()
    {
        return $this->isMathOrNatScience;
    }

    /**
     * @param boolean $isMathOrNatScience
     */
    public function setIsMathOrNatScience($isMathOrNatScience)
    {
        $this->isMathOrNatScience = $isMathOrNatScience;
    }
}
