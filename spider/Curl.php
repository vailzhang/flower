<?php
namespace Spider\Tool ;
class Curl {
	protected $userAgent = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:12.0) Gecko/20100101 Firefox/12.0';
	protected $cookieFile = NULL;
	protected $referer = '';
	protected $timeout = 60;
	protected $url = '';
	protected $method = 'GET';
	protected $isUtf8 = true;
	public function __construct($url = NULL) {
		if ($url) {
			$this->setUrl ( $url );
		}
	}
	public function setUrl($url) {
		$this->url = $url;
	}
	public function setMethod($method) {
		switch ($method) {
			case 'GET' :
			case 'POST' :
				$this->method = $method;
				break;
			default :
				throw new \Exception ( 'inValid HTTP Method "' . $method . '"' );
		}
	}
	public function setReferer($referer) {
		$this->referer = $referer;
	}
	public function setTimeout($timeout) {
		$this->timeout = $timeout;
	}
	public function setCookieFile($file) {
		$this->cookieFile = $file;
	}
	public function setIsUtf8($is) {
		$this->isUtf8 = $is;
	}
	public function fetch(array $data = array()) {
		// 启动CURL
		$hCURL = curl_init ();
		// 设定HTTP链接超时时间
		curl_setopt ( $hCURL, CURLOPT_TIMEOUT, $this->timeout );
		// 将HTTP返回的内容存到内存中，而不是直接输出
		curl_setopt ( $hCURL, CURLOPT_RETURNTRANSFER, TRUE );
		// 将HTTP访问的USERAGENT信息
		curl_setopt ( $hCURL, CURLOPT_USERAGENT, $this->userAgent );
		// 在HTTP请求头中"Referer: "的内容
		curl_setopt ( $hCURL, CURLOPT_REFERER, $this->referer );
		// 根据Location，自动重定向访问
		curl_setopt ( $hCURL, CURLOPT_FOLLOWLOCATION, TRUE );
		// 根据Location:重定向时，自动设置header中的Referer:信息
		curl_setopt ( $hCURL, CURLOPT_AUTOREFERER, TRUE );
		// 设置GZIP压缩返回数据
		curl_setopt ( $hCURL, CURLOPT_ENCODING, "gzip,deflate" );

		$url = $this->url;
		if ($this->method == 'POST') {
			curl_setopt ( $hCURL, CURLOPT_POST, 1 );
			if ($data)
				@ curl_setopt ( $hCURL, CURLOPT_POSTFIELDS, $data );
		} else {
			if ($data) {
				if ($pos = strpos($this->url,'?')) {
					$urlMain = substr ( $this->url, 0, $pos );
					$urlQuery = substr ( $this->url, $pos + 1 );
					parse_str ( $urlQuery, $queryInfo );
					$queryInfo = array_merge ( $queryInfo, $data );
					$url = $urlMain . '?' . http_build_query ( $queryInfo );
				} else {
					$url = $this->url . '?' . http_build_query ( $queryInfo );
				}
			}
		}
		curl_setopt ( $hCURL, CURLOPT_URL, $url );

		if ($this->cookieFile) {
			// 请求链接时，将发送文件中的cookies数据
			curl_setopt ( $hCURL, CURLOPT_COOKIEFILE, $this->cookieFile );
			// 结束链接时，将保存cookies数据到该文件
			curl_setopt ( $hCURL, CURLOPT_COOKIEJAR, $this->cookieFile );
		}

		// CURL执行访问，并返回服务器响应
		$sContent = curl_exec ( $hCURL );
		if ($sContent === FALSE) {
			$error = curl_error ( $hCURL );
			curl_close ( $hCURL );
			throw new \Exception ( $error . ' Url : ' . $url );
		} else {
			curl_close ( $hCURL );
			if (! $this->isUtf8) {
				$sContent = iconv ( 'GB2312', 'UTF-8//IGNORE', $sContent );
			}
			return $sContent;
		}
	}
}