<?
function set_log($name)
{
  global $LOG_FILE;
  $LOG_FILE = $name . '_' . time() . '.txt';
}

function add_log($text, $output = 0)
{
  global $LOG_FILE;
  $text .= "\n";
  if ($output)
    echo '<br>' . $text;
  if ($LOG_FILE)
  {
    $fname = 'cache/' . $LOG_FILE;
    file_put_contents($fname, $text, FILE_APPEND);
  }
}

function send_passcode($cookie, $token, $code, $force = false)
{
  $query2 = '{"passcode":"' . $code . '","method":"dashboard.redeemReward"}';
  $len = strlen($query2);

  $query = 'POST /rpc/dashboard.redeemReward HTTP/1.1
Host: www.ingress.com
Connection: close
Content-Length: ' . $len . '
Origin: http://www.ingress.com
X-Requested-With: XMLHttpRequest
X-CSRFToken: ' . $token . '
User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11
Content-Type: application/json; charset=UTF-8
Accept: application/json, text/javascript, */*; q=0.01
Referer: http://www.ingress.com/intel
Accept-Language: en-US,en;q=0.8,ru;q=0.6
Accept-Charset: windows-1251,utf-8;q=0.7,*;q=0.3
Cookie: ' . $cookie . '

' . $query2;
  $fname = 'cache/code_' . ($code) . '.txt';
  $fname_force = 'cache/code_force_' . ($code) . '.txt';

  if($force)# || file_exists($fname))
    $fname=$fname_force;

  if (!file_exists($fname))
  {
    $attempt = 0;
    $max_attempts = 5;
    while ($attempt < $max_attempts)
    {
      $json = (http($query));

      #in case of fail
      if (is_tmp_error($json))
      {
        $efname = 'cache/err_send_' . time() . '_' . $code . '.txt';
        file_put_contents($efname, $json);
        add_log('Failed to make send password request (503), repeating...', 1);
        sleep(2 + rand(1, 3));
        $attempt++;
      }
      else
      {
        file_put_contents($fname, $json);
        return $json;
      }
    }
    add_log('Max attempts reached, breaking', 1);
    return false;
  }
  else
    #$json=file_get_contents($fname);
    return 'Already sent';
}


function is_tmp_error($text)
{
  if (strpos($text, 'download error trying to access') !== FALSE)
  {
    add_log('error: download error trying to access', 1);
    return true;
  }
  if (strpos($text, 'error code 503') !== FALSE)
  {
    add_log('error: error code 503', 1);
    return true;
  }
  if (strpos($text, 'user is not authenticated or is not a player') !== FALSE)
  {
    add_log('error: user is not authenticated or is not a player', 1);
    return true;
  }

  return false;
}

function send_msg($cookie, $token, $text)
{

  $query2 = '{"message":"' . $text . '","latE6":55755704,"lngE6":37548119,"factionOnly":true,"method":"dashboard.sendPlext"}';

  $useragent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11';

  $referer = 'http://www.ingress.com/intel';

  $path = '/rpc/dashboard.sendPlext';

  $host = 'www.ingress.com';

  $query = 'POST ' . $path . ' HTTP/1.1' . "\r\n" .
    'Host: ' . $host . "\r\n" .
    'User-Agent: ' . $useragent . "\r\n";

  if ($cookie != '') $query .=
    'Cookie: ' . $cookie . "\r\n";

  if ($referer != '') $query .=
    'Referer: ' . $referer . "\r\n";

  $query .= 'Connection: close' . "\r\n" .
    'Origin: http://www.ingress.com' . "\r\n" .
    'X-Requested-With: XMLHttpRequest' . "\r\n" .
    'X-Csrftoken: ' . $token . "\r\n" .
    'Content-Type: application/json; charset=UTF-8' . "\r\n" .
    'Accept: application/json, text/javascript, */*; q=0.01' . "\r\n" .
    'Accept-Language: en-US,en;q=0.8,ru;q=0.6' . "\r\n" .
    'Accept-Charset: windows-1251,utf-8;q=0.7,*;q=0.3' . "\r\n";

#search global area

  $query .= 'Content-Length: ' . strlen($query2) . "\r\n\r\n";
  $query .= $query2;


  $fname = 'cache/sendmsg_' . time() . '.txt';
  if (!file_exists($fname))
  {
    $attempt = 0;
    $max_attempts = 5;
    while ($attempt < $max_attempts)
    {
      $json = (http($query));

      if (is_tmp_error($json))
      {
        $efname = 'cache/err_chat_' . time() . '.txt';
        file_put_contents($efname, $json);
        add_log('Failed to make send chat phrase request (503), repeating...', 1);
        $attempt++;
        sleep(10 + rand(1, 10));
      }
      else
      {
        file_put_contents($fname, $json);
        return $json;
      }
    }
    add_log('Max attempts reached, breaking', 1);
  }
  return false;

}

function get_chat_log($cookie, $token,$fraction=true,$from=-1,$to=-1,$minlat='-90000000', $minlng='-180000000', $maxlat='90000000', $maxlng='180000000',$maxnum='100')
{

  $useragent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11';

  $referer = 'http://www.ingress.com/intel';

  $path = '/rpc/dashboard.getPaginatedPlextsV2';

  $host = 'www.ingress.com';

  $query = 'POST ' . $path . ' HTTP/1.1' . "\r\n" .
    'Host: ' . $host . "\r\n" .
    'User-Agent: ' . $useragent . "\r\n";

  if ($cookie != '') $query .=
    'Cookie: ' . $cookie . "\r\n";

  if ($referer != '') $query .=
    'Referer: ' . $referer . "\r\n";

  $query .= 'Connection: close' . "\r\n" .
    'Origin: http://www.ingress.com' . "\r\n" .
    'X-Requested-With: XMLHttpRequest' . "\r\n" .
    'X-Csrftoken: ' . $token . "\r\n" .
    'Content-Type: application/json; charset=UTF-8' . "\r\n" .
    'Accept: application/json, text/javascript, */*; q=0.01' . "\r\n" .
    'Accept-Language: en-US,en;q=0.8,ru;q=0.6' . "\r\n" .
    'Accept-Charset: windows-1251,utf-8;q=0.7,*;q=0.3' . "\r\n";

#search global area
  $query2 = '{"desiredNumItems":'.$maxnum.',"minLatE6":'.$minlat.',"minLngE6":'.$minlng.',"maxLatE6":'.$maxlat.',"maxLngE6":'.$maxlng.',"minTimestampMs":'.$from.',"maxTimestampMs":'.$to;
  if($fraction)
    $query2.=',"factionOnly":true';
  #',"ascendingTimestampOrder":true
  $query2.=',"method":"dashboard.getPaginatedPlextsV2"}';


  $query .= 'Content-Length: ' . strlen($query2) . "\r\n\r\n";
  $query .= $query2;
  #add_log($query,1);

  $fname = 'cache/chat_' . time() . '.txt';
  if (!file_exists($fname))
  {
    $attempt = 0;
    $max_attempts = 5;
    while ($attempt < $max_attempts)
    {
      $json = (http($query));
      #add_log($json,1);

      if (is_tmp_error($json))
      {
        $efname = 'cache/err_log_' . time() . '.txt';
        file_put_contents($efname, $json);
        add_log('Failed to make send chat log request (503), repeating...', 1);
        sleep(2 + rand(1, 3));
        $attempt++;
      }
      else
      {
        file_put_contents($fname, $json);
        return $json;
      }
    }
    add_log('Max attempts reached, breaking', 1);
  }
  else
    return false;
}

function f_connect()
{
  $db = mysql_connect('localhost', 'root', '');
  mysql_select_db('ingress', $db);
}

function check_badgerov($code)
{
  $result = mysql_query('select 1 from ingress_words where word="' . addslashes($code) . '"');
  if (mysql_num_rows($result))
    return 1;
  return 0;
}

function is_action_message($s)
{

  if (strpos($s, 'deployed'))
    return 1;
  if (strpos($s, 'destroyed'))
    return 1;
  if (strpos($s, 'captured'))
    return 1;
  if (strpos($s, 'linked'))
    return 1;
  if (strpos($s, 'has decayed'))
    return 1;
  if (strpos($s, 'created a Control Field'))
    return 1;

  return 0;
}

function rand_shit()
{
  $f = file('passcode_phrases.txt');
  return $f[rand(0, sizeof($f) - 1)];
}

?>