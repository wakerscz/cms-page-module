<?php
/**
 * Copyright (c) 2018 Wakers.cz
 *
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 *
 */


namespace Wakers\PageModule\Repository;


use Nette\Utils\Finder;
use Propel\Runtime\Collection\ObjectCollection;
use Wakers\BaseModule\Database\AbstractDatabase;
use Wakers\LangModule\Database\Lang;
use Wakers\PageModule\Database\Page;
use Wakers\PageModule\Database\PageQuery;
use Wakers\BaseModule\Util\NestedSet;


class PageRepository extends AbstractDatabase
{
    /**
     * Cesta k do složky s views
     */
    const VIEW_PATH = __DIR__ . '/../../../../../app/template/page/view/';


    /**
     * Základní regulární výraz pro URL
     * Zbytek filtruje metoda $this->webalizeUrl()
     */
    const URL_REGEX = '[a-z0-9\-\/]*';


    /**
     * Aktuálně nastavená Page - mastavuje se v presenteru
     * @var Page
     */
    protected $activePage;


    /**
     * Instance nested-setu - společná pro celý PageRepository
     * @var NestedSet
     */
    protected $nestedSet;


    /**
     * Cashed collection
     * @var ObjectCollection|Page[]
     */
    protected $allPagesByLevelNameByLangAsTree;


    /**
     * Cashed collection
     * @var ObjectCollection|Page[]
     */
    protected $allPagesByLevelNameByLang;


    /**
     * Cashed item
     * @var Page
     */
    protected $absoluteRoot;


    /**
     * Cashed item
     * @var Page
     */
    protected $langRoot;


    /**
     * PageRepository constructor.
     */
    public function __construct()
    {
        $this->nestedSet = new NestedSet('name');
    }


    /**
     * Nastavuje aktuální Page
     * @param Page $activePage
     */
    public function setActivePage(Page $activePage) : void
    {
        $this->activePage = $activePage;
    }


    /**
     * Vrací aktuální Page
     * @return Page
     */
    public function getActivePage() : Page
    {
        return $this->activePage;
    }


    /**
     * Vrací názvy views (šablon)
     * @return string[]
     */
    public function getViews() : array
    {
        $view = [];

        foreach(Finder::findFiles('*.latte')->from(self::VIEW_PATH) as $file)
        {
            /** @var \SplFileInfo $file */
            $view[] = $file->getBasename();
        }

        return $view;
    }


    /**
     * Vrací rodiče $page
     * @param Page $root
     * @param Page $page
     * @return ObjectCollection
     */
    public function findAncestors(Page $root, Page $page) : ObjectCollection
    {
        $pages = new ObjectCollection;

        foreach ($root->getDescendants() as $item)
        {
            if (!($item->getLeftValue() >= $page->getLeftValue() && $item->getRightValue() <= $page->getRightValue()))
            {
                $pages->append($item);
            }
        }

        $tree = $this->nestedSet->getTree($pages, $root->getTreeLeft(), $root->getTreeRight());
        $pages = $this->nestedSet->getFlatCollection($tree);

        return $pages;
    }

    /**
     * @return ObjectCollection|Page[]
     */
    public function findAllJoinUrl() : ObjectCollection
    {
        return PageQuery::create()
            ->filterByTreeLevel(1)
            ->joinWithPageUrl()
            ->find();
    }


    /**
     * Cashed collection
     * Transformuje multilevel array ($tree) do kolekce seřazené podle názvů na jednotlivých úrovních stromu
     * @param array $tree
     * @param bool $refresh
     * @return ObjectCollection|Page[]
     */
    public function findAllByLevelNameByLang(array $tree, bool $refresh = FALSE) : ObjectCollection
    {
        if (!$this->allPagesByLevelNameByLang || $refresh)
        {
            $this->allPagesByLevelNameByLang = $this->nestedSet->getFlatCollection($tree);
        }

        return $this->allPagesByLevelNameByLang;
    }


    /**
     * Cashed array tree
     * Vrací multilevel array - strom položek seřazených podle názvů na jednotlivých úrovních stromu
     * @param Lang $lang
     * @param bool $refresh
     * @return array|Page[]
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function findAllByLevelNameByLangAsTree(Lang $lang, bool $refresh = FALSE) : array
    {
        if (!$this->allPagesByLevelNameByLangAsTree || $refresh)
        {
            $pages = PageQuery::create()
                ->joinWithPageUrl()
                ->usePageUrlQuery()
                    ->filterByLang($lang)
                ->endUse()
                ->orderByTreeLeft()
                ->findTree();

            $root = $pages->getFirst();

            $tree = $this->nestedSet->getTree($pages, $root->getTreeLeft(), $root->getTreeRight());

            $this->allPagesByLevelNameByLangAsTree = [
                'item' => NULL,
                'descendants' => $tree
            ];
        }

        return $this->allPagesByLevelNameByLangAsTree;
    }


    /**
     * Cashed item
     * Vrací root item (Page) podle specifikovaného jazyku ($lang)
     * @param Lang $lang
     * @param bool $refresh
     * @return Page|NULL
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function findOneLangRoot(Lang $lang, bool $refresh = FALSE) : ?Page
    {
        if (!$this->langRoot || $refresh)
        {
            $langRoot = PageQuery::create()
                ->usePageUrlQuery()
                    ->filterByLang($lang)
                ->endUse()
                ->filterByTreeLevel(1)
                ->orderByTreeLeft()
                ->findTree()
                ->getFirst();

            $this->langRoot = $langRoot;
        }

        return $this->langRoot;
    }


    /**
     * Cashed item
     * Vrací absolutního rodiče všech potomků Page
     * @return Page|NULL
     */
    public function findOneAbsoluteRoot() : ?Page
    {
        if (!$this->absoluteRoot)
        {
            $this->absoluteRoot = PageQuery::create()
                ->findRoot();
        }

        return $this->absoluteRoot;
    }


    /**
     * Vrací jednu Page s relalacemi (join) PageUrl a Lang
     * @param string $url
     * @return Page|NULL
     */
    public function findOneByUrlJoinLangJoinUrl(string $url) : ?Page
    {
        $page = PageQuery::create()
            ->joinWithPageUrl()
            ->leftJoinWithSocial()
            ->leftJoinWithPrimary()
            ->usePageUrlQuery()
                ->joinWithLang()
                ->filterByUrl($url)->limit(1)
            ->endUse()
            ->findOne();

        return $page;
    }


    /**
     * Vrací Page podle $id
     * @param int $id
     * @return Page|NULL
     */
    public function findOneById(int $id) : ?Page
    {
        return PageQuery::create()
            ->findOneById($id);
    }


    /**
     * Vrací Page podle $name
     * @param string $name
     * @return Page|NULL
     */
    public function findOneByName(string $name) : ?Page
    {
        return PageQuery::create()
            ->findOneByName($name);
    }
}
