<?php
/**
 * Copyright (c) 2018 Wakers.cz
 *
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 *
 */


namespace Wakers\PageModule\Component\Frontend\UrlModal;


use Nette\Application\UI\Form;
use Wakers\BaseModule\Component\Frontend\BaseControl;
use Wakers\BaseModule\Util\AjaxValidate;
use Wakers\BaseModule\Database\DatabaseException;
use Wakers\LangModule\Database\Lang;
use Wakers\LangModule\Repository\LangRepository;
use Wakers\LangModule\Translator\Translate;
use Wakers\PageModule\Database\Page;
use Wakers\PageModule\Manager\PageManager;
use Wakers\PageModule\Manager\PageUrlManager;
use Wakers\PageModule\Repository\PageRepository;
use Wakers\PageModule\Repository\PageUrlRepository;


class UrlModal extends BaseControl
{
    use AjaxValidate;


    /**
     * @var PageRepository
     */
    protected $pageRepository;


    /**
     * @var PageUrlRepository
     */
    protected $pageUrlRepository;


    /**
     * @var LangRepository
     */
    protected $langRepository;


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
    public $onSaveFail = [];


    /**
     * UrlModal constructor.
     * @param LangRepository $langRepository
     * @param PageUrlRepository $pageUrlRepository
     * @param PageRepository $pageRepository
     * @param PageManager $pageManager
     * @param PageUrlManager $pageUrlManager
     * @param Translate $translate
     */
    public function __construct(
        LangRepository $langRepository,
        PageUrlRepository $pageUrlRepository,
        PageRepository $pageRepository,
        PageManager $pageManager,
        PageUrlManager $pageUrlManager,
        Translate $translate
    ) {
        $this->langRepository = $langRepository;
        $this->pageUrlRepository = $pageUrlRepository;
        $this->pageRepository = $pageRepository;
        $this->pageManager = $pageManager;
        $this->pageUrlManager = $pageUrlManager;
        $this->translate = $translate;

        $this->activeLang = $langRepository->getActiveLang();
        $this->activePage = $pageRepository->getActivePage();
    }


    /**
     * Render
     */
    public function render() : void
    {
        $this->template->urls = $this->pageUrlRepository->findAllJoinPage();

        $this->template->setFile(__DIR__ . '/templates/urlModal.latte');
        $this->template->render();
    }


    /**
     * Form
     * @return Form
     * @throws \Propel\Runtime\Exception\PropelException
     */
    protected function createComponentUrlForm() : Form
    {
        $form = new Form;

        $form->addText('url')
            ->addRule(Form::PATTERN, 'Allowed chars for url are: a-z, 0-9, slash, dash', PageRepository::URL_REGEX)
            ->addRule(Form::MAX_LENGTH, 'Maximal length of url is %d', 255)
            ->addRule(Form::MIN_LENGTH, 'Minimal length of url is %d', 2)
            ->setRequired('Page URL is required.')
            ->addFilter(function ($value) {
                return $this->pageManager->webalizeUrl($value);
            });

        $form->addSubmit('save');

        $form->setDefaults([
            'url' => $this->activePage->getPageUrl()->getUrl()
        ]);


        $form->onValidate[] = function (Form $form) { $this->validate($form); };
        $form->onSuccess[] = function (Form $form) { $this->success($form); };

        return $form;
    }


    /**
     * Success
     * @param Form $form
     * @throws \Propel\Runtime\Exception\PropelException
     * @throws \Nette\Application\AbortException
     */
    public function success(Form $form) : void
    {
        if ($this->presenter->isAjax())
        {
            $values = $form->getValues();

            try
            {
                $urlBefore = $this->activePage->getPageUrl()->getUrl();
                $urlAfter = $this->pageUrlManager->saveUrl($this->activeLang, $this->activePage, $values->url);

                if ($urlBefore !== $urlAfter->getUrl())
                {
                    $this->presenter->notification(
                        $this->translate->translate('Page url updated'),
                        $this->translate->translate('Page url has been successfully updated.'),
                        'success'
                    );

                    $this->presenter->redirect(':App:Run:setUrl', ['url' => $urlAfter->getUrl()]);
                }
                else
                {
                    $this->presenter->notificationAjax(
                        $this->translate->translate('Page url updated'),
                        $this->translate->translate('Page url has been successfully updated.'),
                        'success'
                    );
                }
            }
            catch (DatabaseException $exception)
            {
                $form->setValues([
                    'url' => $this->activePage->getPageUrl()->getUrl()
                ]);

                $this->presenter->notificationAjax(
                    $this->translate->translate('Error'),
                    $exception->getMessage(),
                    'error',
                    FALSE
                );

                $this->onSaveFail();
            }
        }
    }
}