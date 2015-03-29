<?php

namespace Ens\JobeetBundle\Utils;

class Jobeet
{
	/**
	 * transforme une chaine pour qu'elle soit compatible en URL
	 * @param string $text
	 * @return string|mixed
	 */
	static public function slugify($text)
	{
		// replace les caracteres autre que lettres et digits par -
		$text = preg_replace('#[^\\pL\d]+#u', '-', $text);
		
		// supprime le caractere en question
		$text = trim($text, '-');
		
		// transliterate
		if (function_exists('iconv'))
		{
			$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
		}
		
		// lowercase
		$text = strtolower($text);
		
		// remove unwanted characters
		$text = preg_replace('#[^-\w]+#', '', $text);
		
		if (empty($text))
		{
			return 'n-a';
		}
		else
		{
			return $text;
		}
	}
}