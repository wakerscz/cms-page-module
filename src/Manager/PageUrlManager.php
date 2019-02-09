<?php
/**
 * Copyright (c) 2018 Wakers.cz
 *
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 *
 */


namespace Wakers\PageModule\Manager;


use Wakers\BaseModule\Database\DatabaseException;
use Wakers\LangModule\Database\Lang;
use Wakers\LangModule\Repository\LangRepository;
use Wakers\PageModule\Database\Page;
use Wakers\PageModule\Database\PageUrl;
use Wakers\PageModule\Repository\PageUrlRepository;


class PageUrlManager
{
    /**
     * @var LangRepository
     */
    protected $langRepository;


    /**
     * @var PageUrlRepository
     */
    protected $pageUrlRepository;


    /**
     * PageUrlManager constructor.
     * @param LangRepository $langRepository
     * @param PageUrlRepository $pageUrlRepository
     */
    public function __construct(
        LangRepository $langRepository,
        PageUrlRepository $pageUrlRepository
    ) {
        $this->langRepository = $langRepository;
        $this->pageUrlRepository = $pageUrlRepository;
    }


    /**
     * Uloží PageUrl pro $page
     * @param Page $page
     * @param string $url
     * @param Lang|NULL $lang
     * @return PageUrl
     * @throws DatabaseException
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function saveUrl(Lang $lang, Page $page, string $url) : PageUrl
    {
        $pageUrl = $page->getPageUrl();

        if ($pageUrl === NULL)
        {
            $pageUrl = new PageUrl;
            $pageUrl->setPage($page);
        }

        $pageUrlByUrl = $this->pageUrlRepository->findOneByUrl($url);

        if ($pageUrlByUrl && $pageUrl !== $pageUrlByUrl)
        {
            throw new DatabaseException("Url '{$url}' již existuje.");
        }

        $pageUrl->setLang($lang);
        $pageUrl->setUrl($url);
        $pageUrl->save();

        return $pageUrl;
    }
}