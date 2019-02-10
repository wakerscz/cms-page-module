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
use Wakers\BaseModule\Util\SetDisabledForm;
use Wakers\LangModule\Database\Lang;
use Wakers\LangModule\Repository\LangRepository;
use Wakers\PageModule\Database\Page;
use Wakers\PageModule\Manager\PageManager;
use Wakers\PageModule\Repository\PageRepository;
use Wakers\PageModule\Security\PageAuthorizator;


class PrimaryModal extends BaseControl
{
    use AjaxValidate;
    use SetDisabledForm;


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
     * @var callable
     */
    public $onSave = [];


    /**
     * PrimaryModal constructor.
     * @param LangRepository $langRepository
     * @param PageRepository $pageRepository
     * @param PageManager $pageManager
     */
    public function __construct(
        LangRepository $langRepository,
        PageRepository $pageRepository,
        PageManager $pageManager
    ) {
        $this->langRepository = $langRepository;
        $this->pageRepository = $pageRepository;
        $this->pageManager = $pageManager;

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
            ->setRequired('Název stránky je povinný.')
            ->addRule(Form::MIN_LENGTH, 'Minimální délka názvu stránky jsou %d znaky.', 3)
            ->addRule(Form::MAX_LENGTH, 'Maximální délka názvu stránky je %d znaků.', 64);


        $form->addSelect('parentPageId', NULL, $pageNames)
            ->setRequired(FALSE);

        $form->addSubmit('save');

        $form->onValidate[] = function (Form $form) { $this->validate($form); };
        $form->onSuccess[] = function (Form $form) {$this->success($form); };


        $form->setDefaults([
            'name' => $this->activePage->getName(),
            'parentPageId' => $root === $this->activePage->getParent() ? 0 : $this->activePage->getParent()->getId()
        ]);


        if (!$this->presenter->user->isAllowed(PageAuthorizator::RES_PRIMARY_MODAL))
        {
            $this->setDisabledForm($form, TRUE);
        }

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
                    'Nastavení stránky',
                    'Hlavní nastavení stránky bylo úspěšně uloženo.',
                    'success',
                    FALSE
                );

                $this->onSave();
            }
            catch (DatabaseException $exception)
            {
                $this->presenter->notificationAjax(
                    'Chyba',
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