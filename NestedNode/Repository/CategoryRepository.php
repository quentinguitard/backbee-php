<?php

/*
 * @author Quentin Guitard <quentin.guitard@lp-digital.fr>
 */

namespace BackBee\NestedNode\Repository;

use BackBee\ClassContent\Element\Category;
use BackBee\NestedNode\Page;
use Doctrine\ORM\Tools\Pagination\Paginator;
use BackBee\NestedNode\Repository\NestedNodeRepository;
use Exception;

/**
 * Category repository.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      n.bremont <nicolas.bremont@lp-digital.fr>
 */
class CategoryRepository extends NestedNodeRepository
{
    public function getLikeCategories($cond, $max = 10)
    {
        try {
            $q = $this->createQueryBuilder('k')->andWhere('k._category like :key')->orderBy('k._category', 'ASC')->setMaxResults($max)
                    ->setParameters(array('key' => $cond.'%'))
                    ->getQuery();

            return $q->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return;
        } catch (Exception $e) {
            return;
        }
    }

    public function getCategories($parent, $orderInfos, $paging = array())
    {
        $qb = $this->createQueryBuilder('kw');
        $qb->andParentIs($parent);

        /* order */
        if (is_array($orderInfos)) {
            if (array_key_exists('field', $orderInfos) && array_key_exists('dir', $orderInfos)) {
                $qb->orderBy('kw.'.$orderInfos['field'], $orderInfos['dir']);
            }
        }
        /* paging */
        if (is_array($paging) && !empty($paging)) {
            if (array_key_exists('start', $paging) && array_key_exists('limit', $paging)) {
                $qb->setFirstResult($paging['start'])
                       ->setMaxResults($paging['limit']);
                $result = new Paginator($qb);
            }
        } else {
            $result = $qb->getQuery()->getResult();
        }

        return $result;
    }

    public function getRoot()
    {
        try {
            $q = $this->createQueryBuilder('k')
                    ->andWhere('k._parent is NULL')
                    ->getQuery();

            return $q->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return;
        } catch (Exception $e) {
            return;
        }
    }

    public function getCategoryTreeAsArray($node = null)
    {
        $node = (is_null($node)) ? $this->getRoot() : $node;
        $nodeInfos = new \stdClass();
        $nodeInfos->uid = $node->getUid();
        $nodeInfos->level = $node->getLevel();
        $nodeInfos->category = $node->getCategory();
        $nodeInfos->children = array();
        $children = $this->getDescendants($node, 1);
        if (is_array($children)) {
            foreach ($children as $child) {
                $nodeInfos->children[] = $this->getCategoryTreeAsArray($child);
            }
        }

        return $nodeInfos;
    }

    public function getContentsIdByCategories($categories, $limitToOnline = true)
    {
        try {
            if (isset($categories) && !empty($categories)) {
                $categories = (is_array($categories)) ? $categories : array($categories);
                $db = $this->_em->getConnection();
                $queryString = 'SELECT content.uid
                    FROM
                        categories_contents
                    LEFT JOIN
                        content on (content.uid = categories_contents.content_uid)
                    LEFT JOIN
                        page on (content.node_uid = page.uid)
                    WHERE
                        categories_contents.categories_uid IN (?)';

                if ($limitToOnline) {
                    $queryString .= ' AND page.state IN (?)';
                    $pageStates = array(Page::STATE_ONLINE, Page::STATE_ONLINE | Page::STATE_HIDDEN);
                    $secondParam = \Doctrine\DBAL\Connection::PARAM_STR_ARRAY;
                } else {
                    $pageStates = Page::STATE_DELETED;
                    $queryString .= ' AND page.state < (?)';
                    $secondParam = 1;
                }
                $stmt = $db->executeQuery($queryString, array($categories, $pageStates), array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY, $secondParam));
                $result = array();
                while ($contendId = $stmt->fetchColumn()) {
                    $result[] = $contendId;
                }

                return $result;
            }
        } catch (\Doctrine\ORM\NoResultException $e) {
            return;
        } catch (Exception $e) {
            return;
        }
    }

    /**
     * Returns the nested categories object according to the element category objects provided
     * Also set the parameter 'objectCategory' from the element to the nested category.
     *
     * @param array $elements
     *
     * @return array
     */
    public function getCategoriesFromElements(&$elements = array())
    {
        if (0 === count($elements)) {
            return array();
        }

        $uids = array();
        $assoc = array();
        foreach ($elements as &$element) {
            if ($element instanceof Categories) {
                $uids[] = $element->value;
                $assoc[$element->value] = &$element;
            } elseif (true === is_string($element)) {
                $uids[] = trim($element);
            }
        }
        unset($element);

        $objects = $this->createQueryBuilder('k')
                ->where('k._uid IN (:uids)')
                ->setParameter('uids', $uids)
                ->getQuery()
                ->getResult();

        foreach ($objects as $object) {
            if (true === array_key_exists($object->getUid(), $assoc)) {
                $assoc[$object->getUid()]->setParam('objectCategory', [$object]);
            }
        }

        return $objects;
    }

    /**
     * Check if given category already exists in database; it's case sensitive and make difference
     * between "e" and "é".
     *
     * @param string $category string
     *
     * @return object|null return object if it already exists, else null
     */
    public function exists($category)
    {
        $object = null;
        $result = $this->_em->getConnection()->executeQuery(sprintf(
            'SELECT uid FROM category WHERE hex(lower(category)) = hex(lower("%s"))',
            preg_replace('#[/\"]#', '', trim($category))
        ))->fetchAll();

        if (0 < count($result)) {
            $uid = array_shift($result);
            $object = $this->find($uid['uid']);
        }

        return $object;
    }
}
