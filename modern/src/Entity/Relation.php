<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\UniqueConstraint;
use JMS\Serializer\Annotation\Groups;

/**
 * Class Subject
 */
#[ORM\Entity]
#[ORM\Table(name: 'relations')]
#[UniqueConstraint(name: 'unique_relation', columns: ['source_id', 'target_id', 'class', 'context', 'value'])]
class Relation
{
    /**
     * @var integer
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    /**
     * @var Person[]|Collection
     */
    #[ORM\ManyToOne(targetEntity: \App\Entity\Person::class, inversedBy: 'relationsOutgoing')]
    #[Groups(['Relation'])]
    private $source;

    /**
     * @var Person[]|Collection
     */
    #[ORM\ManyToOne(targetEntity: \App\Entity\Person::class, inversedBy: 'relationsIncoming')]
    #[Groups(['Relation'])]
    private $target;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $class;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $context;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $value;

    /**
     * @var Aspect
     */
    #[ORM\ManyToOne(targetEntity: \App\Entity\Aspect::class, inversedBy: 'relations')]
    private $aspect;

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
     * Set class
     *
     * @param string $class
     *
     * @return Relation
     */
    public function setClass($class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Get class
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Set context
     *
     * @param string $context
     *
     * @return Relation
     */
    public function setContext($context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Get context
     *
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Set value
     *
     * @param string $value
     *
     * @return Relation
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    public function getPrettyValue()
    {
        $value = $this->value;
        if ($this->class == 'friend') {
            $value = 'friendOf';
        }

        $arr = preg_split('/(?=[A-Z])/', $value);

        return strtolower(implode(' ', $arr));
    }

    /**
     * Set source
     *
     * @param \App\Entity\Person $source
     *
     * @return Relation
     */
    public function setSource(?\App\Entity\Person $source = null)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return \App\Entity\Person
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set target
     *
     * @param \App\Entity\Person $target
     *
     * @return Relation
     */
    public function setTarget(?\App\Entity\Person $target = null)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * Get target
     *
     * @return \App\Entity\Person
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Set aspect
     *
     * @param \App\Entity\Aspect $aspect
     *
     * @return Relation
     */
    public function setAspect(?\App\Entity\Aspect $aspect = null)
    {
        $this->aspect = $aspect;

        return $this;
    }

    /**
     * Get aspect
     *
     * @return \App\Entity\Aspect
     */
    public function getAspect()
    {
        return $this->aspect;
    }
}
