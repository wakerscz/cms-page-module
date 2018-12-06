<?php
/**
 * Copyright (c) 2018 Wakers.cz
 *
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 *
 */


namespace Wakers\PageModule\Component\Frontend\SummaryModal;


trait Create
{
    /**
     * @var ISummaryModal
     * @inject
     */
    public $IPage_SummaryModal;


    /**
     * Modální okno s přehledem všech stránek
     * @return SummaryModal
     */
    protected function createComponentPageSummaryModal() : object
    {
        return $this->IPage_SummaryModal->create();
    }
}