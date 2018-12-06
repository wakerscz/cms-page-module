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
    const RES_PAGE_MODULE = 'PAGE_RES_MODULE';


    /**
     * @return array
     */
    public function create() : array
    {
        $this->addResource(self::RES_PAGE_MODULE);

        $this->allow([
            UserAuthorizator::ROLE_EDITOR
        ], [
            self::RES_PAGE_MODULE
        ]);

        return parent::create();
    }
}