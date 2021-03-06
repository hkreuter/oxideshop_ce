<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Core;

use OxidEsales\Eshop\Application\Model\Country;

/**
 * Company VAT identification number checker. Check if number belongs to the country.
 */
class CompanyVatInCountryChecker extends \OxidEsales\Eshop\Core\CompanyVatInChecker implements \OxidEsales\Eshop\Core\Contract\ICountryAware
{
    /**
     * Error string if country mismatch
     */
    const ERROR_ID_NOT_VALID = 'ID_NOT_VALID';

    /**
     * Country
     *
     * @var Country
     */
    private $_oCountry = null;

    /**
     * Country setter
     */
    public function setCountry(Country $country)
    {
        $this->_oCountry = $country;
    }

    /**
     * Country getter
     *
     * @return Country
     */
    public function getCountry()
    {
        return $this->_oCountry;
    }

    /**
     * Validates.
     *
     * @param \OxidEsales\Eshop\Application\Model\CompanyVatIn $vatIn
     *
     * @return bool
     */
    public function validate(\OxidEsales\Eshop\Application\Model\CompanyVatIn $vatIn)
    {
        $result = false;
        $country = $this->getCountry();
        if (!is_null($country)) {
            $result = ($country->getVATIdentificationNumberPrefix() === $vatIn->getCountryCode());
            if (!$result) {
                $this->setError(self::ERROR_ID_NOT_VALID);
            }
        }

        return $result;
    }
}
