<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 * @copyright ©2009-2015
 */
namespace LODM\Accessors;

use LODM\Accessors\Prototypes\AbstractTimestamp;
use Spiral\Models\EntityInterface;
use Spiral\ODM\AtomicAccessorInterface;
use Spiral\ODM\CompositableInterface;
use Spiral\ODM\Document;
use Spiral\ORM\Exceptions\AccessorException as ODMAccessException;

/**
 * Mocks MongoDate fields using Carbon class as base.
 */
class MongoTimestamp extends AbstractTimestamp implements AtomicAccessorInterface
{
    /**
     * @invisible
     * @var CompositableInterface
     */
    protected $parent = null;

    /**
     * Original value.
     *
     * @var mixed
     */
    protected $original = null;

    /**
     * {@inheritdoc}
     */
    public function __construct($data = null, EntityInterface $parent = null)
    {
        $this->parent = $parent;
        $this->original = $data;

        if ($data instanceof \MongoDate) {
            parent::__construct(null, 'UTC');
            $this->setTimestamp($data->sec);

            return;
        }

        //We are locking timezone to UTC for mongo
        parent::__construct($data, 'UTC');

        if ($data === null || $this->getTimestamp() === false) {
            //Correcting default values
            $this->setTimestamp(0);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function embed(EntityInterface $parent)
    {
        if (!$parent instanceof CompositableInterface) {
            throw new ODMAccessException("Scalar arrays can be embedded only to ODM objects.");
        }

        $accessor = clone $this;
        $accessor->original = -1;
        $accessor->parent = $parent;

        return $accessor;
    }

    /**
     * {@inheritdoc}
     */
    public function serializeData()
    {
        //MongoDate in a fact just a simple timestamp
        return $this->timestamp ? new \MongoDate($this->timestamp) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($data)
    {
        $this->setTimestamp($this->castTimestamp($data));
    }

    /**
     * {@inheritdoc}
     */
    public function hasUpdates()
    {
        return $this->original != new \MongoDate($this->timestamp);
    }

    /**
     * {@inheritdoc}
     */
    public function flushUpdates()
    {
        $this->original = $this->serializeData();
    }

    /**
     * {@inheritdoc}
     */
    public function buildAtomics($container = '')
    {
        if (!$this->hasUpdates()) {
            return [];
        }

        return [Document::ATOMIC_SET => [$container => $this->serializeData()]];
    }

    /**
     * {@inheritdoc}
     */
    public function defaultValue()
    {
        return null;
    }
}