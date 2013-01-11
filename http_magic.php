<?
#
# HTTP_MAGIC.PHP v.25/05/11   v3.82
# функции для работы с протоколом HTTP/SSL и данными в HTML.
#
#
#    http($request,$port=80,$ssl=false,$read=true) # общая функция для запросов.
#
#         $request      -       полный http запрос
#         $port         -       можно указать порт коннекта
#         $ssl          -       true/false (ssl или обычный)
#         $read         -       true/false/header (читать, не читать, читать только заголовок ответа)
#
#    http_get($addr,$cookies=array(),$referer='',$useragent='....',$ssl=false,$read=true)     ! в версии 3.5 функцию не проверял
#
#         $addr         -       адрес скачиваемой страницы (http://www.domain.com/page.html или www.domain.com/page.html)
#         $cookies      -       можно передать стандартный массив кук, полученный функцией cookies($header);
#         $referer      -       можно передать реферала страницы
#         $useragent    -       можно передать юзерагент
#         $port         -       можно указать порт коннекта
#         $ssl          -       true/false (ssl или обычный)
#         $read         -       true/false/header (читать, не читать, читать только заголовок ответа)
#
#
#    cookies($header,$cookies=array()) # Экстракт и синхронизация кукис
#
#         $header       -       свежеполученный хидер
#         $cookies      -       массив с куками извлеченными ранее при помощи cookies()
#         для использования кукисов в запросах используйте $cookie=implode("\r\n",$cookies);
#
#
#    form_extractor($html,$clear=true)
#
#       Добывает все инпуты из всех форм (работает только с тегами <input, текстарии и прочие селекты не канают, значения берёт только указанные)
#       После чего выдёргивает из них все пропертисы и в случае если указана $clear=true (по умолчанию указана!) оставляет только name и value
#       Соответственно в результате либо массив массивов знаений инпутов[form_i]=>[input_name]=>[input_value]
#       либо массив массивов массивов [form_i]=>[input_name]=>[input_prop_name]=>[input_prop_value];
#
#       $html - код страницы
#       $clear - true (только имена и значения инпутов) / false (все свойства инпутов массивом)
#
#
#    tag_props_extractor($html)
#       Добывает из тега все пропертисы!.
#       Тег ($html) должен быть голый.
#       т.е. из
#               <a href="..." target="..." color="...">...</a>
#       нужно только
#               href="..." target="..." color="..."
#
#
#
#
#    TODO:
#       * Добавить возможность сохранять, загружать, загружать последнюю сохранённую сессию - т.е. куки
#       * Добавить возможность делить куки по доменам внутри единого контейнера (отдельную функцию типа cookies_domain($cookies,)
#


function http($request, $port = 80, $ssl = false, $read = true) # общая функция.
{
  #    echo eee('HTTP Request: ',$request);
  $addr = explode('host: ', strtolower($request), 2);
  $addr = explode("\n", $addr[1], 2);
  $addr = trim($addr[0]);

  $f = http_open($addr, $port, $ssl); # новый
  if ($f == false) return $f;

  @stream_set_blocking($f, 1);

  fwrite($f, $request); # отправляем запрос

  if ($read == false) return;
  $data = '';
  while (true)
  {
    $line = fgets($f);
    $data .= $line;
    if ($line === "\r\n")
    {
      $method = http_method($data);
      break;
    }
  }

  if ($read === 'header') return $data;

  if ($method === 'chunked') # читаем методом CHUNKED
  {
    $data .= http_read_chunked($f);
  }
  else
  {
    $data .= http_read_all($f); # читаем обычным методом
  }
  fclose($f);
  #   echo eee('HTTP Result: ',$data);

  return $data;
}

function http_get($addr, $cookies = array(), $referer = '', $useragent = 'Mozilla/5.0 (Windows; U; Windows NT 6.1; ru; rv:1.9.2.15) Gecko/20110303 MRA 5.7 (build 03686) Firefox/3.6.15')
{
  # Для ускорения работы с методом GET
  $x = explode('//', $addr, 2);
  if (strtolower($x[0]) == 'http:') $addr = $addr[1];

  $addr = explode('/', $addr, 2);

  $query = 'GET /' . $addr[1] . ' HTTP/1.1' . "\r\n" .
    'Host: ' . $addr[0] . "\r\n" .
    'User-Agent: ' . $useragent . "\r\n";

#  if ($cookie != '')
    $query .= 'Cookie: ' . implode('; ', $cookies) . "\r\n";

  if ($referer != '')
    $referer .= 'Referer: ' . $referer . "\r\n";

  $query .= 'Connection: close' . "\r\n" .
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8' . "\r\n" .
    'Accept-Language: en-us,en;q=0.7,ru;q=0.3' . "\r\n" .
    'Accept-Charset: windows-1251,utf-8;q=0.7,*;q=0.7' . "\r\n" . "\r\n";

  $res = http($query); # http_v_3
  return $res;
}

function http_method($header)
{
  $header = header_parser($header);
  $method = strtolower($header['transfer-encoding']);
  if ($method != 'chunked') return 'plain';
  else return 'chunked';
}

function http_read_all($f) # читает документ целиком в обычном режиме. для ф-ии HTTP
{
  $data = '';
  while (!feof($f)) $data .= fgets($f, 16384);
  return $data;
}

function http_error($e = '')
{
  if (!$e) $e = @error_get_last();
  else $e['message'] = $e;
  if (!$e) $e = $php_errormsg;
  echo '<div style="padding:20px;background-color:#FFFFCC;border:1px solid silver">http_magic error: <b>' . $e['message'] . '</b></div>';
}

function http_open($addr, $port = 80, $ssl = false) # функция открытия соединения. для ф-ии HTTP
{
  if ($ssl) $addr = 'ssl://' . $addr;
  $f = @fsockopen($addr, $port, $errno, $errstr, 5);
  if ($f === false) http_error();
  return $f;
}

function http_read_chunked($f) # адекватное чтение в случае режима CHUNKED (нужно обязательно при чтении картинок)
{
  $length = fgets($f);
  $length = hexdec($length);
  $data='';
  while (true)
  {
    if ($length < 1) break;
    $x = '';
    while (strlen($x) < $length)
    {
      $b = fread($f, $length - strlen($x));
      if ($b == '') return $data;
      $x .= $b;
    }
    $data .= $x;
    fgets($f);
    $length = rtrim(fgets($f));
    $length = hexdec($length);
  }
  return $data;
}


function header_parser($header) # Парсит хидеры и достаёт из них всё ценное.
{
  $headers = explode("\r\n\r\n", $header, 2);
  $headers = explode("\r\n", trim($headers[0]));
  $http = trim(array_shift($headers));
  $harray = array(); #all headers
  $cookies = array(); #cookies
  foreach ($headers as $i => $header)
  {
    $header = explode(':', trim($header), 2);
    $type = strtolower($header[0]);
    $value = trim($header[1]);
    if ($type == 'set-cookie')
    {
      $value = explode(';', $value, 2);
      $harray['set-cookie'][] = $value[0];
    }
    else
    {
      $harray[$type] = $value;
    }
  }
  return $harray;
}

function extract_charset($header) # из хидера вытаскивает название кодировки
{
  $charset = explode('charset=', $header, 2);
  $charset = explode("\r\n", $charset[1], 2);
  $charset = explode(';', $charset[0], 2);
  $charset = $charset[0];
  return $charset;
}

function cookies($header, $cookies = array()) # Возвращает массив кук. элементы вида cookeis[0]="name=value"; Может совмещать куки с массивом cookies, такого же вида.
{
  $headers = explode("\r\n\r\n", $header, 2);
  $headers = explode("\r\n", trim($headers[0]));
  $http = trim(array_pop($headers));
  foreach ($headers as $i => $header)
  {
    $header = explode(':', trim($header), 2);
    $type = strtolower($header[0]);
    $value = trim($header[1]);
    if ($type == 'set-cookie')
    {
      $value = explode(';', $value, 2);
      $cookies[] = $value[0];
    }
  }
  return cl_cookies($cookies);
}

function cl_cookies($cookies) # "чистит" массив кук. Вспомогательная для cookies
{
  $cl = array();
  foreach ($cookies as $c)
  {
    $c = explode('=', $c, 2);
    $cl[$c[0]] = $c[1];
  }
  $cookies = array();
  foreach ($cl as $name => $c)
  {
    $cookies[] = $name . '=' . $c;
  }
  return $cookies;

}

# Добывает все инпуты из всех форм (работает только с тегами <input, текстарии и прочие селекты не канают, значения берёт только указанные)
# После чего выдёргивает из них все пропертисы и в случае если указана $clear=true (по умолчанию указана!) оставляет только name и value
# Соответственно в результате либо массив массивов знаений инпутов[form_i]=>[input_name]=>[input_value]
# либо массив массивов массивов [form_i]=>[input_name]=>[input_prop_name]=>[input_prop_value];
function form_extractor($html, $clear = true)
{
  $forms = array();
  $offset = 0;
  $id = 0;
  while (true)
  {
    $find = stripos($html, '<form ', $offset);
    if ($find === false) break;
    $start = $find;
    $end = stripos($html, '</form', $start);
    if ($end == false) $end = strlen($html) - 1;
    $offset = $end;
    $form = substr($html, $start + 5, $end - $start - 5);
    $inputs = input_extractor($form);
    $form = substr($form, 0, strpos($form, '>'));
    $form = tag_props_extractor($form);
    $name = $form['name'];
    if ($name == '') $name = $form['id'];
    if ($name == '' || isset($forms[$name])) $name = $id++;
    $forms[$name] = array('properties' => $form, 'inputs' => $inputs);
  }
  return $forms;
}

# Добывает все инпуты из формы (работает только с тегами <input, текстарии и прочие селекты не канают, значения берёт только указанные)
# После чего выдёргивает из них все пропертисы и в случае если указана $clear=true (по умолчанию указана!) оставляет только name и value
# Соответственно в результате либо массив name=>value либо массив массивов name=>array(...props...);
# Exclude_image - исключает попадание input type=image в результаты. Вхуй! Заебали тут толпиться!
function input_extractor($html, $clear = true, $exclude_image = true)
{
  $inputs = array();
  $offset = 0;
  while (true)
  {
    $find = stripos($html, '<input ', $offset);
    if ($find === false) break;

    $start = $find;
    $end = stripos($html, '>', $start);
    if ($end == false) $end = strlen($html) - 1;
    $offset = $end;

    $input = substr($html, $start + 6, $end - $start - 6);
    $props = tag_props_extractor($input);
    if (strtolower($props['type']) != 'image' || $exclude_image == false)
    {
      if ($clear)
      {
        # name=>value!
        if ($props['name'] != '') $inputs[$props['name']] = $props['value'];
      }
      else
      {
        # name=>array();
        if ($props['name'] != '') $inputs[$props['name']] = $props;
      }
    }

  }
  return $inputs;
}

# Добывает из тега все пропертисы!.
# Тег должен быть голый.
# т.е. из
#        <a href="..." target="..." color="...">...</a>
# нужно только
#        href="..." target="..." color="..."
function tag_props_extractor($html)
{
  $html = trim($html);
  $props = array();


  $begin = false;
  $char = '';
  $name = '';
  $value = '';
  $i = 0;
  for ($i = $i; $i < strlen($html); $i++)
  {
    # Если найден пробел, значит мы нашли аттрибут без значения
    if ($html[$i] == ' ')
    {
      if (trim($name) != '') $props[$name] = '';
      $name = '';
      # После аттрибута может быть куча пробелов
      while ($html[$i] === ' ') $i++;
    }
    if ($html[$i] == '=')
    {
      # Begin!;
      $i++;
      $char = $html[$i];
      if ($char == '"' || $char == "'")
      {
        # Нормальные разделители
        $i++; # Сдвиг
      }
      elseif ($char == ' ')
      {
        # Ну не должно там быть пробела никогда
        echo '<font color=red>Ебанутый инпут, хуй знает что будет за результат: <b>' . $html . '</b></font><Br>';
        if (trim($name) != '') $props[$name] = '';
        $name = '';
        $char = false;
      }
      else
      {
        # Разделителя нету... значит разделитель в конце пробел
        $char = ' ';
      }
      # Разделитель определён, теперь поймать значение учитывая конец
      while (true && $char !== false)
      {
        if ($html[$i] == '') break;
        if ($html[$i] == $char) break;
        # Если тот кто вызвал функцию мудак, то концом обеда будет >
        if ($char == ' ' && $html[$i] == '>') break;
        $value .= $html[$i];
        $i++;
      }
      $name = strtolower($name);
      if ($name == 'name') $value = Cleaner($value);
      $props[strtolower(trim($name))] = trim($value);
      //if($name=='name')echo '&' . $value .  '(' . strlen($value).')=&<br>';
      $name = '';
      $value = '';
    }
    else
    {
      # Часть имени
      $name .= $html[$i];
    }
  }
  return $props;
}

function Cleaner($t)
{
  $alp = 'abcdefghijklmnopqrstuvwxyz1234567890_-[](){}%&.,=+';
  $a2 = '';
  for ($i = 0; $i < strlen($t); $i++)
  {
    if (stripos($alp, $t[$i]) !== false) $a2 .= $t[$i];
    else echo $t[$i] . '<br>';
  }
  return $a2;
}


?>
