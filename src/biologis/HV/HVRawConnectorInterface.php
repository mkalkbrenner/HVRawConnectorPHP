<?php

/**
 * @copyright Copyright 2012-2013 Markus Kalkbrenner, bio.logis GmbH (https://www.biologis.com)
 * @license GPLv2
 * @author Markus Kalkbrenner <info@bio.logis.de>
 */

namespace biologis\HV;


interface HVRawConnectorInterface {

  public function setCountry($country);

  public function setLanguage($language);

  public function setHealthVaultPlatform($healthVaultPlatform);

  public function connect();

  public function anonymousWcRequest($method, $methodVersion, $info, $additionalHeaders);

  public function authenticatedWcRequest($method, $methodVersion, $info, $additionalHeaders);

  public static function getAuthenticationURL($appId, &$session, $healthVaultAuthInstance, $redirect, $actionQs);

  public function invalidateSession();

  public function getRawResponse();

  public function getQueryPathResponse();

}
