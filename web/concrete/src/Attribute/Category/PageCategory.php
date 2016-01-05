<?php

namespace Concrete\Core\Attribute\Category;

use Concrete\Core\Attribute\Category\SearchIndexer\PageSearchIndexer;
use Concrete\Core\Attribute\Category\SearchIndexer\StandardSearchIndexerInterface;
use Concrete\Core\Entity\Attribute\Key\Key;
use Concrete\Core\Entity\Attribute\Key\PageKey;
use Concrete\Core\Entity\Attribute\Type as AttributeType;
use Concrete\Core\Page\Page;
use Symfony\Component\HttpFoundation\Request;

class PageCategory extends AbstractCategory implements StandardSearchIndexerInterface
{

    public function createAttributeKey()
    {
        return new PageKey();
    }

    public function getIndexedSearchTable()
    {
        return 'CollectionSearchIndexAttributes';
    }

    public function getIndexedSearchPrimaryKeyValue($mixed)
    {
        return $mixed->getCollectionID();
    }

    public function getSearchIndexFieldDefinition()
    {
        return array(
            'columns' => array(
                array(
                    'name' => 'cID',
                    'type' => 'integer',
                    'options' => array('unsigned' => true, 'default' => 0, 'notnull' => true)
                )
            ),
            'primary' => array('cID')
        );
    }

    public function getAttributeRepository()
    {
        return $this->entityManager->getRepository('\Concrete\Core\Entity\Attribute\Key\PageKey');
    }

    /**
     * Takes an attribute key as created by the subroutine and assigns it to the page category.
     * @param Key $key
     */
    protected function assignToCategory(Key $key)
    {
        $this->entityManager->persist($key);
        $this->entityManager->flush();
        $attribute = new Attribute();
        $attribute->setAttributeKey($key);
        $this->entityManager->persist($attribute);
        $this->entityManager->flush();
        return $attribute;
    }

    public function addFromRequest(AttributeType $type, Request $request)
    {
        $key = parent::addFromRequest($type, $request);
        return $this->assignToCategory($key);
    }
    
    public function getAttributeValues($page)
    {
        $r = $this->entityManager->getRepository('\Concrete\Core\Entity\Page\AttributeValue');
        $values = $r->findBy(array(
            'cID' => $page->getCollectionID(),
            'cvID' => $page->getVersionID()
        ));
        return $values;
    }

}
