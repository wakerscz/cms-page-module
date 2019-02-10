<?php
/**
 * Copyright (c) 2018 Wakers.cz
 *
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 *
 */


namespace Wakers\PageModule\Security;


use Wakers\BaseModule\Builder\AclBuilder\AuthorizatorBuilder;
use Wakers\UserModule\Security\UserAuthorizator;


class PageAuthorizator extends AuthorizatorBuilder
{
    const
        RES_MODULE = 'PAGE_RES_MODULE',                     // Celý modul
        RES_ADD_MODAL = 'PAGE_RES_ADD_MODAL',               // Modal pro přidání pod-stránky
        RES_PRIMARY_MODAL = 'PAGE_RES_PRIMARY_MODAL',       // Modal pro editaci názvu a zařazení
        RES_PUBLISH_HANDLE = 'PAGE_RES_PUBLISH_HANDLE',     // Handler pro publikování / skrytí
        RES_SUMMARY_MODAL = 'PAGE_RES_SUMMARY_MODAL',       // Modal pro přehled pod-stránek
        RES_URL_MODAL = 'RES_URL_MODAL'                     // Modal peo editaci URL pod-stránky
    ;


    public function create() : array
    {
        $this->addResource(self::RES_MODULE);
        $this->addResource(self::RES_ADD_MODAL);
        $this->addResource(self::RES_PRIMARY_MODAL);
        $this->addResource(self::RES_PUBLISH_HANDLE);
        $this->addResource(self::RES_SUMMARY_MODAL);
        $this->addResource(self::RES_URL_MODAL);


        $this->allow([
            UserAuthorizator::ROLE_EDITOR
        ], [
            self::RES_MODULE,
            self::RES_ADD_MODAL,
            self::RES_PRIMARY_MODAL,
            self::RES_PUBLISH_HANDLE,
            self::RES_SUMMARY_MODAL,
            self::RES_URL_MODAL
        ]);


        return parent::create();
    }
}