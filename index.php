<html>
<head>
    <script type="text/JavaScript">
        //setTimeout("location.reload(true);",30000);//reload every 30 sec for client system. Of cause, better use cron.
    </script>
</head>
<body>
<?
date_default_timezone_set('Europe/Moscow');
set_time_limit(50);
chdir('C:\WEB\htdocs\localhost\ingress');
include 'http_magic.php';
include 'ingress_magic.php';
include 'userdata.php';

add_log('current reload: ' . date('Y-m-d H:i:s') . '<hr>', 1);
set_log('log');

sleep(rand(1, 10));
#error_reporting(E_ALL);

$json = get_log($cookie_checker, $token_checker);
if (!$json)
{
  add_log('No new info in current second!', 1);
  die();
}

$codes = array();
#die(date('H-i:s',1357416232198));
$p = strpos($json, "\r\n\r\n");
$json = substr($json, $p + 4);
$a = json_decode($json, true);
#print_R($a);
if (!is_array($a))
{
  add_log('Failed to parse json! Source: <br>' . $json, 1);
  #sleep(30);
  die();
}
else add_log('Chat log: ', 1);
foreach ($a as $key => $val)
  foreach ($val as $key2 => $val2)
  {
    $s = $val2[2]['plext']['text'];
    $contains_pass = 0;
    #$s='woldklfnm wef !vakavaka131, shit passcode some more shitty shit';
    if (strpos($s, 'deployed'))
      continue;
    if (strpos($s, 'destroyed'))
      continue;
    if (strpos($s, 'captured'))
      continue;
    if (strpos($s, 'linked'))
      continue;
    if (strpos($s, 'has decayed'))
      continue;
    if (strpos($s, 'created a Control Field'))
      continue;
    $p = strpos($s, ': ');
    $b = substr($s, 0, $p + 2);
    $s = substr($s, $p + 2);
    add_log('<font color="green">' . $b . '</font>' . $s, 1);
    if (stripos($s, 'passcode') !== FALSE)
      $contains_pass = 1;
    $s = str_replace(array(',', '.', "\n", "\t", "\r", "-", "!", ':', ';'), ' ', $s);
    $a = explode(' ', $s);
    #print_R($a);die();
    foreach ($a as $val)
    {
      if (!$val) #empty
        continue;
      $numMatches = preg_match('/^[A-Za-z0-9]+$/', $val, $matches);
      #add_log($val,1);
      #if(!ctype_alnum($val))#
      if (!$numMatches)
      {
        #add_log('Strange chars!',1);
        continue;
      }
      if (strlen($val) < 6)
      {
        #add_log('Too short!',1);
        continue;
      }
      if (ctype_alpha($val) && !$contains_pass)
      {
        #add_log('No numbers!',1);
        continue;
      }
      #add_log('added',1);
      $codes[] = $val;
    }
  }
add_log('<hr>', 1);
if (sizeof($codes))
{
  $codes = array_unique($codes);
  add_log('possible codes:<br>' . implode('<br>', $codes), 1);
  flush();
  ob_flush();
  #add_log('Connecting database',1);
  #flush();ob_flush();
  #f_connect();
  #add_log('Database connected',1);
  #flush();ob_flush();
}
else
  add_log('no codes found...', 1);
add_log('<hr>', 1);
foreach ($codes as $val)
{
  $val = trim($val); #just in case
  add_log('Checking <b>' . $val . '</b><ul>', 1);
  flush();
  ob_flush();

  /*$r=check_badgerov($val);
  if($r)
  {
    add_log('Found in badgerov DB, skipping',1);
    continue;
  }*/
  $json = send_passcode($cookie_checker, $token_checker, $val);
  if ($json === 'Already sent')
  {
    add_log('Already sent', 1);
  }
  else
  {
    #$p=strpos($json,"\r\n\r\n");
    #$json=substr($json,$p+4);
    #$r=serialize(json_decode($json,true));
    $r = $json;
    if (stripos($r, 'Award') !== FALSE)
    {
      add_log('<font color="green">Success! Sending 2</font><ul>', 1);
      $r2 = send_passcode($cookie_to, $token_to, $val, 1);
      if (stripos($r2, 'Award'))
      {
        add_log('<font color="green">Success 2!</font>', 1);
        send_msg($cookie_to, $token_to, rand_shit() . ' ' . $val);
      }
      else
        add_log('<font color="red">Fail on 2!</font>', 1);
      add_log('</ul>', 1);

    }
    else
      add_log('Failed: ' . $r, 1);
  }
  add_log('</ul>', 1);
  flush();
  ob_flush();
}
add_log('<hr>Task finished<br><br>', 1);
flush();
ob_flush();
#sleep(30);
?> 