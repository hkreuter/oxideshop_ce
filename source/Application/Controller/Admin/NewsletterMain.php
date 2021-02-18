<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

/**
 * Admin Menu: Customer Info -> Newsletter -> Main.
 */
class NewsletterMain extends \OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController
{
    public function render()
    {
        parent::render();
        return "newsletter_main.tpl";
    }
}
