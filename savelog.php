<html>
<!--<head>
    <script type="text/JavaScript">
        //setTimeout("location.reload(true);",30000);//reload every 30 sec for client system. Of cause, better use cron.
    </script>
</head>-->
      <head>
<meta charset="utf-8" />
</head>
  
<body>
<?
date_default_timezone_set('Europe/Moscow');
set_time_limit(70);
#chdir('D:\WEB\htdocs\localhost\ingress');
include 'http_magic.php';
include 'ingress_magic.php';

set_log('savelog');

if(file_exists('userdata.cache.txt')&&(filemtime('userdata.cache.txt')>filemtime('userdata.php')))
{
  add_log('Using cached user data',1);
  $user=unserialize(file_get_contents('userdata.cache.txt'));
}
else
{
  add_log('Using user data from php file',1);
  include 'userdata.php';
}


$c=mysql_connect( 'localhost', 'root', '1111');
mysql_select_db('ingress', $c);

$totime=-1;
$sql='SELECT MAX(TIMESTAMP) FROM chat';
$result=mysql_query($sql);
$row=mysql_fetch_row($result);
if($row[0])
  $fromtime=$row[0];
else
{
  add_log('Beginning stats from NOW -1 min',1);
  $fromtime=(time()-60)*1000;
}

$user2=$user;
$json = get_chat_log($user['cookie_checker'], $user['token_checker'],false,$fromtime,$totime);

$user2['cookie_checker']=update_cookie($json,$user['cookie_checker']);
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
if (!is_array($a))
{
  add_log('Failed to parse json! Source: <br>' . $json, 1);
  #sleep(30);
  die();
}
else add_log('Chat log: ', 1);


print_R($a);
/*
$a=array_reverse($a['result']);
  foreach ($a as $key2 => $val2)# ($val as $key2 => $val2)
  {
    $s = $val2[2]['plext']['text'];
    if($last_timestamp==-1)
      $last_timestamp=$val2[1];
    #add_log('Last timestamp: '.intval($last_timestamp),1);
    $time=date('Y-m-d H:i:s',floor($val2[1]/1000));
    $p = strpos($s, ': ');
    $b = substr($s, 0, $p);
    $s = substr($s, $p);
    add_log('<font color="green">'.$time . $b . '</font>' . $s, 1);
  }
}*/


if(serialize($user)!==serialize($user2))
{
  add_log('Caching new user cookie',1);
  file_put_contents('userdata.cache.txt',serialize($user2));
}
?> 