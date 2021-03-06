<?php

require_once(__DIR__ . '/functions.php');

class URLShortener
{
	const ALLOWED_CHARS = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ&!';
	const DB_TABLE_NAME = 'short_url';
	const DB_CREATE_TABLE = '
		CREATE TABLE DB_TABLE_NAME (
			id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			long_url VARCHAR(255) NOT NULL,
			short_url VARCHAR(6) NULL DEFAULT NULL,
			hits BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

			PRIMARY KEY (id)
		) DEFAULT CHARSET=utf8;
	';

	private static function char_encode($id, $chars = URLShortener::ALLOWED_CHARS)
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

	private static function num_as_str($num) {
		$str = '';
		
		while ($num > 0) {
			$str .= chr($num & 0xFF);
			$num = $num >> 8;
		}

		return $str;
	}

	public static function short_url_from_id($id)
	{
		return self::char_encode( (int)sprintf( '%u', crc32( self::num_as_str($id) ) ) );
	}

	public static function shorten_url_internal_($long_url)
	{
		$existing_entry = Conn::queryArray('
			SELECT id, short_url 
			FROM ' . URLShortener::DB_TABLE_NAME . '
			WHERE long_url = \'' . goodString($long_url) . '\'
		');
		$short_url = NULL;
		$id = NULL;

		if ($existing_entry)
		{
			$short_url = $existing_entry['short_url'];
			$id = $existing_entry['id'];
		}
		else
		{
			Conn::query('LOCK TABLES ' . URLShortener::DB_TABLE_NAME . ' WRITE');
			Conn::query('INSERT INTO ' . URLShortener::DB_TABLE_NAME
				. ' (long_url) VALUES'
				. ' (\'' . goodString($long_url) . '\')');
			$id = Conn::getId();
			$short_url = URLShortener::short_url_from_id($id);
			Conn::query('UPDATE ' . URLShortener::DB_TABLE_NAME . ' SET '
				. ' short_url = \'' . goodString($short_url) . '\''
				. ' WHERE id = ' . $id);
			Conn::query('UNLOCK TABLES');
		}

		return array($short_url, $id);
	}

	public static function shorten_url_get_id($long_url)
	{
		$short = self::shorten_url_internal_($long_url);

		return $short[1];
	}

	public static function shorten_url($long_url)
	{
		$short = self::shorten_url_internal_($long_url);

		return $short[0];
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
				. ' SET hits = hits + 1'
				. ' WHERE id = ' . $existing_entry['id']);
			Conn::query('UNLOCK TABLES');
		}

		return $long_url;
	}
}
