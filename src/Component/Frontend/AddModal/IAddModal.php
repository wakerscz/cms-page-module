<?php
/**
 * Copyright (c) 2018 Wakers.cz
 *
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 *
 */


namespace Wakers\PageModule\Component\Frontend\AddModal;


interface IAddModal
{
    /**
     * @return AddModal
     */
    public function create() : AddModal;
}