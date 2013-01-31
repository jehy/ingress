<?
class IIBbase {

	# Возвращает в "last" список всех *подошедших* ключей за последние 5 минут #
	public static function itemsLastGet() {
		$res = self::base_get_contents(array('last' => 1));
		if (!isset($res['last']))
			{ self::error('bad last', $res); }
		return $res['last'];
	}

	# Возвращает в "old" список всех ключевых слов базы. Нужен для получения текущего кэша перед запуском скрипта. #
	public static function itemsOldGet() {
		$res = self::base_get_contents(array('old' => 1));
		if (!isset($res['old']))
			{ self::error('bad old', $res); }
		return $res['old'];
	}

	# Добавить слово в базу. [$good = 1] - слово подошло. #	
	public static function codeSend($code,$good=0) {
		$res = self::base_get_contents(array(
			'add'		=> $code,
			'good'	=> (!empty($good) ? 1 : ''),			
		));
		if (!isset($res['add']))
			{ self::error('bad send', $res); }
		return $res['add'];
	}

	private static function error($txt,$dump=array()) {
		die('Shit happens: '.$txt.' <hr/><pre>'.print_r($dump,1));
	}
	
	private static function base_get_contents($params=array()) {
		$url = 'http://iib.evilplace.ru/words.php';
		$time = time();
		$params = array_merge($params, array(
			't' => $time,
			'h' => 'yemu2ukth8p',  											# <<<<<<--------------- public key
			'k' => md5($time.'z9=^_^=a0'.'ay3j31ovi1'),	# <<<<<<--------------- sec2 key
		));
		
		$timeout = 10;
		@ini_set('default_socket_timeout', $timeout); 
		# @ini_set('max_execution_time', $timeout*2);
		$opts = array('http' => array(
			'method'  => 'POST',
			'header'  => array(
				'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
				'Accept-Encoding' => 'gzip, deflate',
				'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
				'Connection' => 'Close',
				'Content-Type' => 'application/x-www-form-urlencoded',
				'Host' => '',
				'User-Agent' => 'Mozilla/5.0 (Windows NT 6.0; rv:17.0) Gecko/20100101 Firefox/17.0',
			),
			'content' => http_build_query($params),
			'timeout' => $timeout,
			'ignore_errors' => true,
		) );
		$urlParsed = parse_url($url);
		$opts['http']['header']['Host'] = $urlParsed['host'];
		if (!empty($params)) { $opts['http']['header']['Content-Length'] = mb_strlen($opts['http']['content']); }

		$header = '';
		foreach ($opts['http']['header'] as $k=>$v)
			{ $header .= $k.': '.$v."\r\n"; }
		$opts['http']['header'] = $header;

		$page = @file_get_contents($url, false, stream_context_create($opts));
		$res = @json_decode($page, true);
		if (empty($page) || empty($res) || !empty($res['error']))
			{ self::error('bad query',$page); }
		return $res;
	}
}
?>