<?php

/**
 * @copyright Copyright 2012-2013 Markus Kalkbrenner, bio.logis GmbH (https://www.biologis.com)
 * @license GPLv2
 * @author Markus Kalkbrenner <info@bio.logis.de>
 */

namespace biologis\HV;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;


class HVRawConnector extends AbstractHVRawConnector implements LoggerAwareInterface {
  public static $version = 'HVRawConnector1.2.0';

  private $session;
  private $appId;
  private $thumbPrint;
  private $privateKey;
  private $sharedSecret;
  private $digest;
  private $authToken;
  private $userAuthToken;
  private $rawResponse;
  private $qpResponse;
  private $responseCode;
  private $logger = NULL;

  public function setLogger(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * @param string $appId
   *   HealthVault Application ID
   * @param string $thumbPrint
   *   Certificate thumb print
   * @param string $privateKey
   *   Private key as string or file path to load private key from
   * @param array $session
   *   Session array, in most cases $_SESSION
   */
  public function __construct($appId, $thumbPrint, $privateKey, &$session) {
    $this->session = & $session;
    $this->appId = $appId;
    $this->thumbPrint = $thumbPrint;
    $this->privateKey = $privateKey;

    if (empty($this->session['healthVault']['sharedSecret'])) {
      $this->session['healthVault']['sharedSecret'] = $this->hash(uniqid());
      $this->session['healthVault']['digest'] = $this->hmacSha1($this->session['healthVault']['sharedSecret'], $this->session['healthVault']['sharedSecret']);
    }

    $this->sharedSecret = $this->session['healthVault']['sharedSecret'];
    $this->digest = $this->session['healthVault']['digest'];
  }

  public function connect() {
    if (!$this->logger) {
      $this->logger = new NullLogger();
    }

    if (empty($this->session['healthVault']['userAuthToken']) && !empty($_GET['wctoken']) && !empty($_GET['actionqs'])) {
      $this->session['healthVault']['actionQs'] = array();
      parse_str($_GET['actionqs'], $this->session['healthVault']['actionQs']);

      if ($this->session['healthVault']['actionQs']['redirectToken'] == $this->session['healthVault']['redirectToken']) {
        // TODO verify wctoken / security check
        $this->session['healthVault']['userAuthToken'] = $_GET['wctoken'];
      }

      unset($this->session['healthVault']['actionQs']['redirectToken']);
    }

    if (!empty($this->session['healthVault']['userAuthToken'])) {
      $this->userAuthToken = $this->session['healthVault']['userAuthToken'];
    }
    else {
      throw new HVRawConnectorUserNotAuthenticatedException();
    }

    if (empty($this->session['healthVault']['authToken'])) {
      $info = qp(HVRawConnector::$commandCreateAuthenticatedSessionTokenXML, NULL, array('use_parser' => 'xml'))
        ->xpath('auth-info/app-id')->text($this->appId)->top()
        ->xpath('//content/app-id')->text($this->appId)->top()
        ->find('sig')->attr('thumbprint', $this->thumbPrint)->top()
        ->find('hmac-alg')->text($this->digest)->top();

      $content = $info->find('content')->xml();

      $xml = $info->top()->find('sig')->text($this->sign($content))->top()->innerXML();

      // throws HVRawConnectorAnonymousWcRequestException
      $this->anonymousWcRequest('CreateAuthenticatedSessionToken', '1', $xml);

      $this->session['healthVault']['authToken'] = $this->qpResponse->find('token')->text();
    }

    if (!empty($this->session['healthVault']['authToken'])) {
      $this->authToken = $this->session['healthVault']['authToken'];
    }
  }


  public function anonymousWcRequest($method, $methodVersion = '1', $info = '', $additionalHeaders = array()) {
    $header = $this->getBasicCommandQueryPath(HVRawConnector::$anonymousWcRequestXML, $method, $methodVersion, $info)
      ->find('header app-id')->text($this->appId)->top();

    $this->addAdditionalHeadersToWcRequest($header, $additionalHeaders);

    $this->doWcRequest($header);
  }


  public function authenticatedWcRequest($method, $methodVersion = '1', $info = '', $additionalHeaders = array()) {
    $header = $this->getBasicCommandQueryPath(HVRawConnector::$authenticatedWcRequestXML, $method, $methodVersion, $info)
      ->find('header hash-data')->text($this->hash(empty($info) ? '<info/>' : '<info>' . $info . '</info>'))->top()
      ->find('header auth-token')->text($this->authToken)->top()
      ->find('header user-auth-token')->text($this->userAuthToken)->top();

    $this->addAdditionalHeadersToWcRequest($header, $additionalHeaders);
    $headerRawXml = $header->find('header')->xml();

    $this->doWcRequest(
      $header->top()->find('hmac-data')->text($this->hmacSha1($headerRawXml, base64_decode($this->digest)))
    );
  }


  protected function getBasicCommandQueryPath($wcRequestXML, $method, $methodVersion, $info) {
    return qp($wcRequestXML)
      ->find('method')->text($method)->top()
      ->find('method-version')->text($methodVersion)->top()
      ->find('msg-time')->text(gmdate("Y-m-d\TH:i:s"))->top()
      ->find('version')->text(HVRawConnector::$version)->top()
      ->find('info')->append($info)->top();
  }


  private function addAdditionalHeadersToWcRequest($header, $additionalHeaders) {
    if ($this->language) {
      $header->top()->find('header language')->text($this->language);
    }
    if ($this->country) {
      $header->top()->find('header language')->text($this->country);
    }
    if (!empty($additionalHeaders)) {
      foreach ($additionalHeaders as $element => $text) {
        $header->top()->find('method-version')->after('<' . $element . '>' . $text . '</' . $element . '>');
      }
    }
    $header->top();
  }

  protected function doWcRequest($qpObject) {
    $params = array(
      'http' => array(
        'method' => 'POST',
        // remove line breaks and spaces between elements, otherwise the signature check will fail
        'content' => preg_replace('/>\s+</', '><', $qpObject->top()->xml()),
      ),
    );

    $this->logger->debug('Request: ' . $params['http']['content']);
    $ctx = stream_context_create($params);
    $this->rawResponse = @file_get_contents($this->healthVaultPlatform, FALSE, $ctx);
    if (!$this->rawResponse) {
      $this->qpResponse = NULL;
      $this->responseCode = -1;
      throw new \Exception('HealthVault Connection Failure', -1);
    }
    $this->logger->debug('Response: ' . $this->rawResponse);
    $this->qpResponse = qp($this->rawResponse, NULL, array('use_parser' => 'xml'));
    $this->responseCode = (int) $this->qpResponse->find('response status code')->text();

    if ($this->responseCode > 0) {
      $this->logger->error('Response Code: ' . $this->responseCode);
      $this->logger->error('Error Message: ' . $this->qpResponse->top()->find('error message')->text());
      switch ($this->responseCode) {
        // TODO add more error codes
        case 7: // The user authenticated session token has expired.
        case 11: // Access is denied. (Happens when the user revokes grants for the app during an active session.)
        case 65: // The authenticated session token has expired.
          // the easiest solution is to invalidate everything and let the user initialize a new connection @see connect()
          $this->invalidateSession();
          throw new HVRawConnectorAuthenticationExpiredException($this->qpResponse->top()->find('error message')->text(), $this->responseCode);
      }
      throw new HVRawConnectorWcRequestException($this->qpResponse->top()->find('error message')->text(), $this->responseCode);
    }

    $this->qpResponse->top();
  }


  protected function sign($str) {
    static $privateKey = NULL;

    if (is_null($privateKey)) {
      if (is_file($this->privateKey)) {
        if (is_readable($this->privateKey)) {
          $privateKey = @file_get_contents($this->privateKey);
        }
        else {
          throw new Exception('Unable to read private key file.');
        }
      }
      else {
        $privateKey = $this->privateKey;
      }
    }

    // TODO check if $privateKey really is a key (format)

    openssl_sign(
      // remove line breaks and spaces between elements, otherwise the signature check will fail
      preg_replace('/>\s+</', '><', $str),
      $signature,
      $privateKey,
      OPENSSL_ALGO_SHA1);

    return trim(base64_encode($signature));
  }


  protected function hash($str)
  {
    return trim(base64_encode(sha1(preg_replace('/>\s+</', '><', $str), TRUE)));
  }


  protected function hmacSha1($str, $key) {
    return trim(base64_encode(hash_hmac('sha1', preg_replace('/>\s+</', '><', $str), $key, TRUE)));
  }


  public static function getAuthenticationURL($appId, &$session, $healthVaultAuthInstance = 'https://account.healthvault-ppe.com/redirect.aspx', $redirect = '', $actionQs = array()) {
    $actionQs['redirectToken'] = $session['healthVault']['redirectToken'] = md5(uniqid());

    array_walk($actionQs, function(&$v, $k) {
      $v = $k . '=' . rawurlencode($v);
    });

    $params = array(
      'target' => 'AUTH',
      'targetqs' => '?appid=' . $appId . '&actionqs=' . rawurlencode(implode('&', $actionQs)),
    );

    if (!empty($redirect)) {
      $params['targetqs'] .= '&redirect=' . $redirect;
    }

    if (!empty($actionqs)) {
      ;
    }

    // see http://msdn.microsoft.com/library/ff803620.aspx
    $healthVaultUrl = new \Net_URL2($healthVaultAuthInstance);
    $healthVaultUrl->setQueryVariables($params);

    return $healthVaultUrl->getURL();
  }


  public function invalidateSession() {
    $this->logger->debug('invalidating session');
    unset($this->session['healthVault']);
    $this->session['healthVault']['sharedSecret'] = $this->sharedSecret;
    $this->session['healthVault']['digest'] = $this->digest;
  }


  public function getRawResponse() {
    return $this->rawResponse;
  }


  public function getQueryPathResponse() {
    return $this->qpResponse->top();
  }
}

class HVRawConnectorUserNotAuthenticatedException extends \Exception {}

class HVRawConnectorAppNotAuthenticatedException extends \Exception {}

class HVRawConnectorAuthenticationExpiredException extends \Exception {}

class HVRawConnectorWcRequestException extends \Exception {}
