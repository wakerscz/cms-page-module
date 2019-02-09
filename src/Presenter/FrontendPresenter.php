<?php
/**
 * Copyright (c) 2018 Wakers.cz
 *
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 *
 */


namespace Wakers\PageModule\Presenter;


use Nette\Application\BadRequestException;
use Nette\Application\UI\Presenter;
use Wakers\LangModule\Repository\LangRepository;
use Wakers\OnPageModule\Repository\OnPageRepository;
use Wakers\PageModule\Repository\PageRepository;
use Wakers\PageModule\Security\PageAuthorizator;
use Wakers\UserModule\Repository\UserRepository;


abstract class FrontendPresenter extends Presenter
{
    use \Wakers\BaseModule\Component\Common\PermissionWatcher\Create;

    use \Wakers\BaseModule\Component\Common\AssetLoader\Create;
    use \Wakers\BaseModule\Component\Common\Notification\Create;
    use \Wakers\BaseModule\Component\Common\Modal\CreateHandleModalToggle;
    use \Wakers\BaseModule\Component\Common\Logout\CreateHandleLogout;

    use \Wakers\BaseModule\Component\Frontend\DashboardModal\Create;

    use \Wakers\UserModule\Component\Frontend\LoginModal\Create;
    use \Wakers\UserModule\Component\Frontend\SummaryModal\Create;
    use \Wakers\UserModule\Component\Frontend\AddModal\Create;
    use \Wakers\UserModule\Component\Frontend\EditModal\Create;
    use \Wakers\UserModule\Component\Frontend\RemoveModal\Create;

    use \Wakers\PageModule\Component\Frontend\PrimaryModal\Create;
    use \Wakers\PageModule\Component\Frontend\UrlModal\Create;
    use \Wakers\PageModule\Component\Frontend\AddModal\Create;
    use \Wakers\PageModule\Component\Frontend\SummaryModal\Create;
    use \Wakers\PageModule\Component\Frontend\Publish\Create;

    use \Wakers\OnPageModule\Component\Frontend\Head\Create;
    use \Wakers\OnPageModule\Component\Frontend\SocialModal\Create;
    use \Wakers\OnPageModule\Component\Frontend\PrimaryModal\Create;
    use \Wakers\OnPageModule\Component\Frontend\RedirectModal\Create;
    use \Wakers\OnPageModule\Component\Frontend\RemoveRedirectModal\Create;

    use \Wakers\LangModule\Component\Frontend\SystemModal\Create;

    use \Wakers\CategoryModule\Component\Frontend\Modal\Create;
    use \Wakers\CategoryModule\Component\Frontend\RemoveModal\Create;

    use \Wakers\StructureModule\Component\Frontend\RecipeModal\Create;
    use \Wakers\StructureModule\Component\Frontend\RecipeSummaryModal\Create;
    use \Wakers\StructureModule\Component\Frontend\RecipeRemoveModal\Create;

    use \Wakers\StructureModule\Component\Frontend\RecipeSlugModal\Create;
    use \Wakers\StructureModule\Component\Frontend\RecipeSlugRemoveModal\Create;

    use \Wakers\StructureModule\Component\Frontend\VariableSummaryModal\Create;
    use \Wakers\StructureModule\Component\Frontend\VariableModal\Create;
    use \Wakers\StructureModule\Component\Frontend\VariableRemoveModal\Create;

    use \Wakers\StructureModule\Component\Frontend\StructureModal\Create;
    use \Wakers\StructureModule\Component\Frontend\StructureRemoveModal\Create;

    use \Wakers\StructureModule\Component\Frontend\Printer\Create;


    /**
     * @var UserRepository
     * @inject
     */
    public $userRepository;


    /**
     * @var OnPageRepository
     * @inject
     */
    public $onPageRepository;


    /**
     * @var PageRepository
     * @inject
     */
    public $pageRepository;


    /**
     * @var LangRepository
     * @inject
     */
    public $langRepository;


    /**
     * @var int
     */
    protected $pagination = 1;


    /**
     * Startup kontroloje zda-li uživateli nebyla změněna oprávnění.
     * @throws \Nette\Application\ForbiddenRequestException
     * @throws \Propel\Runtime\Exception\PropelException
     */
    protected function startup() : void
    {
        parent::startup();

        $this->compareIdentityWithDb();
    }


    /**
     * Parametr $page je implicitně na NULL kvůli možnosti odkázat na homepage bez udávání parametru.
     * @param string|NULL $url
     * @param int $pagination
     * @throws BadRequestException
     * @throws \Nette\Application\AbortException
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function actionSetUrl(string $url = NULL, int $pagination = 1) : void
    {
        $redirectUrl = $this->onPageRepository->findOneRedirectByHttpRequest();

        if ($redirectUrl)
        {
            $domain = $this->context->getParameters()['baseDomain'];
            $url = $redirectUrl->getPage()->getPageUrl()->getUrl();
            $this->redirectUrl($domain . $url, 301);
        }

        if ($url === 'home')
        {
            $this->redirect(301, ':App:Run:setUrl');
        }

        $url = ($url === NULL) ? 'home' : $url;
        $page = $this->pageRepository->findOneByUrlJoinLangJoinUrl($url);

        if ($page === NULL)
        {
            throw new BadRequestException("Page with url '{$url}' does not exists.");
        }

        if ($page->getPublished() === FALSE && !$this->presenter->user->isAllowed(PageAuthorizator::RES_PAGE_MODULE))
        {
            throw new BadRequestException("Page '{$page->getPageUrl()->getUrl()}' is not published.", 403);
        }

        $this->pageRepository->setActivePage($page);
        $this->langRepository->setActiveLang($page->getPageUrl()->getLang());

        $this->pagination = $pagination > 1 ? $pagination : 1;

        $this->setView($page->getView());
    }


    /**
     * Before render
     */
    public function beforeRender() : void
    {
        $this->template->pageEntity = $this->pageRepository->getActivePage();
        $this->template->langEntity = $this->langRepository->getActiveLang();
    }


    /**
     * Format templates
     * @return array
     */
    public function formatTemplateFiles() : array
    {
        list(, $presenter) = \Nette\Application\Helpers::splitName($this->getName());
        $dir = dirname($this->getReflection()->getFileName());
        $dir = is_dir("{$dir}/templates") ? $dir : dirname($dir);
        return [
            "{$dir}/template/page/view/{$this->view}",
            "{$dir}/template/page/view/{$presenter}{$this->view}",
        ];
    }
}