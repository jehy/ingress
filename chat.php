<html>
<!--<head>
    <script type="text/JavaScript">
        //setTimeout("location.reload(true);",30000);//reload every 30 sec for client system. Of cause, better use cron.
    </script>
</head>-->
      <head>
<meta charset="utf-8" />
<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.0/themes/base/jquery-ui.css" />
<script src="http://code.jquery.com/jquery-1.8.3.js"></script>
<script src="http://code.jquery.com/ui/1.10.0/jquery-ui.js"></script>
<link rel="stylesheet" href="/resources/demos/style.css" />
<script>
$(function() {
$( "#datepicker" ).datepicker();
});
</script>
</head>
  
<body>
<?
date_default_timezone_set('Europe/Moscow');
set_time_limit(70);
#chdir('D:\WEB\htdocs\localhost\ingress');
include 'http_magic.php';
include 'ingress_magic.php';
include 'userdata.php';
set_log('chat');


$date=$_REQUEST['date'];
if(!$date)
{
?><form action="chat.php">
  MinLat: <input type="text" name="minlat"><br>
  MinLong: <input type="text" name="minlng"><br>
  
  MaxLat: <input type="text" name="maxlat"><br>
  MaxLong: <input type="text" name="maxlng"><br>
  
  Date:<input type="text" id="datepicker" name="date">
  <!--MinTime: <input type="text" name="mintime"><br>
  MaxTime: <input type="text" name="maxtime"><br>-->
<input type="submit" value="send">
  </form><?
}
else
{
  $date=date_create_from_format('m/d/Y',$date);
  $fromtime=date_format($date,'U');
  echo date_format($date,'Y-m-d');
  $totime=$fromtime+3600*20;
  $fromtime=($fromtime).'000';
  $totime=($totime).'000';
  #$totime='-1';
  $minlat=$_REQUEST['minlat'];
  $minlng=$_REQUEST['minlng'];
  $maxlat=$_REQUEST['maxlat'];
  $maxlng=$_REQUEST['maxlng'];
  $maxnum='2000';
    
$json = get_chat_log($cookie_checker, $token_checker,false,$fromtime,$totime,$minlat, $minlng, $maxlat,$maxlng,$maxnum);

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
}
?> 