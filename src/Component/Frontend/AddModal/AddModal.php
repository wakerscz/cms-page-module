<?php
/**
 * Copyright (c) 2018 Wakers.cz
 *
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 *
 */


namespace Wakers\PageModule\Component\Frontend\AddModal;


use Nette\Application\UI\Form;
use Wakers\BaseModule\Component\Frontend\BaseControl;
use Wakers\BaseModule\Util\AjaxValidate;
use Wakers\BaseModule\Database\DatabaseException;
use Wakers\LangModule\Database\Lang;
use Wakers\LangModule\Repository\LangRepository;
use Wakers\LangModule\Translator\Translate;
use Wakers\PageModule\Manager\PageManager;
use Wakers\PageModule\Manager\PageUrlManager;
use Wakers\PageModule\Repository\PageRepository;


class AddModal extends BaseControl
{
    use AjaxValidate;


    /**
     * @var LangRepository
     */
    protected $langRepository;


    /**
     * @var PageRepository
     */
    protected $pageRepository;


    /**
     * @var PageManager
     */
    protected $pageManager;


    /**
     * @var PageUrlManager
     */
    protected $pageUrlManager;


    /**
     * @var Lang
     */
    protected $activeLang;


    /**
     * @var Translate
     */
    protected $translate;


    /**
     * @var array
     */
    protected $defaults = [];


    /**
     * @var callable
     */
    public $onOpen = [];


    /**
     * AddModal constructor.
     * @param LangRepository $langRepository
     * @param PageRepository $pageRepository
     * @param PageManager $pageManager
     * @param PageUrlManager $pageUrlManager
     * @param Translate $translate
     */
    public function __construct(
        LangRepository $langRepository,
        PageRepository $pageRepository,
        PageManager $pageManager,
        PageUrlManager $pageUrlManager,
        Translate $translate
    ) {
        $this->langRepository = $langRepository;
        $this->pageRepository = $pageRepository;
        $this->pageManager = $pageManager;
        $this->pageUrlManager = $pageUrlManager;
        $this->translate = $translate;

        $this->activeLang = $langRepository->getActiveLang();
    }


    /**
     * Render
     */
    public function render() : void
    {
        $this->template->setFile(__DIR__ . '/templates/addModal.latte');
        $this->template->render();
    }


    /**
     * @return Form
     * @throws \Propel\Runtime\Exception\PropelException
     */
    protected function createComponentAddForm() : Form
    {
        $pageUrls = [];
        $pageNames[0] = 'Bez nadřazené stránky';

        $tree = $this->pageRepository->findAllByLevelNameByLangAsTree($this->activeLang);
        $pages = $this->pageRepository->findAllByLevelNameByLang($tree);

        foreach($pages as $page)
        {
            $pageUrls[$page->getId()] = $page->getPageUrl()->getUrl();
            $pageNames[$page->getId()] = str_repeat('––', $page->getLevel() - 2) . ' ' . $page->getName();
        }

        $views = [];

        foreach ($this->pageRepository->getViews() as $view)
        {
            $views[$view] = $view;
        }


        $languages = [];

        foreach ($this->langRepository->getLangs() as $language)
        {
            $languages[] = $language->getName();
        }

        $urlTypes = [
            0 => 'Automatická (stromově)',
            1 => 'Vlastní - výjimečné případy'
        ];


        $form = new Form;

        $form->getElementPrototype()->addAttributes([
            'data-active-lang' => $this->langRepository->getActiveLang()->getName(),
            'data-languages' => $languages,
            'data-pages' => $pageUrls
        ]);

        $form->addText('name')
            ->addRule(Form::MAX_LENGTH, 'Maximal length of page name is %d chars.', 64)
            ->addRule(Form::MIN_LENGTH, 'Minimal length of page name is %d chars.', 3)
            ->setRequired('Page name is required.');

        $form->addSelect('parentPageId', NULL, $pageNames)
            ->setRequired(FALSE);

        $form->addSelect('view', NULL, $views)
            ->setRequired('Page view is required.');

        $form->addText('url')
            ->addRule(Form::PATTERN, 'Allowed chars for url are: a-z, 0-9, slash, dash', PageRepository::URL_REGEX)
            ->addRule(Form::MAX_LENGTH, 'Maximal length of url is %d', 255)
            ->addRule(Form::MIN_LENGTH, 'Minimal length of url is %d', 2)
            ->setRequired('Page URL is required.')
            ->addFilter(function ($value) {
                return $this->pageManager->webalizeUrl($value);
            });

        $form->addSelect('urlType', NULL, $urlTypes)
            ->setOmitted(TRUE);

        $form->addSubmit('save');

        $form->setDefaults($this->defaults);


        $form->onValidate[] = function (Form $form) { $this->validate($form); };
        $form->onSuccess[] = function (Form $form) { $this->success($form); };

        return $form;
    }


    /**
     * @param Form $form
     * @throws \Nette\Application\AbortException
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function success(Form $form) : void
    {
        if ($this->presenter->isAjax())
        {
            $values = $form->getValues();

            $this->pageManager->getConnection()->beginTransaction();

            try
            {
                $page = $this->pageManager->addPage($this->activeLang, $values->name, $values->view, $values->parentPageId);
                $this->pageUrlManager->saveUrl($this->activeLang, $page, $values->url);

                $this->pageManager->getConnection()->commit();

                $this->presenter->notification(
                    $this->translate->translate('Page created'),
                    $this->translate->translate('Page has been created. Do not forget to publish it.'),
                    'success'
                );

                $this->presenter->redirect(':App:Run:setUrl', ['url' => $page->getPageUrl()->getUrl()]);
            }
            catch (DatabaseException $exception)
            {
                $this->pageManager->getConnection()->rollBack();

                $this->presenter->notificationAjax(
                    $this->translate->translate('Error'),
                    $exception->getMessage(),
                    'error'
                );
            }
        }
    }


    /**
     * @param string $view
     * @param int|NULL $parentPageId
     */
    public function handleOpen(string $view, int $parentPageId = NULL) : void
    {
        if ($this->presenter->isAjax())
        {
            $this->defaults = [
                'view' => $view,
                'parentPageId' => $parentPageId
            ];

            $this->presenter->handleModalToggle('toggle', '#wakers_page_add_modal', FALSE);
            $this->onOpen();
        }
    }
}