<?php

namespace App\Libraries;

class Helper
{

	public static function getStringBetween($string, $start, $end)
	{
		$string = ' ' . $string;
		$ini = strpos($string, $start);
		if ($ini == 0) return '';
		$ini += strlen($start);
		$len = strpos($string, $end, $ini) - $ini;

		return substr($string, $ini, $len);
	}

	public static function array_shuffle($array)
	{
		return (shuffle($array)) ? $array : false;
	}

}
