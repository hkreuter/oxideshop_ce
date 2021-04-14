<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Domain\Newsletter\Dao;

use Doctrine\DBAL\Exception;
use OxidEsales\EshopCommunity\Internal\Domain\Newsletter\DataObject\NewsletterRecipient;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;

class NewsletterRecipientsDao implements NewsletterRecipientsDaoInterface
{
    /**
     * @var QueryBuilderFactoryInterface
     */
    private $queryBuilderFactory;

    public function __construct(QueryBuilderFactoryInterface $queryBuilderFactory)
    {
        $this->queryBuilderFactory = $queryBuilderFactory;
    }

    /**
     * @param int $shopId
     *
     * @return NewsletterRecipient[]
     * @throws Exception
     */
    public function getNewsletterRecipients(int $shopId): array
    {
        $recipientList = [];

        $subscribersList = $this->getSubscribersList($shopId);

        foreach ($subscribersList as $row) {
            $recipient = new NewsletterRecipient();
            $recipient->setSalutation($row['Salutation']);
            $recipient->setFistName($row['Firstname']);
            $recipient->setLastName($row['Lastname']);
            $recipient->setEmail($row['Email']);
            $recipient->setOtpInState($row['OptInState']);
            $recipient->setCountry($row['Country']);
            $recipient->setUserGroups((string)$row['Groups']);
            $recipientList[] = $recipient;
        }

        return $recipientList;
    }

    /**
     * @param int $shopId
     *
     * @return array
     * @throws Exception
     */
    private function getSubscribersList(int $shopId): array
    {
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->select([
                'n.oxsal AS Salutation',
                'n.oxfname AS Firstname',
                'n.oxlname AS Lastname',
                'u.oxusername AS Email',
                'n.oxdboptin AS OptInState',
                'c.oxtitle AS Country',
                'group_concat(g.oxtitle) AS Groups'
            ])
            ->from('oxnewssubscribed', 'n')
            ->join('n', 'oxuser', 'u', 'u.oxid=n.oxuserid')
            ->join('u', 'oxcountry', 'c', 'u.oxcountryid=c.oxid')
            ->leftJoin('u', 'oxobject2group', 'o2g', 'u.oxid=o2g.oxobjectid')
            ->leftJoin('o2g', 'oxgroups', 'g', 'o2g.oxgroupsid=g.oxid')
            ->where('n.oxshopid = :shopId')
            ->setParameters(["shopId" => $shopId])
            ->groupBy('u.oxid')
            ->orderBy('g.oxtitle', 'ASC')
            ->addOrderBy('u.oxcreate', 'ASC');

        return $queryBuilder->execute()->fetchAllAssociative();
    }
}
