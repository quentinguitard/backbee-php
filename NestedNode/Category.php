<?php
namespace BackBee\NestedNode;

use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as Serializer;

use BackBee\ClassContent\AbstractClassContent;
use BackBee\Renderer\RenderableInterface;

use Doctrine\ORM\Mapping as ORM;

/**
 * A Category entry of a tree in BackBee.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      quentin.guitard <quentin.guitard@lp-digital.fr>
 * @ORM\Entity(repositoryClass="BackBee\NestedNode\Repository\CategoryRepository")
 * @ORM\Table(name="category",indexes={
 *     @ORM\Index(name="IDX_ROOT", columns={"root_uid"}),
 *     @ORM\Index(name="IDX_PARENT", columns={"parent_uid"}),
 *     @ORM\Index(name="IDX_SELECT_CATEGORY", columns={"root_uid", "leftnode", "rightnode"}),
 *     @ORM\Index(name="IDX_CATEGORY", columns={"category"})
 * })
 *
 * @Serializer\ExclusionPolicy("all")
 */
class Category extends AbstractNestedNode implements RenderableInterface, \JsonSerializable
{
    /**
     * Unique identifier of the content.
     *
     * @var string
     * @ORM\Id
     * @ORM\Column(type="string", length=32, name="uid")
     *
     * @Serializer\Expose
     * @Serializer\SerializedName("id")
     * @Serializer\Type("string")
     */
    protected $_uid;

    /**
     * The root node, cannot be NULL.
     *
     * @var \BackBee\NestedNode\Category
     * @ORM\ManyToOne(targetEntity="BackBee\NestedNode\Category", inversedBy="_descendants", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="root_uid", referencedColumnName="uid", onDelete="SET NULL")
     * @Serializer\Exclude
     */
    protected $_root;

    /**
     * The parent node.
     *
     * @var \BackBee\NestedNode\Category
     * @ORM\ManyToOne(targetEntity="\BackBee\NestedNode\Category", inversedBy="_children", cascade={"persist"}, fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="parent_uid", referencedColumnName="uid")
     */
    protected $_parent;

    /**
     * The category.
     *
     * @var string
     * @ORM\Column(type="string", name="category")
     *
     * @Serializer\Expose
     * @Serializer\SerializedName("category")
     * @Serializer\Type("string")
     */
    protected $_category;

    /**
     * Descendants nodes.
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="\BackBee\NestedNode\Category", mappedBy="_root", fetch="EXTRA_LAZY")
     */
    protected $_descendants;

    /**
     * Direct children nodes.
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="\BackBee\NestedNode\Category", mappedBy="_parent", fetch="EXTRA_LAZY")
     */
    protected $_children;

    /**
     * A collection of AbstractClassContent indexed by this category.
     *
     * @ORM\ManyToMany(targetEntity="BackBee\ClassContent\AbstractClassContent", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="categories_contents",
     *      joinColumns={
     *          @ORM\JoinColumn(name="category_uid", referencedColumnName="uid")},
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="content_uid", referencedColumnName="uid")}
     *      )
     */
    protected $_content;

    /**
     * Class constructor.
     *
     * @param string $uid The unique identifier of the category
     */
    public function __construct($uid = null)
    {
        parent::__construct($uid);

        $this->_content = new ArrayCollection();
    }

    /**
     * Returns the category.
     *
     * @return string
     */
    public function getCategory()
    {
        return $this->_category;
    }

    /**
     * Returns a collection of indexed AbstractClassContent.
     *
     * @return Doctrine\Common\Collections\Collection
     * @codeCoverageIgnore
     */
    public function getContent()
    {
        return $this->_content;
    }

    /**
     * Sets the category.
     *
     * @param string $category
     *
     * @return \BackBee\NestedNode\Category
     */
    public function setCategory($category)
    {
        $this->_category = $category;

        return $this;
    }

    /**
     * Adds a content to the collection.
     *
     * @param  BackBee\ClassContent\AbstractClassContent $content
     * @return \BackBee\NestedNode\Category
     */
    public function addContent(AbstractClassContent $content)
    {
        $this->_content->add($content);

        return $this;
    }

    /**
     * Removes a content from the collection.
     *
     * @param \BackBee\ClassContent\AbstractClassContent $content
     */
    public function removeContent(AbstractClassContent $content)
    {
        $this->_content->removeElement($content);
    }

    /**
     * Returns data associated to $var for rendering assignation, all data if NULL provided.
     *
     * @param string $var
     *
     * @return string|array|null
     */
    public function getData($var = null)
    {
        return null !== $var ? null : [];
    }

    /**
     * Returns parameters associated to $var for rendering assignation, all data if NULL provided.
     *
     * @param string $var
     *
     * @return string|array|null
     */
    public function getParam($var = null)
    {
        $param = array(
            'left' => $this->getLeftnode(),
            'right' => $this->getRightnode(),
            'level' => $this->getLevel(),
        );

        if (null !== $var) {
            if (false === array_key_exists($var, $param)) {
                return;
            }

            return $param[$var];
        }

        return $param;
    }

    /**
     * Returns TRUE if the page can be rendered.
     *
     * @return Boolean
     * @codeCoverageIgnore
     */
    public function isRenderable()
    {
        return true;
    }

    /**
     * Returns default template name.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return str_replace(array("BackBee".NAMESPACE_SEPARATOR."NestedNode".NAMESPACE_SEPARATOR, NAMESPACE_SEPARATOR), array("", DIRECTORY_SEPARATOR), get_class($this));
    }

    /**
     * Returns a stdObj representation of the node.
     *
     * @return \stdClass
     */
    public function toStdObject()
    {
        $object = new \stdClass();
        $object->uid = $this->getUid();
        $object->level = $this->getLevel();
        $object->category = $this->getCategory();
        $object->children = array();
        return $object;
    }

    /**
     *
     */
    public function jsonSerialize()
    {
        return [
            'uid'          => $this->getUid(),
            'root_uid'     => $this->getRoot()->getUid(),
            'parent_uid'   => $this->getParent() ? $this->getParent()->getUid() : null,
            'category'      => $this->getCategory(),
            'has_children' => $this->hasChildren(),
            'created'      => $this->getCreated() ? $this->getCreated()->getTimestamp() : null,
            'modified'     => $this->getModified() ? $this->getModified()->getTimestamp() : null,
        ];
    }

}
