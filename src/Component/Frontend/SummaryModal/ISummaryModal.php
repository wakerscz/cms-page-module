<?php
/**
 * Copyright (c) 2018 Wakers.cz
 *
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 *
 */


namespace Wakers\PageModule\Component\Frontend\SummaryModal;


interface ISummaryModal
{
    /**
     * @return SummaryModal
     */
    public function create() : SummaryModal;
}