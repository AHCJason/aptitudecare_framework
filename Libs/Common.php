<?php

function pr($var) {
	echo '<pre>';
 	print_r($var);
    echo '</pre>';
}



function camelizeString($string) {
	return lcfirst (str_replace('-', '', implode('-', array_map('ucfirst', explode('-', $string)))));			
}


function underscoreString($string) {
	return lcfirst (str_replace('-', '_', implode('-', array_map('lcfirst', explode('-', $string)))));
}

function hyphenateString($string) {
	$hs = preg_replace('/([^A-Z-])([A-Z])/', '$1-$2', $string);
	return strtolower($hs);
}

function getRandomString($length = 8) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$string = "";

	for ($p = 0; $p < $length; $p++) {
		$string .= $characters[mt_rand(8, strlen($characters))];
	}
	return $string;
}


function currentUrl() {
	return 'https://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];	
}