<?php
/**
 * Copyright (c) 2018 Wakers.cz
 *
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 *
 */


namespace Wakers\PageModule\Component\Frontend\UrlModal;


interface IUrlModal
{
    /**
     * @return UrlModal
     */
    public function create() : UrlModal;
}