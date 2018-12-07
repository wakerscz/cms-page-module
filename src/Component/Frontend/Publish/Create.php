<?php
/**
 * Copyright (c) 2018 Wakers.cz
 *
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 *
 */


namespace Wakers\PageModule\Component\Frontend\Publish;


trait Create
{
    /**
     * @var IPublish
     * @inject
     */
    public $IPage_Publish;


    /**
     * Komponenta pro publikování stránky
     * @return Publish
     */
    protected function createComponentPagePublish() : object
    {
        $control = $this->IPage_Publish->create();

        $control->onSave[] = function () use ($control)
        {
            $this->getComponent('pageSummaryModal')->redrawControl('pageSummary');

            $control->redrawControl('publish');

            $this->redrawPrinters(); // TODO: Remove dependency on Structure Module
        };

        return $control;
    }
}