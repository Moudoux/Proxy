<?php

	/*
		TODO: Update encryption method as mcrypt is getting deprecated
	*/

	session_start();
	
	if ($_SESSION['key'] == '') {
		$_SESSION['key'] = pack('H*', bin2hex(openssl_random_pseudo_bytes(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC))));
	}
	
	$proxy_url = 'https://'.$_SERVER['HTTP_HOST'];
	
	if (strpos($_SERVER['REQUEST_URI'],'?') !== false) {
		$value = explode('?',$_SERVER['REQUEST_URI']);
		$proxy_url .= $value[0];
	} else {
		$proxy_url .= $_SERVER['REQUEST_URI'];
	}
	
	$key = $_SESSION['key'];
	
	// Default to base64 or AES-256 ?
	$use_b64 = true;
	
	if (isset($_GET['b64'])) {
		$use_b64 = true;
	} else {
		// Detect Base64
		if (base64_encode(base64_decode($_GET['u'])) === $_GET['u']){
			$use_b64 = true;
		}
	}
	
	function filetime_callback($a, $b) {
		if (filemtime($a) === filemtime($b)) return 0;
		return filemtime($a) < filemtime($b) ? -1 : 1; 
	}
	
	if (strpos($_SERVER['REQUEST_URI'],'?cache') !== false) {
		echo '<h2>Image cache database</h2>';
		$array = glob('img-cache/*',GLOB_BRACE);
		usort($array, "filetime_callback");
		$array = array_reverse($array);
		$count = 0;
		foreach ($array as $file) {
			$count += 1;
			$name = $file;
			$name = str_replace('img-cache/','',$name);
			$link = $proxy_url.'?c='.$name;
			echo '<a href="'.$link.'" target="_blank"><img style="max-width: 1280px;" src="'.$link.'" /></a><br><br>';
		}
		die();
	}
	
	if (isset($_GET['c'])) {
		header("Content-type: image");
		$file = 'img-cache/'.$_GET['c'];
		if (file_exists($file)) {
			die(file_get_contents($file));
		}
		die('Invalid file');
	}
	
	function getCache($file) {
		if (endsWith($file,'.png') || endsWith($file,'.jpg') || endsWith($file,'.jpeg') || endsWith($file,'.gif')) {
			$file = 'img-cache/'.md5($file);
		} else {
			$file = 'cache/'.md5($file);
		}
		if (file_exists($file)) {
			return file_get_contents($file);
		}
		return '';
	}
	
	function setCache($file,$content) {
		if (endsWith($file,'.png') || endsWith($file,'.jpg') || endsWith($file,'.jpeg') || endsWith($file,'.gif')) {
			$file = 'img-cache/'.md5($file);
		} else {
			$file = 'cache/'.md5($file);
		}
		if (file_exists($file)) {
			unlink($file);
		}
		file_put_contents($file,$content);
	}
	
	function encrypt($toEncrypt) {
		global $use_b64;
		if ($use_b64) {
			return base64_encode($toEncrypt);
		}
		global $key;
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		return base64_encode($iv . mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $toEncrypt, MCRYPT_MODE_CBC, $iv));
	}

	function decrypt($toDecrypt){
		global $use_b64;
		if ($use_b64) {
			return base64_decode($toDecrypt);
		}
		global $key;
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
		$toDecrypt = base64_decode($toDecrypt);
		return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, substr($toDecrypt, $iv_size), MCRYPT_MODE_CBC, substr($toDecrypt, 0, $iv_size)));
	}
	
	function makeURL($uri) {
		global $proxy_url;
		$uri = $proxy_url.'?u='.urlencode(encrypt($uri));
		if (isset($_GET['b64'])) {
			$uri .= '&b64=true';
		}
		if (isset($_GET['np'])) {
			$uri .= '&np=true';
		}
		return $uri;
	}

	if (isset($_GET['e'])) {
		$uri = $proxy_url.'?u='.urlencode(encrypt($_GET['e']));
		if (isset($_GET['b64'])) {
			$uri .= '&b64=true';
		}
		if (isset($_GET['np'])) {
			$uri .= '&np=true';
		}
		echo '<a href="'.$uri.'">'.$uri.'</a>';
		die();
	}
	
	if (!isset($_GET['u'])) {
		die('Invalid URI');
	}

	$url = decrypt($_GET['u']);
	$compare = $url;
	
	if (!(strpos($url,'http') !== false)) {
		$compare = 'https://'.$compare;
	}
	
	if (filter_var($compare, FILTER_VALIDATE_URL) === FALSE) {
		die('Invalid session or url > '.$url);
	}
	
	/*
		Main proxy service
	*/

	// Bot user agent
	$user_agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.7) Gecko/20070914 Firefox/2.0.0.7';
	
	// Proxy settings, SOCKS5 only
	$proxy = '<username>:<password>@<host>:<port>';
	
	// Use proxy to fetch content ?
	$use_proxy = true;
	
	if (isset($_GET['np'])) {
		$use_proxy = false;
	}
	
	function tor_requests($url) {
		$proxy = "127.0.0.1";
		$port = "9050";
		
		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL, $url);
		curl_setopt ($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_PROXYTYPE, 7 );
		curl_setopt ($ch, CURLOPT_PROXY, $proxy.':'.$port );
		ob_start();

		curl_exec ($ch);
		curl_close ($ch);

		$result = ob_get_contents();
		ob_end_clean();
		return $result;
	}
	
	function get_page($url){
		global $use_proxy;
		if ($use_proxy == false) {
			if (strpos($url,'.onion') !== false) {
				die('Unsupported url');
			}
			return file_get_contents($url);
		}
		
		global $proxy;

		$ch = curl_init();
		
		if (strpos($url,'.onion') !== false) {
			return tor_requests($url);
		} else {
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
			curl_setopt($ch, CURLOPT_PROXY, $proxy);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 900);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
			curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
			curl_setopt($ch, CURLOPT_COOKIEJAR, "cookies.txt");
			curl_setopt($ch, CURLOPT_COOKIEFILE, "cookies.txt");
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_ENCODING, "gzip"); 
		}
		
		$data = curl_exec($ch);
		$curl_error = curl_error($ch);
		curl_close($ch);
		
		if ($data == '') {
			return get_page($url);
		}
		
		return $data;
	}
	
	function startsWith($haystack, $needle) {
		$length = strlen($needle);
		return (substr($haystack, 0, $length) === $needle);
	}
	
	function endsWith($haystack, $needle) {
		$length = strlen($needle);
		if ($length == 0) {
			return true;
		}
		return (substr($haystack, -$length) === $needle);
	}
	
	/*
		Get the current domain name (example.com || www.example.com)
	*/
	function getBaseURL($url) {
		$url = str_replace('https://','',$url);
		$url = str_replace('http://','',$url);
		$url = str_replace('//','',$url);
		if ((strpos($url,'/') !== false) == false) {
			return $url;
		}
		$url = explode('/',$url);
		return $url[0];
	}
	
	/*
		Get current domain name with current page (example.com/site/test.html || www.example.com/site/test.html)
	*/
	function getCurrentURL($url) {
		$ori = $url;
		$url = str_replace('https://','',$url);
		$url = str_replace('http://','',$url);
		$url = str_replace('//','',$url);
		if ((strpos($url,'/') !== false) == false) {
			return $url;
		}
		$total = substr_count($url,'/');
		$url = explode('/',$url);
		$final = $url[$total];
		$ori = str_replace('/'.$final,'/',$ori);
		return $ori;
	}
	
	/*
		Rewrites a url that does not contain a domain name to the current domain
	*/
	function makeValid($url,$_base_url,$_root) {
		$url = str_replace('https://','',$url);
		$url = str_replace('http://','',$url);
		$url = str_replace('//','',$url);
		$match = $url;
		if (strpos($match,'/') !== false) {
			$match = explode('/',$match);
			$match = $match[0];
		}
		$valid = false;
		if (preg_match('#([0-9a-z-]+\.)?[0-9a-z-]+\.[a-z]{2,7}#',$match)) {
			$valid = true;
		}
		if (strpos($match,'.png') !== false || strpos($match,'.jpg') !== false || strpos($match,'.jpeg') !== false) {
			$valid = false;
		}
		if ($valid == false) {
			if (startsWith($url,'/')) {
				$url = $_root.$url;
			} else if (endsWith($_base_url,'/')) {
				$url = $_base_url.$url;
			} else {
				$url = $_root.'/'.$url;
			}
		}
		return $url;
	}
	
	/*
		Get page title
	*/
	function page_title($fp) {
		$res = preg_match("/<title>(.*)<\/title>/siU", $fp, $title_matches);
		if (!$res) 
			return null; 
		$title = preg_replace('/\s+/', ' ', $title_matches[1]);
		$title = trim($title);
		return $title;
	}
	
	/*
		Minify a string
	*/
	function minimize($content) {
		$out = '';
		foreach(preg_split("/<(.*?)/", $content) as $line){
			if ($line == '') {
				continue;
			}
			$line = '<'.$line;
			$out .= trim($line);
		}
		return $out;
	}
	
	/*
		Get the file extension of a url
	*/
	function fileExtension($url) {
		$total = substr_count($url,'.');
		$url = explode('.',$url);
		$url = $url[$total];
		return $url;
	}

	// Domain name
	$_base_url = getCurrentURL($url);
	
	// Domain name with current page 
	$_root_url = getBaseURL($url);
	
	// Custom headers and files
	if (endsWith($url,'.css')) {
		header("Content-type: text/css");
		$cache = getCache($url);
		if ($cache != '') {
			die($cache);
		}
		$page_content = get_page($url);
		$_ori = $page_content;
		$explode = preg_match_all('#url\(("|\')(.*?)("|\')\)#',$page_content,$matches,PREG_PATTERN_ORDER);
		foreach ($matches[0] as $line) {
			$__ori = $line;
			$_delim = '"';
			if (strpos($__ori,'url(\'') !== false) {
				$_delim = '\'';
			}
			$line = str_replace('url('.$_delim,'',$line);
			$line = substr($line, 0, -2);
			if (strpos($line,'data:') !== false) {
				continue;
			}
			$line = makeURL(makeValid($line,$_base_url,$_root_url));
			$line = 'url('.$_delim.$line.$_delim.')';
			$_ori = str_replace($__ori,$line,$_ori);
		}
		setCache($url,minimize($_ori));
		die(minimize($_ori));
	} else if (endsWith($url,'.png') || endsWith($url,'.jpg') || endsWith($url,'.jpeg') || endsWith($url,'.gif')) {
		header("Content-type: image");
		$cache = getCache($url);
		if ($cache != '') {
			die($cache);
		}
		$page_content = get_page($url);
		setCache($url,$page_content);
		die($page_content);
	} else if (endsWith($url,'.ttf') || endsWith($url,'.svg') || endsWith($url,'.woff') || endsWith($url,'.eot')) {
		$page_content = get_page($url);
		if (endsWith($url,'.ttf')) {
			header("Content-type: application/octet-stream");
		} else if (endsWith($url,'.svg')) {
			header("Content-type: image/svg+xml");
		} else if (endsWith($url,'.woff')) {
			header("Content-type: application/font-woff");
		} else if (endsWith($url,'.eot')) {
			header("Content-type: application/vnd.ms-fontobject");
		}
		die($page_content);
	}
	
	$cache = getCache($url);
	if ($cache != '') {
		die($cache);
	}
	
	if ($page_content == '') {
		$page_content = get_page($url);
	}
	
	// Remove all JavaScript
	$page_content = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $page_content);
	
	// Remove page title and icon
	$favicon = '<link href="data:image/x-icon;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQEAYAAABPYyMiAAAABmJLR0T///////8JWPfcAAAACXBIWXMAAABIAAAASABGyWs+AAAAF0lEQVRIx2NgGAWjYBSMglEwCkbBSAcACBAAAeaR9cIAAAAASUVORK5CYII=" rel="icon" type="image/x-icon" />';
	$page_content = str_replace('<title>'.page_title($page_content).'</title>','<title>Proxy</title>'.$favicon,$page_content);
	
	$_content = '';
	
	// Remake the page (Rewrite URLs, etc)
	foreach(preg_split("/<(.*?)/", $page_content) as $line){
		if ($line == '') {
			continue;
		}
		$line = '<'.$line;
		if (preg_match('#href="(.*?)"#is',$line) || preg_match('#src="(.*?)"#is',$line) || preg_match('#data-src="(.*?)"#is',$line)) {
			
			$_delimiter = 'href';
			
			if (strpos(strtolower($line),'src="') !== false) {
				$_delimiter = 'src';
			}
			
			$_ori = $line;
			$_href = $line;
			$ori = $line;
			$line = explode($_delimiter.'="',$ori);
			$line = $line[1];
			$ori = explode('"',$line);
			$line = $ori[0];
			
			$line = str_replace('https://','',$line);
			$line = str_replace('http://','',$line);
			
			$_href = str_replace('https://','',$_href);
			$_href = str_replace('http://','',$_href);
			
			$line = str_replace('//','',$line);
			$_href = str_replace('//','',$_href);
			
			if ($line == '#') {
				$line = getBaseURL($__url);
			}

			$new = makeURL(makeValid($line,$_base_url,$_root_url));

			$_href = str_replace($_delimiter.'="'.$line.'"',$_delimiter.'="'.$new.'"',$_href);
			$_content .= $_href;
		} else {
			$_content .= $line;
		}
	}
	
	$page_content = '<!-- Page served by Proxy -->'.$_content;
	
	// Display the content
	setCache($url,minimize($page_content));
	echo minimize($page_content);
	die();
	
?>