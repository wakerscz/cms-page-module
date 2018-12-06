<?php
/**
 * Copyright (c) 2018 Wakers.cz
 *
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 *
 */


namespace Wakers\PageModule\Component\Frontend\PrimaryModal;


trait Create
{
    /**
     * @var IPrimaryModal
     * @inject
     */
    public $IPage_PrimaryModal;


    /**
     * Modální okno pro nastavení názvu a nadřazené stránky
     * @return PrimaryModal
     */
    protected function createComponentPagePrimaryModal() : object
    {
        $control = $this->IPage_PrimaryModal->create();

        $control->onSave[] = function () use ($control)
        {
            $this->getComponent('pageSummaryModal')->redrawControl('pageSummary');
            $this->getComponent('pageUrlModal')->redrawControl('urlSummary');
            $control->redrawControl('primaryForm');
        };

        return $control;
    }
}