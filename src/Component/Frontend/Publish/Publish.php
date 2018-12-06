<?php
/**
 * Copyright (c) 2018 Wakers.cz
 *
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 *
 */


namespace Wakers\PageModule\Component\Frontend\Publish;


use Wakers\BaseModule\Component\Frontend\BaseControl;
use Wakers\LangModule\Translator\Translate;
use Wakers\PageModule\Database\Page;
use Wakers\PageModule\Manager\PageManager;
use Wakers\PageModule\Repository\PageRepository;


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
     * @var Translate
     */
    protected $translate;


    /**
     * @var callable
     */
    public $onSave = [];


    /**
     * Publish constructor.
     * @param PageRepository $pageRepository
     * @param PageManager $pageManager
     * @param Translate $translate
     */
    public function __construct(
        PageRepository $pageRepository,
        PageManager $pageManager,
        Translate $translate
    ) {
        $this->pageRepository = $pageRepository;
        $this->pageManager = $pageManager;
        $this->translate = $translate;

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
        if ($this->presenter->isAjax())
        {
            $published = !$this->activePage->getPublished();

            $this->pageManager->savePublish($this->activePage, $published);

            $type = $published ? 'success' : 'warning';
            $status = $published ? $this->translate->translate('published') : $this->translate->translate('disabled');

            $this->presenter->notificationAjax(
                $this->translate->translate('Page %status%', ['status' => $status]),
                $this->translate->translate('Publish status has been successfully updated.'),
                $type,
                FALSE
            );

            $this->onSave();
        }
    }
}