<?php

/**
 * @copyright Copyright 2012-2013 Markus Kalkbrenner, bio.logis GmbH (https://www.biologis.com)
 * @license GPLv2
 * @author Markus Kalkbrenner <info@bio.logis.de>
 */

use biologis\HV\HVRawConnector;
use biologis\HV\HVRawConnectorUserNotAuthenticatedException;
use biologis\HV\HVRawConnectorAuthenticationExpiredException;

require '../vendor/autoload.php';

$appId = file_get_contents('app.id');

session_start();

ob_start();

print "Connecting HealthVault ...<br><hr>";
ob_flush();

try {
  $hv = new HVRawConnector(
    $appId,
    file_get_contents('app.fp'),
    'app.pem',
    $_SESSION
  );

  $hv->connect();

  $hv->authenticatedWcRequest('GetPersonInfo');

  #var_dump($hv->getRawResponse());

  $personId = $hv->getQueryPathResponse()->find('person-id')->text();
  $recordId = $hv->getQueryPathResponse()->find('selected-record-id')->text();

  print "person-id: <b>$personId</b><br>selected-record-id: <b>$recordId</b><br><hr>";
  ob_flush();

  foreach (HVRawConnector::$things as $thing => $thingId) {
    $hv->authenticatedWcRequest(
      'GetThings',
      '3',
      '<group max="30"><filter><type-id>' . $thingId . '</type-id></filter><format><section>core</section><xml/></format></group>',
      array('record-id' => $recordId)
    );

    print "Thing: <b>$thing</b><br>" . htmlentities($hv->getRawResponse()) . "<hr>";
    ob_flush();
  }
}
catch (HVRawConnectorUserNotAuthenticatedException $e) {
  print "You're not authenticated! ";
  printAuthenticationLink();
}
catch (HVRawConnectorAuthenticationExpiredException $e) {
  print "Your authentication expired! ";
  printAuthenticationLink();
}

function printAuthenticationLink() {
  global $appId;

  print '<a href="' . HVRawConnector::getAuthenticationURL($appId,
    'http' . (!empty($_SERVER["HTTP_SSL"]) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
    $_SESSION) .
    '">Authenticate</a>';
}