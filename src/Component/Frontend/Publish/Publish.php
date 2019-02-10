<?php
/**
 * Copyright (c) 2018 Wakers.cz
 *
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 *
 */


namespace Wakers\PageModule\Component\Frontend\Publish;


use Wakers\BaseModule\Component\Frontend\BaseControl;
use Wakers\PageModule\Database\Page;
use Wakers\PageModule\Manager\PageManager;
use Wakers\PageModule\Repository\PageRepository;
use Wakers\PageModule\Security\PageAuthorizator;


class Publish extends BaseControl
{
    /**
     * @var PageRepository
     */
    protected $pageRepository;


    /**
     * @var PageManager
     */
    protected $pageManager;


    /**
     * @var Page
     */
    protected $activePage;


    /**
     * @var callable
     */
    public $onSave = [];


    /**
     * Publish constructor.
     * @param PageRepository $pageRepository
     * @param PageManager $pageManager
     */
    public function __construct(
        PageRepository $pageRepository,
        PageManager $pageManager
    ) {
        $this->pageRepository = $pageRepository;
        $this->pageManager = $pageManager;

        $this->activePage = $pageRepository->getActivePage();
    }


    /**
     * Render
     */
    public function render() : void
    {
        $this->template->setFile(__DIR__ . '/templates/publish.latte');
        $this->template->render();
    }


    /**
     * Publikovat / Vypnout stránku
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function handlePublish() : void
    {
        if ($this->presenter->isAjax() && $this->presenter->user->isAllowed(PageAuthorizator::RES_PUBLISH_HANDLE))
        {
            $published = !$this->activePage->getPublished();

            $this->pageManager->savePublish($this->activePage, $published);

            $type = $published ? 'success' : 'warning';
            $status = $published ? 'publikována' : 'vypnuta';

            $this->presenter->notificationAjax(
                "Stránka {$status}",
                "Status stránky byl upraven na: {$status}",
                $type,
                FALSE
            );

            $this->onSave();
        }
    }
}