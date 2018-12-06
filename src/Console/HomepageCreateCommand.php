<?php
/**
 * Copyright (c) 2018 Wakers.cz
 *
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 *
 */


namespace Wakers\PageModule\Console;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wakers\LangModule\Repository\LangRepository;
use Wakers\PageModule\Manager\PageManager;
use Wakers\PageModule\Manager\PageUrlManager;
use Wakers\PageModule\Repository\PageUrlRepository;


class HomepageCreateCommand extends Command
{
    /**
     * Configuration
     */
    protected function configure() : void
    {
        $this
            ->setName('wakers:homepage-create')
            ->setDescription('Create homepages by defined languages')
            ->addArgument('defaultLang', InputArgument::REQUIRED, 'Name (shortcut) of default language')
            ->addArgument('view', InputArgument::OPTIONAL, 'File name of the view')
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output) : void
    {
        /**
         * @var PageManager $pageManager
         * @var PageUrlManager $pageUrlManager
         * @var LangRepository $langRepository
         * @var PageUrlRepository $pageUrlRepository
         */

        $container = $this->getHelper('container');
        $langRepository = $container->getByType(LangRepository::class);
        $pageUrlRepository = $container->getByType(PageUrlRepository::class);
        $pageUrlManager = $container->getByType(PageUrlManager::class);
        $pageManager = $container->getByType(PageManager::class);

        $defaultLang = $input->getArgument('defaultLang');

        if ($langRepository->findOneByName($defaultLang) !== NULL)
        {
            $view = $input->getArgument('view');
            $view = $view === NULL ? 'home.latte' : $view;

            $definedLanguages = $langRepository->getLangs();

            foreach ($definedLanguages as $lang)
            {
                try
                {
                    $url = $lang->getName();
                    $name = 'Homepage - ' . $lang->getName();

                    if ($lang->getName() === $defaultLang)
                    {
                        $pageUrl =  $pageUrlRepository->findOneByUrl('home');

                        // Pokud již existuje homepage, vytvoří se URL podle názvu jazyka
                        $url = $pageUrl === NULL ? 'home' : $lang->getName();
                    }

                    $pageManager->createRoot($lang);

                    $page = $pageManager->addPage($lang, $name, $view, 0, TRUE);

                    $pageUrlManager->saveUrl($lang, $page, $url);

                    $output->writeln("<info>Homepage with url: '{$url}' has been successfully created.</info>");
                }
                catch (\Exception $exception)
                {
                    $output->writeln("<error>{$exception->getMessage()}</error>");
                }
            }
        }
        else
        {
            $output->writeln("<error>Default language: '{$defaultLang}' does not exists.</error>");
        }
    }
}