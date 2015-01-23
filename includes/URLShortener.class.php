<?php

require_once(__DIR__ . '/functions.php');

class URLShortener
{
	const ALLOWED_CHARS = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	const DB_TABLE_NAME = 'short_url';

	public static function short_url_from_id($id, $chars = URLShortener::ALLOWED_CHARS)
	{
		$charslen = strlen($chars);
		$out = '';
		while ($id > $charslen - 1)
		{
			$out = $chars[$id % $charslen] . $out;
			$id = (int)($id / $charslen);
		}

		return $chars[$id] . $out;
	}

	public static function id_from_short_url($string, $chars = URLShortener::ALLOWED_CHARS)
	{
		$charslen = strlen($chars);
		$stringlen = strlen($string) - 1;
		$string = str_split($string);
		$out = strpos($chars, array_pop($string));
		foreach($string as $i => $char)
		{
			$out += strpos($chars, $char) * pow($charslen, $stringlen - $i);
		}
		return $out;
	}

	public static function shorten_url($long_url)
	{
		$existing_entry = Conn::queryArray('
			SELECT id, short_url 
			FROM ' . URLShortener::DB_TABLE_NAME . '
			WHERE long_url = \'' . goodString($long_url) . '\'
		');
		$short_url = NULL;

		if ($existing_entry)
		{
			$short_url = $existing_entry['short_url'];
		}
		else
		{
			Conn::query('LOCK TABLES ' . URLShortener::DB_TABLE_NAME . ' WRITE');
			Conn::query('INSERT INTO ' . URLShortener::DB_TABLE_NAME
				. ' (long_url) VALUES'
				. ' (\'' . goodString($long_url) . '\')');
			$id = Conn::getId();
			$short_url = $this->short_url_from_id($id);
			Conn::query('UPDATE ' . URLShortener::DB_TABLE_NAME . ' SET '
				. ' short_url = \'' . goodString($short_url) . '\''
				. ' WHERE id = ' . $id);
			Conn::query('UNLOCK TABLES');
		}

		return $short_url;
	}

	public static function lengthen_url($short_url)
	{
		$long_url = NULL;

		$existing_entry = Conn::queryArray('
			SELECT long_url, id
			FROM ' . URLShortener::DB_TABLE_NAME . '
			WHERE short_url = \'' . goodString($short_url) . '\'
		');

		if ($existing_entry)
		{
			$long_url = $existing_entry['long_url'];

			Conn::query('LOCK TABLES ' . URLShortener::DB_TABLE_NAME . ' WRITE');
			Conn::query('UPDATE ' . URLShortener::DB_TABLE_NAME
				. ' SET ' . ' hits = hits + 1'
				. ' WHERE id = ' . $existing_entry['id']);
			Conn::query('UNLOCK TABLES');
		}

		return $long_url;
	}
}

?>