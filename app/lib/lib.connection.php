<?php
	if(!defined('APP')) die('...');

	$conn = ADONewConnection('mysqli');
	if(ST_ONLINE == 0)
	{
		//bu kısma, yereldeki sitenin veritabanı bilgileri girilecektir
		if($conn->PConnect('localhost', 'root', '123', 'zeki_rss') == false)
		{
			die('<html><head><title>DB Error</title><body><center><img style="max-width:100%" src="'.SITELINK.'assets/img/static/dberror.png"></center></body></html>');
		}
	}

	if(ST_ONLINE == 1)
	{
		//bu kısma, yayındaki sitenin veritabanı bilgileri girilecektir
		if($conn->PConnect('localhost', 'kh_db', 'qMrM&.P^BOmh', 'kh_db') == false)
		{
			die('<html><head><title>DB Error</title><body><center><img style="max-width:100%" src="'.SITELINK.'assets/img/static/dberror.png"></center></body></html>');
		}
	}
	$conn->SetFetchMode(ADODB_FETCH_ASSOC);
	$conn->Execute('SET NAMES "utf8"');
	$conn->Execute('SET CHARACTER SET utf8_turkish_ci');
	$conn->Execute('SET COLLATION_CONNECTION = "utf8_turkish_ci"');
	$conn->debug = false;

	if(ST_ONLINE == 0)
	{
		$conn->memCache			= true;
		$conn->memCacheHost		= 'localhost'; /// $db->memCacheHost = $ip1; will work too
		$conn->memCachePort		= 11211; /// this is default memCache port
		$conn->memCacheCompress	= false; /// Use 'true' to store the item compressed (uses zlib)

	}
	if(ST_ONLINE == 1)
	{
		$conn->memCache			= true;
		$conn->memCacheHost		= 'localhost'; /// $db->memCacheHost = $ip1; will work too
		$conn->memCachePort		= 11211; /// this is default memCache port
		$conn->memCacheCompress	= false; /// Use 'true' to store the item compressed (uses zlib)
	}
