<?php

namespace Oro\Bundle\ConfigBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *  name="oro_config",
 *  uniqueConstraints={@ORM\UniqueConstraint(name="UQ_ENTITY", columns={"entity", "record_id"})}
 * )
 * @ORM\Entity
 */
class Config
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="entity", type="string", length=255)
     */
    protected $entity;

    /**
     * @var int
     *
     * @ORM\Column(name="record_id", type="integer")
     */
    protected $recordId;

    /**
     * @var array
     *
     * @ORM\Column(name="settings", type="json_array")
     */
    protected $values;

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
     * Get entity
     *
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set entity
     *
     * @param  string $entity
     * @return Config
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Get record id
     *
     * @return integer
     */
    public function getRecordId()
    {
        return $this->recordId;
    }

    /**
     * Set record id
     *
     * @param  integer $recordId
     * @return Config
     */
    public function setRecordId($recordId)
    {
        $this->recordId = $recordId;

        return $this;
    }

    /**
     * Returns array of entity settings
     *
     * @return array Entity related settings
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * Pass an associative array of settings => values and re-set settings with new ones.
     *
     * @param array $values Array of setting => value pairs
     * @return Config
     */
    public function setValues(array $values)
    {
        $this->values = $values;

        return $this;
    }
}
