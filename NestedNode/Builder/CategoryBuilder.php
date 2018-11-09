<?php

namespace BackBee\NestedNode\Builder;

/**
 * @author q.guitard <quentin.guitard@lp-digital.fr>
 */
class CategoryBuilder
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * CategoryBuilder's constructor.
     *
     * @param Doctrine\ORM\EntityManager $em
     */
    public function __construct(\Doctrine\ORM\EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Create new entity BackBee\NestedNode\Category with $category if not exists.
     *
     * @param string $category
     *
     * @return BackBee\NestedNode\Category
     */
    public function createCategoryIfNotExists($category, $do_persist = true)
    {

        if (null === $category_object = $this->em->getRepository('BackBee\NestedNode\Category')->exists($category)) {
            $category_object = new \BackBee\NestedNode\Category();
            //$category_object->setRoot($this->em->find('BackBee\NestedNode\Category', md5('root')));
            $category_object->setCategory(preg_replace('#[/\"]#', '', trim($category)));

            if (true === $do_persist) {
                $this->em->persist($category_object);
                $this->em->flush($category_object);
            }
        }
        

        return $category_object;
    }
}
