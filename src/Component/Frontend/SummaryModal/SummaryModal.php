<?php
/**
 * Copyright (c) 2018 Wakers.cz
 *
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 *
 */


namespace Wakers\PageModule\Component\Frontend\SummaryModal;


use Wakers\BaseModule\Component\Frontend\BaseControl;
use Wakers\LangModule\Database\Lang;
use Wakers\LangModule\Repository\LangRepository;
use Wakers\PageModule\Repository\PageRepository;


class SummaryModal extends BaseControl
{
    /**
     * @var LangRepository
     */
    protected $langRepository;


    /**
     * @var PageRepository
     */
    protected $pageRepository;


    /**
     * @var Lang
     */
    protected $activeLang;


    /**
     * SummaryModal constructor.
     * @param LangRepository $langRepository
     * @param PageRepository $pageRepository
     */
    public function __construct(LangRepository $langRepository, PageRepository $pageRepository)
    {
        $this->langRepository = $langRepository;
        $this->pageRepository = $pageRepository;

        $this->activeLang = $langRepository->getActiveLang();
    }


    /**
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function render() : void
    {
        $this->template->pages = $this->pageRepository->findAllByLevelNameByLangAsTree($this->activeLang);

        $this->template->setFile(__DIR__ . '/templates/summaryModal.latte');
        $this->template->render();
    }

}