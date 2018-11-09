<?php

namespace BackBeeCloud\ClassContent\Repository\Element;

use BackBee\ClassContent\Element\Category;
use BackBee\ClassContent\AbstractClassContent;
use BackBee\ClassContent\Exception\ClassContentException;
use BackBee\ClassContent\Repository\ClassContentRepository;
use BackBee\Security\Token\BBUserToken;

/**
 * category repository.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      q.guitard <quentin.guitard@lp-digital.fr>
 */
class CategoryRepository extends ClassContentRepository
{
    /**
     * Do update by post of the content editing form.
     *
     * @param  \BackBee\ClassContent\AbstractClassContent $content
     * @param  stdClass                            $value
     * @param  \BackBee\ClassContent\AbstractClassContent $parent
     *
     * @return \BackBee\ClassContent\Element\File
     * @throws ClassContentException Occures on invalid content type provided
     * @deprecated since version v1.1
     */
    public function getValueFromPost(AbstractClassContent $content, $value, AbstractClassContent $parent = null)
    {
        if (false === ($content instanceof Category)) {
            throw new ClassContentException('Invalid content type');
        }

        if (true === property_exists($value, 'value')) {
            $content->value = $value->value;

            if (null !== $realCategory = $this->_em->find('BackBee\NestedNode\Category', $value->value)) {
                if (null === $parent) {
                    throw new ClassContentException('Invalid parent content');
                }

                if (null === $realCategory->getContent() || false === $realCategory->getContent()->contains($parent)) {
                    $realCategory->addContent($parent);
                }
            }
        }

        return $content;
    }

    /**
     * Do removing content from the content editing form.
     *
     * @param  \BackBee\ClassContent\AbstractClassContent $content
     * @param  type                                $value
     * @param  \BackBee\ClassContent\AbstractClassContent $parent
     *
     * @return type
     *
     * @throws ClassContentException
     * @deprecated since version v1.1
     */
    public function removeFromPost(AbstractClassContent $content, $value = null, AbstractClassContent $parent = null)
    {
        if (false === ($content instanceof Category)) {
            throw new ClassContentException('Invalid content type');
        }

        $content = parent::removeFromPost($content);

        if (true === property_exists($value, 'value')) {
            if (null === $parent) {
                throw new ClassContentException('Invalid parent content');
            }

            if (null !== $realCategory = $this->_em->find('BackBee\NestedNode\Category', $value->value)) {
                if (true === $realCategory->getContent()->contains($parent)) {
                    $realCategory->removeContent($parent);
                }
            }
        }

        return $content;
    }

    /**
     * Updates categories_contents join.
     *
     * @param AbstractClassContent $content
     * @param mixed                $categories
     * @param BBUserToken          $token
     */
    public function updateCategoryLinks(AbstractClassContent $content, $categories, BBUserToken $token = null)
    {
        if (!is_array($categories)) {
            $categories = [$categories];
        }

        foreach ($categories as $category) {
            if (!($category instanceof Category)) {
                continue;
            }

            if (
                null !== $token &&
                null !== $draft = $this->_em->getRepository('BackBee\ClassContent\Revision')->getDraft($category, $token)
            ) {
                $category->setDraft($draft);
            }

            if (
                empty($category->value)
                || (null === $realCategory = $this->_em->find('BackBeeCloud\NestedNode\Category', $category->value))
            ) {
                continue;
            }

            if (!$realCategory->getContent()->contains($content)) {
                $realCategory->getContent()->add($content);
            }
        }
    }

    /**
     * Deletes outdated category content joins.
     *
     * @param AbstractClassContent $content
     * @param mixed                $categories
     */
    public function cleanCategoryLinks(AbstractClassContent $content, $categories)
    {
        if (!is_array($categories)) {
            $categories = [$categories];
        }

        $categoryUids = [];
        foreach ($categories as $category) {
            if (
                $category instanceof Category
                && !empty($category->value)
                && (null !== $realCategory = $this->_em->find('BackBeeCloud\NestedNode\Category', $category->value))
            ) {
                $categoryUids[] = $realCategory->getUid();
            }
        }

        $query = $this->_em
            ->getConnection()
            ->createQueryBuilder()
            ->select('c.category_uid')
            ->from('categories_contents', 'c')
        ;
        $query->where($query->expr()->eq('c.content_uid', $query->expr()->literal($content->getUid())));
        $savedCategories = $query->execute()->fetchAll(\PDO::FETCH_COLUMN);

        $linksToBeRemoved = array_diff($savedCategories, $categoryUids);
        if (count($linksToBeRemoved)) {
            $query = $this->_em
                ->getConnection()
                ->createQueryBuilder()
                ->delete('categories_contents')
            ;

            array_walk(
                $linksToBeRemoved,
                function(&$value, $key, $query) {
                    $value = $query->expr()->literal($value);
                },
                $query
            );

            $query
                ->where($query->expr()->eq('content_uid', $query->expr()->literal($content->getUid())))
                ->andWhere($query->expr()->in('category_uid', $linksToBeRemoved))
                ->execute()
            ;
        }
    }
}
