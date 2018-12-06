<?php
/**
 * Copyright (c) 2018 Wakers.cz
 *
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 *
 */


namespace Wakers\PageModule\Repository;


use Propel\Runtime\Collection\ObjectCollection;
use Wakers\BaseModule\Database\AbstractDatabase;
use Wakers\PageModule\Database\PageUrl;
use Wakers\PageModule\Database\PageUrlQuery;


class PageUrlRepository extends AbstractDatabase
{

    /**
     * @var ObjectCollection|PageUrl[]|NULL
     */
    protected $allPageUrlJoinPage;


    /**
     * Cashed collection
     * Vrací všechny PageUrl s relací (join) Page
     * @param bool $refresh
     * @return ObjectCollection|PageUrl[]
     */
    public function findAllJoinPage(bool $refresh = FALSE) : ObjectCollection
    {
        if (!$this->allPageUrlJoinPage || $refresh)
        {
            $this->allPageUrlJoinPage = PageUrlQuery::create()
                ->joinWithPage()
                ->usePageQuery()
                    ->filterByTreeLevel(['min' => 2])
                ->endUse()
                ->orderByUrl()
                ->find();
        }

        return $this->allPageUrlJoinPage;
    }


    /**
     * Vrací PageUrl podle $url
     * @param string $url
     * @return PageUrl|NULL
     */
    public function findOneByUrl(string $url) : ?PageUrl
    {
        return PageUrlQuery::create()
            ->findOneByUrl($url);
    }
}