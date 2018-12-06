<?php
/**
 * Copyright (c) 2018 Wakers.cz
 *
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 *
 */


namespace Wakers\PageModule\Component\Frontend\UrlModal;


trait Create
{
    /**
     * @var IUrlModal
     * @inject
     */
    public $IPage_UrlModal;


    /**
     * Modální okno pro nastavení URL adresy
     * @return UrlModal
     */
    protected function createComponentPageUrlModal() : object
    {
        $control = $this->IPage_UrlModal->create();

        $control->onSaveFail[] = function () use ($control)
        {
            $control->redrawControl('urlForm');
        };

        return $control;
    }
}