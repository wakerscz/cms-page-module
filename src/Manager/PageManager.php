<?php
/**
 * Copyright (c) 2018 Wakers.cz
 *
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 *
 */


namespace Wakers\PageModule\Manager;


use Nette\InvalidArgumentException;
use Wakers\BaseModule\Database\AbstractDatabase;
use Wakers\BaseModule\Database\DatabaseException;
use Wakers\LangModule\Database\Lang;
use Wakers\PageModule\Database\Page;
use Wakers\PageModule\Repository\PageRepository;


class PageManager extends AbstractDatabase
{
    /**
     * @var PageRepository
     */
    protected $pageRepository;


    /**
     * @var $pageUrlManager
     */
    protected $pageUrlManager;



    /**
     * PageManager constructor.
     * @param PageRepository $pageRepository
     * @param PageUrlManager $pageUrlManager
     */
    public function __construct(
        PageRepository $pageRepository,
        PageUrlManager $pageUrlManager
    ) {
        $this->pageRepository = $pageRepository;
        $this->pageUrlManager = $pageUrlManager;
    }


    /**
     * Vytvoří stránku, případně nastaví rodiče
     * @param string $name
     * @param string $view
     * @param Lang $lang
     * @param int $parentPageId
     * @param bool $isPublished
     * @return Page|NULL
     * @throws DatabaseException
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function addPage(Lang $lang, string $name, string $view, int $parentPageId = 0, bool $isPublished = FALSE) : Page
    {
        if ($parentPageId === 0)
        {
            $parentPage = $this->pageRepository->findOneLangRoot($lang);
        }
        else
        {
            $parentPage = $this->pageRepository->findOneById($parentPageId);
        }

        $pageByName = $this->pageRepository->findOneByName($name);

        if ($pageByName)
        {
            throw new DatabaseException("Stránka s názvem '{$name}' již existuje");
        }

        $page = new Page;
        $page->setParent($parentPage);
        $page->insertAsLastChildOf($parentPage);
        $page->setName($name);
        $page->setView($view);
        $page->setPublished($isPublished);
        $page->save();

        return $page;
    }


    /**
     * Uloží název stránky
     * @param Page $page
     * @param string $name
     * @throws DatabaseException
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function saveName(Page $page, string $name) : void
    {
        $pageByName = $this->pageRepository->findOneByName($name);

        if ($pageByName && $name === $pageByName->getName() && $pageByName !== $page)
        {
            throw new DatabaseException("Stránka s názvem '{$name}' již existuje");
        }

        $page->setName($name);
        $page->save();
    }


    /**
     * Nastaví rodiče stránky
     * @param Lang $lang
     * @param Page $page
     * @param int $parentPageId
     * @throws \Propel\Runtime\Exception\PropelException
     * @throws \Exception
     */
    public function saveParentPage(Lang $lang, Page $page, int $parentPageId) : void
    {
        if ($parentPageId === 0)
        {
            $parentPage = $this->pageRepository->findOneLangRoot($lang);
        }
        else
        {
            $parentPage = $this->pageRepository->findOneById($parentPageId);
        }

        if (!$parentPage)
        {
            throw new \Exception("Nadřazená stránka s id: '{$parentPageId}' nebyla nalezena.");
        }

        $page->setParent($parentPage);
        $page->moveToLastChildOf($parentPage);
        $page->save();
    }


    /**
     * Nastaví status (publikováno / nepublikováno)
     * @param Page $page
     * @param bool $published
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function savePublish(Page $page, bool $published) : void
    {
        $page->setPublished($published);
        $page->save();
    }


    /**
     * Metoda pro vytvoření pěkné URL
     * @param string $url
     * @return string
     */
    public function webalizeUrl(string $url) : string
    {
        $regex = PageRepository::URL_REGEX;

        if (preg_match("/{$regex}/", $url) === FALSE)
        {
            throw new InvalidArgumentException('Url contains wrong characters. Allowed characters are \'a-z, 0-9, slash, dash\'');
        }

        $url = preg_replace('~-{2,}~', '-', $url);
        $url = preg_replace('~/{2,}~', '/', $url);
        $url = trim($url, '-/');

        return $url;
    }


    /**
     * Dostupné pouze pro CLI
     * Vytvoří homepage (Page) pro specifikovaný jazyk (pužívá se přes konzoli - wakers:homepage-create)
     * @param Lang $lang
     * @throws DatabaseException
     * @throws \Exception
     */
    public function createRoot(Lang $lang) : void
    {
        if (php_sapi_name() !== 'cli')
        {
            throw new \Exception("Method createRoot() is allowed only in CLI mode.");
        }

        $root = $this->pageRepository->findOneAbsoluteRoot();

        if ($root === NULL)
        {
            $root = new Page;
            $root->setName('root');
            $root->setView('root');
            $root->makeRoot();
            $root->save();
        }

        $langRoot = $this->pageRepository->findOneLangRoot($lang, TRUE);

        if ($langRoot === NULL)
        {
            $langRootName = 'root-' . $lang->getName();

            $langRoot = new Page;
            $langRoot->setName($langRootName);
            $langRoot->setView($langRootName);
            $langRoot->setParent($root);
            $langRoot->insertAsLastChildOf($root);
            $langRoot->save();

            $this->pageUrlManager->saveUrl($lang, $langRoot, '__a@&*^-9' . $langRootName);
        }
    }
}