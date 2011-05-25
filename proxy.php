<?php
/*--------------------------------------------------------------/
| PROXY.PHP														                         |
| Created By: Eric-Sebastien Lachance							             |
| Contact: eslachance@gmail.com									               |
| Description: This proxy does a POST or GET request from any	 |
| 		page on the authorized domain to the defined URL		      |
/--------------------------------------------------------------*/

// Destination URL: Where this proxy leads to.
$destinationURL = 'http://www.otherdomain.com/backend.php';

// The only domain from which requests are authorized.
$RequestDomain = 'mydomain.com';

// That's it for configuration!

preg_match('@^(?:http://)?([^/]+)@i', $_SERVER['HTTP_REFERER'], $matches);
$host = $matches[1];
preg_match('/[^.]+\.[^.]+$/', $host, $matches);
$domainName = "{$matches[0]}";

if($domainName == $RequestDomain) {

	$method = $_SERVER['REQUEST_METHOD'];
	$response = proxy_request($destinationURL, ($method == "GET" ? $_GET : $_POST), $method);
	$headerArray = explode("\r\n", $response[header]);

	foreach($headerArray as $headerLine) {
	 header($headerLine);
	}

	echo $response[content];
 
 } else {

	echo "HTTP Referer is not recognized. Cancelling all actions";

}

function proxy_request($url, $data, $method) {
// Based on post_request from http://www.jonasjohn.de/snippets/php/post-request.htm
// Heavly modified since then, though.

	// Convert the data array into URL Parameters like a=b&foo=bar etc.
	$data = http_build_query($data);
 
	// parse the given URL
	$url = parse_url($url);
 
	if ($url['scheme'] != 'http') { 
		die('Error: Only HTTP request are supported !');
	}
 
	// extract host and path:
	$host = $url['host'];
	$path = $url['path'];
	
	// open a socket connection on port 80 - timeout: 30 sec
	$fp = fsockopen($host, 80, $errno, $errstr, 30);
 
	if ($fp){
		// send the request headers:
		if($method == "POST") {
			fputs($fp, "POST $path HTTP/1.1\r\n");
		} else {
			fputs($fp, "GET $path?$data HTTP/1.1\r\n");
		}
		fputs($fp, "Host: $host\r\n");
		fputs($fp, "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n"); 
		if($method == "POST")
		
		$myCount = 0;
   		$requestHeaders = apache_request_headers();
		while ((list($header, $value) = each($requestHeaders))) {
			$logtext = "$header with value $value";
			if($header !== "Connection" && $header !== "Host")
				fputs($fp, "$header: $value\r\n");
			$myCount = $myCount + 1;
		}
		fputs($fp, "Connection: close\r\n\r\n");
		fputs($fp, $data);
 
		$result = ''; 
		while(!feof($fp)) {
			// receive the results of the request
			$result .= fgets($fp, 128);
		}
	}
	else { 
		return array(
			'status' => 'err', 
			'error' => "$errstr ($errno)"
		);
	}
 
	// close the socket connection:
	fclose($fp);
 
	// split the result header from the content
	$result = explode("\r\n\r\n", $result, 2);
 
	$header = isset($result[0]) ? $result[0] : '';
	$content = isset($result[1]) ? $result[1] : '';
 
	// return as structured array:
	return array(
		'status' => 'ok',
		'header' => $header,
		'content' => $content
	);
}

?>
