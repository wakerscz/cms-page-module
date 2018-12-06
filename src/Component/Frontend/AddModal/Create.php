<?php
/**
 * Copyright (c) 2018 Wakers.cz
 *
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 *
 */


namespace Wakers\PageModule\Component\Frontend\AddModal;


trait Create
{
    /**
     * @var IAddModal
     * @inject
     */
    public $IPage_AddModal;


    /**
     * Modální okno pro vytvoření nové stránky
     * @return AddModal
     */
    protected function createComponentPageAddModal() : object
    {
        $control = $this->IPage_AddModal->create();

        $control->onOpen[] = function () use ($control)
        {
            $control->redrawControl('pageAddForm');
        };

        return $control;
    }
}