<?php

namespace naf\auth;
use naf\auth\OpenID\Fault;

class OpenID {
	
	/**
	 * Known OpenID providers.
	 * For these provs, only a username could be provided,
	 * not the complete identity URL.
	 *
	 * @var array
	 */
	private $providers = array(
		'http://www.blogger.com/openid-server.g' => array(
			'id'          => 'google',
			'description' => 'Google/Blogger',
			'format'      => 'http://{username}.blogspot.com/',
			'regexp'      => '~^http://([a-z0-9\-]+)\.blogspot\.com/$~i',
		),
	);
	
	/**
	 * @var string
	 */
	private $baseUrl, $returnUrl, $trustUrl;
	
	function getKnownProviders()
	{
		return $this->providers;
	}
	
	/**
	 * @param string $url
	 * @throws naf\auth\OpenID\Fault
	 */
	function setBaseUrl($url)
	{
		$this->baseUrl = $this->checkUrl($url);
	}
	/**
	 * @return string
	 * @throws naf\auth\OpenID\Fault
	 */
	function getTrustUrl()
	{
		if ($this->trustUrl)
		{
			return $this->trustUrl;
		}
		
		$this->checkBaseUrl();
		
		return $this->baseUrl;
	}
	/**
	 * @param string $url
	 * @throws naf\auth\OpenID\Fault
	 */
	function setTrustUrl($url)
	{
		$this->trustUrl = $this->checkUrl($url, 'Invalid trust URL');
	}
	/**
	 * @return string
	 * @throws naf\auth\OpenID\Fault
	 */
	function getReturnUrl()
	{
		if ($this->returnUrl)
		{
			return $this->returnUrl;
		}
		
		$this->checkBaseUrl();
		
		return $this->baseUrl;
	}
	/**
	 * @param string $url
	 * @throws naf\auth\OpenID\Fault
	 */
	function setReturnUrl($url)
	{
		$this->returnUrl = $this->checkUrl($url, 'Invalid return URL');
	}
	/**
	 * perform a redirect to OpenID/setup, where the openid server takes control over
	 * user agent.
	 *
	 * @param string $identity
	 * @param string $provider
	 * @throws naf\auth\OpenID\Fault
	 */
	function setup($identity, $provider = null) {
		
		if (null === $provider)
		{
			throw new Fault("Sorry. Arbitrary identities are not yet supported. You will need to specify a provider");
		}
		
		if (empty($identity) || (! is_string($identity)) || ! trim($identity))
		{
			throw new Fault("Error: empty identity!");
		}
		
		$this->checkUrl($provider, "Invalid provider URL");
		
		$provider = rtrim($provider, '/');
		
		try {
			$this->checkUrl($identity);
		} catch (Fault $e) {
			if (array_key_exists($provider, $this->providers))
			{
				$identity = str_replace('{username}', $identity, $this->providers[$provider]['format']);
			} else {
				throw new Fault($provider . " is not yet supported on the level of usernames. Please specify identity URL.");
			}
		}
		
		$params = array(
			'openid.mode' => 'checkid_setup',
			'openid.identity' => $identity,
			'openid.return_to' => $this->getReturnUrl(),
			'openid.trust_root' => $this->getTrustUrl(),
			'openid.assoc_handle' => uniqid('oida-', true),
		);
		
		header("Location: $provider?" . http_build_query($params, null, '&'));
		exit();
	}
	
	/**
	 * Check user information.
	 *
	 * @return bool
	 * @throws naf\auth\OpenID\Fault
	 */
	function check()
	{
		if ('id_res' != @$_GET['openid_mode'])
		{
			throw new Fault("Sorry. OpendID provider did not authentificate you");
		}

		if (! $signed = trim(filter_input(INPUT_GET, 'openid_signed', FILTER_SANITIZE_STRING)))
		{
			throw new Fault("No signed parameters");
		}
		
		if (! $assoc = trim(filter_input(INPUT_GET, 'openid_assoc_handle', FILTER_SANITIZE_STRING)))
		{
			throw new Fault("openid_assoc_handle not set");
		}
		
		if (! $sig = trim(filter_input(INPUT_GET, 'openid_sig', FILTER_SANITIZE_STRING)))
		{
			throw new Fault("openid_sig not set");
		}
		
		$identity = filter_input(INPUT_GET, 'openid_identity', FILTER_VALIDATE_URL);
		
		$provider = $this->getProvider($identity);
		
		$providerInfo = parse_url($provider);
		
		$params = array(
			'openid.mode' => 'check_authentication',
			'openid.signed' => $signed,
			'openid.assoc_handle' => $assoc,
			'openid.sig' => $sig,
			'openid.return_to' => filter_input(INPUT_GET, 'openid_return_to', FILTER_VALIDATE_URL),
			'openid.identity' => $identity,
		);
		
		$postData = http_build_query($params);
		
		if (! $fp = fsockopen($providerInfo['host'], 80, $errrno, $errstr, 5))
		{
			throw new Fault("Could not connect to openid server. Network problems?");
		}

		$target = $providerInfo['path'] . (@$providerInfo['query'] ? "?".$providerInfo['query'] : "");
		$header = "POST {$target} HTTP/1.0\r\n" .
			"Host: {$providerInfo['host']}\r\n" . 
			"Content-type: application/x-www-form-urlencoded\r\n" .
			"Content-length: " . strlen($postData) .
			"Connection: Close\r\n";
		
		fwrite($fp, $header . "\r\n" . $postData);
		list($responseHeaders, $responseBody) = $this->getResponse($fp);
		
		return ('is_valid:true' == trim($responseBody)) ? 
			array('server' => $provider, 'identity' => $identity) : 
			false;
	}
	
	function getProviderById($id)
	{
		foreach ($this->providers as $server => $spec)
		{
			if ($id == $spec['id']) {
				return $server;
			}
		}
	}
	
	function username($identity)
	{
		foreach ($this->providers as $server => $spec)
		{
			if (preg_match($spec['regexp'], $identity, $matches)) {
				return $matches[1];
			}
		}
		
		return $identity;
	}
	
	/**
	 * Get OpenID service provider, being given an identity
	 *
	 * @param string $identity
	 * @return string
	 * @throws naf\auth\OpenID\Fault
	 */
	function getProvider($identity)
	{
		$this->checkUrl($identity);
		
		foreach ($this->providers as $server => $spec)
		{
			if (preg_match($spec['regexp'], $identity)) {
				return $server;
			}
		}
		
		$info = parse_url($identity);
		
		if (! $fp = fsockopen($info['host'], 80, $errrno, $errstr, 5))
		{
			throw new Fault("Could not connect to openid identity. Network problems?");
		}
		
		$pathAndQuery = $info['path'];
		if (! empty($info['query']))
		{
			$pathAndQuery .= "?" . $info['query'];
		}
		
		$header = "GET {$pathAndQuery} HTTP/1.0\r\n" .
			"Host: {$info['host']}\r\n" . 
			"Connection: Close\r\n";
		
		fwrite($fp, $header . "\r\n");
		
		$re = '~\<link +rel=["\']openid.server["\'] +href=["\']([^"\']+)["\'] ?/\>~i';
		list($responseHeaders, $htmlLinkElement) = $this->getResponse($fp, $re);
		
		$server = '';
		
		if ($htmlLinkElement)
		{
			preg_match($htmlLinkElement, $re, $matches);
			$server = filter_var($matches[1], FILTER_VALIDATE_URL);
		}
		
		if (! $server)
		{
			throw new Fault("Unable to resolve OpenID server for this identity");
		}
		
		return $server;
	}
	
	/*
	 * -------------- Below go private methods --------------
	 */
	
	private function checkUrl($url, $errorMessage = "Invalid URL")
	{
		$url = filter_var($url, FILTER_VALIDATE_URL);
		if (! $url) {
			throw new Fault($errorMessage);
		}
		
		return $url;
	}
	
	private function checkBaseUrl()
	{
		if (empty($this->baseUrl))
		{
			throw new Fault("Base URL not set");
		}
	}
	
	private function getResponse($fp, $expectLinePattern = null)
	{
		$responseHeaders = '';
		$inHeaders = true;
		$responseBody = '';
		while (! feof($fp))
		{
			$str = fgets($fp, 128);
			if ($inHeaders && ("\r\n" == $str))
			{
				$inHeaders = false;
			}
			elseif ($inHeaders)
			{
				$responseHeaders .= $str;
			}
			else
			{
				if ($expectLinePattern)
				{
					$str = trim($str);
					if (preg_match($expectLinePattern, $str))
					{
						$responseBody = $str;
						break;
					}
				} else {
					$responseBody .= $str;
				}
			}
		}
		fclose($fp);
		
		if (preg_match('~HTTP/1\.[01]\s(\d+)\s(.+)~i', $responseHeaders, $matches))
		{
			$statusCode = $matches[1];
			$statusString = $matches[2];
		}
		else
		{
			$statusCode = '404';
			$statusString = 'Not Found';
		}
		
		if (200 != $statusCode)
		{
			throw new Fault('HTTP request failed with status ' . $statusCode);
		}
		
		return array($responseHeaders, $responseBody);
	}
	
}