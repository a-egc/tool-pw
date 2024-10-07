<?php

if (!isset($_SESSION)) {
  session_start();
}

$show_flg = 0;
$flg = 0;
$length = 0;
$count = 0;
$type = 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

  if (isset($_POST['show_flg'])) {
    //表示フラグ
    $show_flg = htmlspecialchars($_POST["show_flg"], 3, 'UTF-8');
  }

  if (isset($_POST['flg'])) {
    //ダウンロードフラグ
    $flg = htmlspecialchars($_POST["flg"], 3, 'UTF-8');
  }

  if (isset($_POST['length'])) {
    //パスワード長
    $length = htmlspecialchars($_POST["length"], 3, 'UTF-8');
  }

  if (isset($_POST['count'])) {
    //件数
    $count = htmlspecialchars($_POST["count"], 3, 'UTF-8');
  }

  if (isset($_POST['type'])) {
    //文字
    $type = htmlspecialchars($_POST["type"], 3, 'UTF-8');
  }


  if ($flg > 0 && is_numeric($length) && is_numeric($count)) {
    if ($show_flg == 2) {
      //表示されたパスワードのダウンロード
      if ($_SESSION['pwcreatenow'] == $flg) {
        //パスワード生成のみ

        if(isset($_SESSION["temparray"])){
          $arr_pass_show = $_SESSION["temparray"];
          putTxt(array("length" => $length, "count" => $count, "type" => $type), $arr_pass_show);
  
          $_SESSION['pwcreatenow'] = $flg;
        }

      } else {
        $show_flg = 0;
      }
    } elseif ($show_flg == 1) {
      //表示
      if (!isset($_SESSION['pwcreatenow']) || $_SESSION['pwcreatenow'] != $flg) {
        //パスワード生成のみ
        $arr_pass_show = pwcreate($length, $count, $type);

        $_SESSION["temparray"] = $arr_pass_show;
        $_SESSION['pwcreatenow'] = $flg;
      } else {
        $show_flg = 0;
        unset($_SESSION["temparray"]);
      }
    } else {

      if (!isset($_SESSION['pwcreatenow']) || $_SESSION['pwcreatenow'] != $flg) {

        //パスワード出力
        // put(array("length" => $length, "count" => $count, "type" => $type)); //csv
        putTxt(array("length" => $length, "count" => $count, "type" => $type)); 

        $_SESSION['pwcreatenow'] = $flg;
      }
    }
  }
}



function pwcreate($length = 8, $count = 100, $type = 1)

{

  mb_language("japanese");
  mb_internal_encoding("UTF-8");

  // header('Content-Type: text/csv');
  // header('Content-Disposition: attachment; filename="password_' . $count . 's.csv"');

  $arr_pass = array();

  // if ($length % 2 == 0) {
  //   $evenlen = $length;
  // } else {
  //   $evenlen = $length + 1;
  // }

  switch ($type) {
    case 2:
      $str = "ABCDEFGHIJKLMNPQRSTUVWXYZabcdefghijkmnprstuvwxyz123456789";
      break;
    case 3:
      $str = "1234567890";
      break;
      case 4:
        $str = "abcdefghijkmnprstuvwxyz";
        break;
    default:
      $str = "abcdefghijkmnprstuvwxyz123456789";
      break;
  }
  
  $repeat_time = (strlen($str) - (strlen($str) % 3)) / 3;

  $i = 0;
  while ($i < $count) {
    // $bytes = openssl_random_pseudo_bytes($evenlen);
    // $pass = substr(bin2hex($bytes), 0, $length);

    $pass = substr(str_shuffle(str_repeat($str, $repeat_time)), 0, $length);

    if($pass[0] != '0') {
      if (in_array($pass, $arr_pass) === false) {
        $i++;
        $arr_pass[$i] = $pass;
      }
    }
  }

  return $arr_pass;
}

function put($arr_status, $arr_pw = NULL)
{
  
  if (!is_null($arr_pw) && is_array($arr_pw) && !empty($arr_pw)) {
    $arr_pass = $arr_pw;
  } else {
    $arr_pass = pwcreate($arr_status["length"], $arr_status["count"], $arr_status["type"]);
  }

  try {
    // header('Content-Type: text/csv');
    // header('Content-Disposition: attachment; filename="password_' . $arr_status["count"] . 's.csv"');

    $file_name = 'password_' . $arr_status["count"] . 's.csv';
    // $file = fopen("php://temp", "w");
    // 一時ファイルを作成
    $file = tmpfile();
    // $file = fopen("php://output", "w");
    if ($file === FALSE) {
      throw new Exception('ファイルの書き込みに失敗しました。');
    }

    //ヘッダー
    $header = array("No.", "PW");
    fputcsv($file, $header);

    //パスワード
    foreach ($arr_pass as $k => $val) {
      fputcsv($file, array($k, $val));
    }

    // $content_length = filesize($file_name);

    // /* 出力バッファリングを無効化する */
    while (ob_get_level()) {
      ob_end_clean();
    }

    rewind($file); // ポインタを戻す
    $content = stream_get_contents($file);
    /* 出力 */
    // file_put_contents('password_' . $count . 's.csv', $content);


    /* ダウンロード用のHTTPヘッダー送信 */
    header("Cache-Control: private");
    header("Pragma: private");
    header('Content-Description: File Transfer');
    header("Content-Disposition: attachment; filename*=UTF-8''" . $file_name);
    header("Content-Length: " . strlen($content));
    // header("Content-Type: application/octet-stream");
    header("Content-Type: application/force-download");
    header('Content-Transfer-Encoding: binary');


    // unlink($file);
    readfile(stream_get_meta_data($file)['uri']);

    /* 閉じる */
    fclose($file);

    exit;
  } catch (Exception $e) {
    echo $e->getMessage();
  }
}

function putTxt($arr_status, $arr_pw = NULL)
{
  
  if (!is_null($arr_pw) && is_array($arr_pw) && !empty($arr_pw)) {
    $arr_pass = $arr_pw;
  } else {
    $arr_pass = pwcreate($arr_status["length"], $arr_status["count"], $arr_status["type"]);
  }

  try {
    // header('Content-Type: text/csv');
    // header('Content-Disposition: attachment; filename="password_' . $arr_status["count"] . 's.csv"');

    $file_name = 'password_' . $arr_status["count"] . 's.txt';
    // $file = fopen("php://temp", "w");
    // 一時ファイルを作成
    $file = tmpfile();
    // $file = fopen("php://output", "w");
    if ($file === FALSE) {
      throw new Exception('ファイルの書き込みに失敗しました。');
    }


    //パスワード
    foreach ($arr_pass as $val) {
      fwrite($file, $val . PHP_EOL);
    }

    // $content_length = filesize($file_name);

    // /* 出力バッファリングを無効化する */
    while (ob_get_level()) {
      ob_end_clean();
    }

    rewind($file); // ポインタを戻す
    $content = stream_get_contents($file);

    /* ダウンロード用のHTTPヘッダー送信 */
    header("Cache-Control: private");
    header("Pragma: private");
    header('Content-Description: File Transfer');
    header("Content-Disposition: attachment; filename*=UTF-8''" . $file_name);
    header("Content-Length: " . strlen($content));
    // header("Content-Type: application/octet-stream");
    header("Content-Type: application/force-download");
    header('Content-Transfer-Encoding: binary');


    // unlink($file);
    /* 出力 */
    readfile(stream_get_meta_data($file)['uri']);

    /* 閉じる */
    fclose($file);

    exit;
  } catch (Exception $e) {
    echo $e->getMessage();
  }
}

$res = null;




// echo $retstr;
?>
<!DOCTYPE HTML>
<HTML lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
  <title>パスワード作成</title>
  <style>
    * {
      font-family: "BIZ UDGothic", "メイリオ", sans-serif;

    }

    html {
      font-size: 16px;
      background-color: #f3f3f3;
      color: #2d2d2d;
    }

    form {
      width: 700px;
      max-width: 100%;
      margin: 10px auto;
      padding: 1em;
    }

    h2 {
      font-size: 1.3em;
    }

    form div {
      padding: 4px;
    }

    button {
      box-sizing: border-box;
      width: 80%;
      max-width: 250px;
      min-width: 100px;
      margin: 2em auto;
      font-size: 1.2em;
      /* border: 1px solid #227c7c; */
      border: 0;
      background-color: #227c7c;
      color: #fff;
      font-weight: bold;
      padding: 1em;
    }

    button.btn2 {
      background-color: #43438d;
    }

    button.btn3 {
      background-color: #bfefff;
      color: #3e3e3e;
    }

    .wrap-group {
      width: 100%;
      display: flex;
      flex-flow: row wrap;
      margin: 4px 0;
    }

    input.pwtxt {
      border: 1px solid #ddd;
      background-color: #fff;
      padding: 10px;
      box-shadow: none;
      appearance: none;
      margin: 0 2px;
    }
  </style>
</head>

<body>
  <form name="form" action="" method="post">
    <input type="hidden" name="flg" value="<?= $flg ?>" />
    <input type="hidden" name="show_flg" value="<?= $show_flg ?>" />
    <div>
      <h2></h2>
      <label for="length">パスワード桁数</label>
      <input type="number" max="20" min="3" step="1" name="length" value="<?php $length > 0 ? print($length) : print(8) ?>" id="length">
    </div>
    <div style="display:flex">
      <label for="length">文字種</label>
      <div>
        <input type="radio" name="type" id="type1" value="1" <?php $type != 2 ? print("checked") : ""; ?>>
        <label for="type1" title="abcdefghijkmnprstuvwxyz123456789">英字（小文字）＋数字</label>
      </div>
      <div>
        <input type="radio" name="type" id="type2" value="2" <?php $type == 2 ? print("checked") : ""; ?>>
        <label for="type2" title="ABCDEFGHIJKLMNPQRSTUVWXYZabcdefghijkmnprstuvwxyz123456789">英字＋数字</label>
      </div>
      <div>
        <input type="radio" name="type" id="type3" value="3" <?php $type == 3 ? print("checked") : ""; ?>>
        <label for="type3" title="1234567890">数字</label>
      </div>
      <div>
        <input type="radio" name="type" id="type4" value="4" <?php $type == 4 ? print("checked") : ""; ?>>
        <label for="type4" title="abcdefghijkmnprstuvwxyz">英字（小文字）</label>
      </div>
    </div>


    <div>
      <label for="count">出力件数</label>
      <input type="number" max="50000" min="1" step="1" name="count" value="<?php $count > 0 ? print($count) : print(100) ?>" id="count">
    </div>

    <div>
      <button type="button" onclick="show();" class="btn2">作成</button>
      <?php if ($show_flg == 1) { ?>
        <button type="button" onclick="downl();" class="btn3">表示中のパスワードを<br>ダウンロード</button>
      <?php } ?>
      <br>
      <button type="button" onclick="exec();" class="btn1">作成してダウンロード</button>
    </div>
  </form>
  <?php if (isset($arr_pass_show)) { ?>
    <div>表示<span style="font-size:0.8em">(1000件まで)</span></div>
    <div class="wrap-group">
      <?php $i = 0;
      while ($i < 1000) {
        $i++;

      ?>
        <input id="pwtxt<?= $i ?>" title="パスワード<?= $i ?>" type="text" readonly value="<?= $arr_pass_show[$i] ?>" class="pwtxt">
      <?php
        if ($i == max(array_keys($arr_pass_show))) {
          break;
        }
      } ?>
    </div>
    <br>
  <?php } ?>
  <script>
    function exec() {
      var now = Date.now();

      let len = document.getElementById("length").value;
      let cnt = document.getElementById("count").value;

      if (len > 0 && cnt > 0) {

        document.forms['form'].show_flg.value = 0;
        document.forms['form'].flg.value = now;
        document.forms['form'].method = "post";
        document.forms['form'].encoding = "application/x-www-form-urlencoded";
        document.forms['form'].action = "";
        document.forms['form'].submit();
      } else {
        return false;
      }

    }

    function show() {
      var now = Date.now();

      let len = document.getElementById("length").value;
      let cnt = document.getElementById("count").value;

      if (len > 0 && cnt > 0) {

        document.forms['form'].show_flg.value = 1;
        document.forms['form'].flg.value = now;
        document.forms['form'].method = "post";
        document.forms['form'].encoding = "application/x-www-form-urlencoded";
        document.forms['form'].action = "";
        document.forms['form'].submit();
      } else {
        return false;
      }

    }

    function downl() {

      document.forms['form'].show_flg.value = 2;
      document.forms['form'].method = "post";
      document.forms['form'].encoding = "application/x-www-form-urlencoded";
      document.forms['form'].action = "";
      document.forms['form'].submit();
    }
  </script>
</body>

</html>