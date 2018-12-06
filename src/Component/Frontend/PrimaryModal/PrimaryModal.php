<?php
/**
 * Copyright (c) 2018 Wakers.cz
 *
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 *
 */


namespace Wakers\PageModule\Component\Frontend\PrimaryModal;


use Nette\Application\UI\Form;
use Wakers\BaseModule\Component\Frontend\BaseControl;
use Wakers\BaseModule\Util\AjaxValidate;
use Wakers\BaseModule\Database\DatabaseException;
use Wakers\LangModule\Database\Lang;
use Wakers\LangModule\Repository\LangRepository;
use Wakers\LangModule\Translator\Translate;
use Wakers\PageModule\Database\Page;
use Wakers\PageModule\Manager\PageManager;
use Wakers\PageModule\Repository\PageRepository;


class PrimaryModal extends BaseControl
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
     * @var Page
     */
    protected $activePage;


    /**
     * @var Lang
     */
    protected $activeLang;


    /**
     * @var Translate
     */
    protected $translate;


    /**
     * @var callable
     */
    public $onSave = [];


    /**
     * PrimaryModal constructor.
     * @param LangRepository $langRepository
     * @param PageRepository $pageRepository
     * @param PageManager $pageManager
     * @param Translate $translate
     */
    public function __construct(
        LangRepository $langRepository,
        PageRepository $pageRepository,
        PageManager $pageManager,
        Translate $translate
    ) {
        $this->langRepository = $langRepository;
        $this->pageRepository = $pageRepository;
        $this->pageManager = $pageManager;
        $this->translate = $translate;

        $this->activeLang = $langRepository->getActiveLang();
        $this->activePage = $pageRepository->getActivePage();
    }


    /**
     * Render
     */
    public function render() : void
    {
        $this->template->setFile(__DIR__ . '/templates/primaryModal.latte');
        $this->template->render();
    }


    /**
     * @return Form
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function createComponentPrimaryForm() : Form
    {
        $root = $this->pageRepository->findOneLangRoot($this->activeLang);
        $ancestors = $this->pageRepository->findAncestors($root, $this->activePage);

        $pageNames[0] = 'Bez nadřazené stránky';

        foreach($ancestors as $page)
        {
            $pageNames[$page->getId()] = str_repeat('––', $page->getLevel() - 2) . ' ' . $page->getName();
        }

        $form = new Form;

        $form->addText('name')
            ->addRule(Form::MAX_LENGTH, 'Maximal length of page name is %d chars.', 64)
            ->addRule(Form::MIN_LENGTH, 'Minimal length of page name is %d chars.', 3)
            ->setRequired('Page name is required.');

        $form->addSelect('parentPageId', NULL, $pageNames)
            ->setRequired(FALSE);

        $form->addSubmit('save');

        $form->onValidate[] = function (Form $form) { $this->validate($form); };
        $form->onSuccess[] = function (Form $form) {$this->success($form); };


        $form->setDefaults([
            'name' => $this->activePage->getName(),
            'parentPageId' => $root === $this->activePage->getParent() ? 0 : $this->activePage->getParent()->getId()
        ]);

        return $form;
    }


    /**
     * Success
     * @param Form $form
     * @throws \Exception
     */
    public function success(Form $form) : void
    {
        if ($this->presenter->isAjax())
        {
            $values = $form->getValues();

            try
            {
                $this->pageManager->saveName($this->activePage, $values->name);
                $this->pageManager->saveParentPage($this->activeLang, $this->activePage, $values->parentPageId);

                $this->presenter->notificationAjax(
                    $this->translate->translate('Page updated'),
                    $this->translate->translate('Primary info has been successfully updated.'),
                    'success',
                    FALSE
                );

                $this->onSave();
            }
            catch (DatabaseException $exception)
            {
                $this->presenter->notificationAjax(
                    $this->translate->translate('Error'),
                    $exception->getMessage(),
                    'error'
                );
            }
            catch (\Exception $exception)
            {
                throw $exception;
            }
        }
    }
}