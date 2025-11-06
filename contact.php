<?php
// 変数の初期化
$page_flag = 0;

// スパム検出関数
function detectSpam($data) {
    $spamPatterns = [
        '/credit.*account/i',
        '/transfer.*here/i',
        '/confirm.*transfer/i',
        '/\$[\d,]+\.\d{2}/i',
        '/http[s]?:\/\/[^\s]+/i',
        '/click.*here/i',
        '/verify.*account/i',
        '/suspended.*account/i',
        '/urgent.*action/i',
        '/\* \* \*.*\?/i',  // スパムメッセージのパターン
        '/\d{4,}xr/i',      // 不自然な文字列パターン
        '/ksa\d+pu/i',      // スパム件名パターン
        '/cek\d+j\d+/i'     // スパム内容パターン
    ];
    
    $allText = $data['c-name'] . ' ' . $data['c-kana'] . ' ' . $data['c-mail'] . ' ' . 
               $data['c-subject'] . ' ' . $data['c-message'];
    
    foreach ($spamPatterns as $pattern) {
        if (preg_match($pattern, $allText)) {
            return true;
        }
    }
    
    // メールアドレスドメインブラックリスト
    $blockedDomains = [
        'gmail.com',  // 今回のスパムで使用されたドメイン
        'yahoo.com',
        'hotmail.com',
        'outlook.com',
        'aol.com',
        'mail.ru',
        'yandex.ru',
        'protonmail.com',
        'tutanota.com',
        'guerrillamail.com',
        '10minutemail.com',
        'tempmail.org'
    ];
    
    $emailDomain = substr(strrchr($data['c-mail'], "@"), 1);
    if (in_array(strtolower($emailDomain), $blockedDomains)) {
        return true;
    }
    
    // Honeypotフィールドのチェック
    if (!empty($data['website'])) {
        return true;
    }
    
    return false;
}

// レート制限チェック
function checkRateLimit($ip) {
    $rateLimitFile = 'rate_limit.json';
    $currentTime = time();
    $rateLimit = 300; // 5分間
    $maxAttempts = 3; // 最大試行回数
    
    if (file_exists($rateLimitFile)) {
        $data = json_decode(file_get_contents($rateLimitFile), true);
    } else {
        $data = [];
    }
    
    // 古いエントリを削除
    foreach ($data as $key => $entry) {
        if ($currentTime - $entry['time'] > $rateLimit) {
            unset($data[$key]);
        }
    }
    
    // 現在のIPの試行回数をチェック
    $attempts = 0;
    foreach ($data as $entry) {
        if ($entry['ip'] === $ip) {
            $attempts++;
        }
    }
    
    if ($attempts >= $maxAttempts) {
        return false;
    }
    
    // 新しい試行を記録
    $data[] = ['ip' => $ip, 'time' => $currentTime];
    file_put_contents($rateLimitFile, json_encode($data));
    
    return true;
}

// reCAPTCHA検証
function verifyRecaptcha($token) {
    $secretKey = '6LfYourSecretKeyHere'; // 実際のシークレットキーに置き換え
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    
    $data = [
        'secret' => $secretKey,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $response = json_decode($result, true);
    
    return $response['success'] && $response['score'] > 0.5;
}

if( !empty($_POST['btn_confirm']) ) {
    // スパム検出
    if (detectSpam($_POST)) {
        die('スパムと検出された内容が含まれています。');
    }
    
    // レート制限チェック
    if (!checkRateLimit($_SERVER['REMOTE_ADDR'])) {
        die('送信回数が上限に達しました。しばらく時間をおいてから再度お試しください。');
    }
    
    // reCAPTCHA検証
    if (!empty($_POST['recaptcha_token'])) {
        if (!verifyRecaptcha($_POST['recaptcha_token'])) {
            die('reCAPTCHA検証に失敗しました。');
        }
    }

	$page_flag = 1;

} elseif( !empty($_POST['btn_submit']) ) {
    // 最終送信時も再度チェック
    if (detectSpam($_POST)) {
        die('スパムと検出された内容が含まれています。');
    }

  $page_flag = 2;

}
?>

<html lang="ja">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title> お問い合わせ | 鹿児島市認可外保育園 企業主導型保育園 サニーサイド保育園 | ほいくえんsopo By Sunny Side | 鹿児島市</title>
    <meta property="og:title" content=" お問い合わせ | 鹿児島市認可外保育園 企業主導型保育園 サニーサイド保育園 | ほいくえんsopo By Sunny Side | 鹿児島市">
    <meta name="description" content="ほいくえんsopo By Sunny Sideは、鹿児島市認可保育園「サニーサイド保育園」と業務提携しており姉妹園として認可保育園同等の保育をしております。また、企業主導型保育園として働くお父さんお母さんのサポートも行っております。">
    <meta property="og:description" content="ほいくえんsopo By Sunny Sideは、鹿児島市認可保育園「サニーサイド保育園」と業務提携しており姉妹園として認可保育園同等の保育をしております。また、企業主導型保育園として働くお父さんお母さんのサポートも行っております。">
    <meta property="og:url" content="https://hoikuen-sopo.com/place.html">
    <meta property="og:type" content="website">
    <meta property="og:image" content="/images/sopo-logo.webp" />
    <meta property="og:site_name" content="ほいくえんsopo">
    <meta name="format-detection" content="telephone=no">
    <meta name="msapplication-TileColor" content="transparent">
    <meta name="msapplication-TileImage" content="/images/sopo-logo.webp">
    <meta name="msapplication-config" content="">
    <link rel="icon" type="image/png" sizes="16x16" href="images/logo.webp">
    <link rel="icon" type="image/png" sizes="32x32" href="images/logo.webp">
    <link rel="icon" href="images/sopo-logo.webp">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="taniyama.css">
    <link rel="stylesheet" href="sunny.css">
    <link rel="stylesheet" href="side.css">
    <meta name="google-site-verification" content="nXz-7U3ZKwKe1YLXjnM5shp1tUHmD9F0CRs_3JknxnQ" />
    <script src="https://www.google.com/recaptcha/api.js?render=6LfYourSiteKeyHere"></script>
  </head>
<body data-rsssl="1" class="facility">
  <div class="facility active" id="container">
    <header class="header guidance" style="padding-left: 0px !important; background-color: #70c5c5;">

      <a href="index.html" class="toplogo">
        <img src="images/sopo-logo.webp" alt="" style="width: 100%; height: 100%;">
      </a>

      <ul class="menu pconly">

        <li class="menu__item">
          <a href="diary.html" class="menu__link" style="margin-bottom: 20px;">SOPOのブログ</a>
        </li>

        <li class="menu__item">
          <a href="about.html" class="menu__link">保育理念・保育方針</a>
          <ul class="drop-menu">
            <li class="drop-menu__item"><a href="about.html#hoiku" class="drop-menu__link">保育内容</a></li>
          </ul>
        </li>

        <li class="menu__item">
          <a href="schedule.html" class="menu__link">1日の流れ</a>
          <ul class="drop-menu">
            <li class="drop-menu__item"><a href="schedule.html#schedule" class="drop-menu__link">1日の流れ</a></li>
          </ul>
        </li>

        <li class="menu__item">
          <a href="health.html" class="menu__link">病後児・体調不良児保育について</a>
        </li>

        <li class="menu__item">
          <a href="place.html" class="menu__link">施設概要</a>
          <ul class="drop-menu">
            <li class="drop-menu__item"><a href="place.html#map" class="drop-menu__link">園の場所</a></li>
            <li class="drop-menu__item"><a href="place.html#aboutus" class="drop-menu__link">園の概要</a></li>
            <li class="drop-menu__item"><a href="place.html#image" class="drop-menu__link">施設内観</a></li>
          </ul>
        </li>

        <li class="menu__item">
          <a href="contact.php" class="menu__link">お問い合わせ</a>
        </li>
      </ul>
      <div class="nav">
        <nav class="global-nav">
          <div class="wrapper">
            <div class="inner">
              <div class="box">
                <ul class="wrap">
                  <li class="list"><a class="parent" href="index.html"><span class="ja">トップ</span><span class="en">top</span></a></li>
                  <li class="list"><a class="parent" href="diary.html"><span class="ja">SOPOのブログ</span><span class="en">diary</span></a></li>
                  <li class="list">
                    <a class="parent" href="about.html">
                      <span class="ja">保育理念・保育方針</span>
                      <span class="en">about us</span>
                    </a>
                    <ul class="child">
                      <li class="item">
                        <a href="about.html#hoiku">
                          <span>保育内容</span>
                        </a>
                      </li>
                    </ul>
                  </li>
                  <li class="list">
                    <a class="parent" href="schedule.html">
                      <span class="ja">1日の流れ</span>
                      <span class="en">schedule</span>
                    </a>
                    <ul class="child">
                      <li class="item">
                        <a href="schedule.html#schedule">
                          <span>年間・1日スケジュール</span>
                        </a>
                      </li>
                    </ul>
                  </li>
                  <li class="list"><a class="parent" href="health.html"><span class="ja">病後児・体調不良児保育について</span><span class="en">health care</span></a></li>
                  <li class="list">
                    <a class="parent" href="place.html">
                      <span class="ja">施設概要</span>
                      <span class="en">sunny side</span>
                    </a>
                    <ul class="child">
                      <li class="item">
                        <a href="place.html#map">
                          <span>園の場所</span>
                        </a>
                      </li>
                      <li class="item">
                        <a href="place.html#aboutus">
                          <span>園の概要</span>
                        </a>
                      </li>
                      <li class="item">
                        <a href="place.html#image">
                          <span>施設内観</span>
                        </a>
                      </li>
                    </ul>
                  </li>
                  <li class="list">
                    <a class="parent" href="contact.php">
                      <span class="ja">お問い合わせ</span>
                      <span class="en">contact</span>
                    </a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </nav>
        <div class="hamburger"><span class="icon"></span><span class="icon"></span><span class="icon"></span>
        </div>
      </div>
    </header>

    <?php if( $page_flag === 1 ): ?>

      <h1 style="text-align: center; font-size: 20px; margin-top: 100px; margin-bottom: 25px;">入力内容の確認</h1>

      <form id="f1" method="post" action="thanks.html" style="text-align: center;">
        <div class="element_wrap">
          <label>お名前</label>
          <br>
          <p><?php echo $_POST['c-name']; ?></p>
        </div>
        <br>
        <div class="element_wrap">
          <label>フリガナ</label>
          <br>
          <p><?php echo $_POST['c-kana']; ?></p>
        </div>
        <br>
        <div class="element_wrap">
          <label>メールアドレス</label>
          <br>
          <p><?php echo $_POST['c-mail']; ?></p>
        </div>
        <br>
        <div class="element_wrap">
          <label>メールアドレス確認用</label>
          <br>
          <p><?php echo $_POST['c-mail02']; ?></p>
        </div>
        <br>
        <div class="element_wrap">
          <label>お電話番号</label>
          <br>
          <p><?php echo $_POST['c-phone']; ?></p>
        </div>
        <br>
        <div class="element_wrap">
          <label>件名</label>
          <br>
          <p><?php echo $_POST['c-subject']; ?></p>
        </div>
        <br>
        <div class="element_wrap">
          <label>問い合わせ内容</label>
          <br>
          <p><?php echo $_POST['c-message']; ?></p>
        </div>
        <br>
        
        <a href="contact.php">戻る</a>

        <input type="submit" name="btn_submit" value="入力内容を送信" onclick="submitSJIS();">
        <input type="hidden" name="c-name" value="<?php echo $_POST['c-name']; ?>">
        <input type="hidden" name="c-kana" value="<?php echo $_POST['c-kana']; ?>">
        <input type="hidden" name="c-mail" value="<?php echo $_POST['c-mail']; ?>">
        <input type="hidden" name="c-mail02" value="<?php echo $_POST['c-mail02']; ?>">
        <input type="hidden" name="c-phone" value="<?php echo $_POST['c-phone']; ?>">
        <input type="hidden" name="c-subject" value="<?php echo $_POST['c-subject']; ?>">
        <input type="hidden" name="c-message" value="<?php echo $_POST['c-message']; ?>">

        
      </form>

      <?php
    
          mb_language("Japanese");
          mb_internal_encoding("UTF-8");

          $header = null;
          $auto_reply_subject = null;
          $auto_reply_text = null;
          $auto_reply_subject = null;
          $auto_reply_text = null;
          date_default_timezone_set('Asia/Tokyo');

          // ヘッダー情報を設定
          $header = "MIME-Version: 1.0\n";
          $header .= "From: hoikuen sopo <sawa.co.sopo1@outlook.jp>\n";
          $header .= "Reply-To: hoikuen sopo <sawa.co.sopo1@outlook.jp>\n";

          // 件名を設定
          $auto_reply_subject = 'お問い合わせありがとうございます。';

          // 本文を設定
          $auto_reply_text = "この度は、保育園そぽにお問い合わせ頂き誠にありがとうございます。
          下記の内容でお問い合わせを受け付けました。\n\n";
          $auto_reply_text .= "お問い合わせ日時：" . date("Y-m-d H:i") . "\n";
          $auto_reply_text .= "お名前：" . $_POST['c-name'] . "\n";
          $auto_reply_text .= "フリガナ：" . $_POST['c-kana'] . "\n";
          $auto_reply_text .= "メールアドレス：" . $_POST['c-mail'] . "\n";
          $auto_reply_text .= "メールアドレス確認用：" . $_POST['c-mail02'] . "\n";
          $auto_reply_text .= "お電話番号：" . $_POST['c-phone'] . "\n";
          $auto_reply_text .= "件名：" . $_POST['c-subject'] . "\n";
          $auto_reply_text .= "問い合わせ内容：" . $_POST['c-message'] . "\n\n";
          $auto_reply_text .= "このメールは送信専用ですので、返信される場合はsawa.co.sopo1@outlook.jpまでお願い致します。" . "\n\n";
          $auto_reply_text .= "保育園 そぽ";

          $to = $_POST['c-mail'];

          $admin = "s.seisaku.co@icloud.com";

          mb_send_mail($to, $auto_reply_subject, $auto_reply_text, $header);

          // 運営側へ送るメールの件名
          $admin_reply_subject = "保育園そぽへの問い合わせを受け付けました";
          
          // 本文を設定
          $admin_reply_text = "下記の内容で問い合わせがありました。\n\n";
          $admin_reply_text .= "問い合わせ日時：" . date("Y-m-d H:i") . "\n";
          $admin_reply_text .= "お名前：" . $_POST['c-name'] . "\n";
          $admin_reply_text .= "フリガナ：" . $_POST['c-kana'] . "\n";
          $admin_reply_text .= "メールアドレス：" . $_POST['c-mail'] . "\n";
          $admin_reply_text .= "メールアドレス確認用：" . $_POST['c-mail02'] . "\n";
          $admin_reply_text .= "お電話番号：" . $_POST['c-phone'] . "\n";
          $admin_reply_text .= "件名：" . $_POST['c-subject'] . "\n";
          $admin_reply_text .= "問い合わせ内容：" . $_POST['c-message'] . "\n\n";

          // 運営側へメール送信
          mb_send_mail($admin, $admin_reply_subject, $admin_reply_text, $header);
        ?>

    <?php elseif( $page_flag === 2 ): ?>

    <p>送信が完了しました。</p> 

    <?php else: ?>


    <section class="section" style="margin-top: 200px;">
      <div class="contact-form content-wrap">
        <h2 class="headding-senary">
          <span class="en">園へのお問い合わせ</span>
          <span class="ja" style="font-size: 20px;">contact</span>
        </h2>
        <p class="text-primary">
          WEBサイトからのお問い合わせには、返信に1〜2日いただくことがございます。お急ぎの方は、お電話でご連絡ください。
          <br>
          ご入力いただいた後、「送信内容の確認」ボタンを押してください。
          <br>
          ※必須科目
        </p>
        <div id="mw_wp_form_mw-wp-form-37" class="mw_wp_form mw_wp_form_input">
          <form method="post">
            <div class="form">
              <p class="head">
                入力内容
              </p>
              <table>
                <tbody>
                  <tr>
                    <th>
                      お名前
                      <span class="required">
                        ※
                      </span>
                    </th>
                    <td>
                      <input type="text" name="c-name" class="full" size="60" placeholder="田中　太郎" required="required">
                    </td>
                  </tr>
                  <tr>
                    <th>
                      フリガナ
                      <span class="required">
                        ※
                      </span>
                    </th>
                    <td>
                      <input type="text" name="c-kana" class="full" size="60" placeholder="たなか　たろう" required="required">
                    </td>
                  </tr>
                  <tr>
                    <th>
                      メールアドレス
                      <span class="required">
                        ※
                      </span>
                    </th>
                    <td>
                      <input type="email" name="c-mail" class="full" size="60" placeholder="xxxx@example.com" required="required">
                    </td>
                  </tr>
                  <tr>
                    <th>
                      メールアドレス確認用
                      <span class="required">
                        ※
                      </span>
                    </th>
                    <td>
                      <input type="email" name="c-mail02" class="full" size="60" placeholder="xxxx@example.com" required="required">
                    </td>
                  </tr>
                  <tr>
                    <th>
                      お電話番号
                      <span class="required">
                        ※
                      </span>
                    </th>
                    <td>
                      <input type="tel" name="c-phone" class="full" size="60" placeholder="080-xxxx-xxxx" required="required">
                    </td>
                  </tr>
                  <!-- <tr>
                    <th>
                      ご住所
                    </th>
                    <td>
                      <span class="mwform-zip-field">
                         〒 
                        <input type="text" name="c-postcode[data][0]" class="small" size="4" maxlength="3">
                         - 
                         <input type="text" name="c-postcode[data][1]" class="small" size="5" maxlength="4">
                      </span>
                      <input type="hidden" name="c-postcode[separator]" value="-">
                      <br>
                      <input type="text" name="c-add" class="full add" size="60">
                    </td>
                  </tr> -->
                  <tr>
                    <th>
                      件名
                      <span class="required">
                        ※
                      </span>
                    </th>
                    <td>
                      <input type="text" name="c-subject" class="full" size="60" placeholder="園児の空き状況について" required="required">
                    </td>
                  </tr>
                  <tr>
                    <th>
                      問い合わせ内容
                      <span class="required">
                        ※
                      </span>
                    </th>
                    <td>
                      <textarea name="c-message" class="full" cols="50" rows="5"></textarea>
                    </td>
                  </tr>
                </tbody>
              </table>
              <!-- Honeypot field (hidden from users) -->
              <div style="display: none;">
                <input type="text" name="website" value="" />
              </div>
              <!-- reCAPTCHA token -->
              <input type="hidden" name="recaptcha_token" id="recaptcha_token" value="">
              <!-- <div class="check">
                <label>
                  <br>
                  <input class="privacy" type="checkbox">
                  <span>下記、プライバシーポリシーに同意する</span>
                  <br>
                </label>
              </div> -->
              <p>
                <!-- <input type="hidden" name="token" value="28cnhprap6as0gs44og4wwc0kokcs80s"> -->
                <input id="send" type="submit" name="btn_confirm" class="button-flat is-disabled" value="送信内容を確認" style="color: #333;" onclick="generateRecaptchaToken();">
              </p>
              <div class="button-wrap">
                <br>
              </div>
            </div>
          </form>
        </div>
      </div>
    </section>

    <?php endif; ?>

    <footer class="footer guidance">
      <div class="inner">
        <div class="info">
          <div class="logo"><a href="index.html"><noscript><img class="lazyload" src="images/sopo-logo.webp" loading="lazy" alt="LOGO"/></noscript><img class="lazyloaded ls-is-cached" src="images/sopo-logo.webp" data-src="" loading="lazy" alt="LOGO"></a></div>
          <div class="access">
            <p class="text-primary">〒890-0053<br class="for-large"><br>鹿児島市中央町16-2 南国甲南ビル1階106区画</p>
          </div>
          <div class="request">
            <p class="text-primary">見学のお申し込みはこちら<br>［ 受付時間：8:00-18:00 ］</p><a class="tel" href="tel:099-297-5788" style="color: #e8004a;"><span>TEL.</span><span class="num">099-297-5788</span></a>
          </div>
        </div>
        <div class="footer-nav">
          <ul class="wrap">
            <li class="list"><a class="parent" href="index.html"><span class="ja">トップ</span><span class="en">top</span></a></li>
            <li class="list"><a class="parent" href="diary.html"><span class="ja">SOPOのブログ</span><span class="en">diary</span></a></li>
            <li class="list">
              <a class="parent" href="about.html">
                <span class="ja">保育理念・保育方針</span>
                <span class="en">about us</span>
              </a>
              <ul class="child">
                <li class="item">
                  <a href="about.html#hoiku">
                    <span>保育内容</span>
                  </a>
                </li>
              </ul>
            </li>
            <li class="list">
              <a class="parent" href="schedule.html">
                <span class="ja">1日の流れ</span>
                <span class="en">schedule</span>
              </a>
              <ul class="child">
                <li class="item">
                  <a href="schedule.html#schedule">
                    <span>年間・1日スケジュール</span>
                  </a>
                </li>
              </ul>
            </li>
          </ul>
          <ul class="wrap">
            <li class="list"><a class="parent" href="health.html"><span class="ja">病後児・体調不良児保育について</span><span class="en">health care</span></a></li>
            <li class="list">
              <a class="parent" href="place.html">
                <span class="ja">施設概要</span>
                <span class="en">sunny side</span>
              </a>
              <ul class="child">
                <li class="item">
                  <a href="place.html#map">
                    <span>園の場所</span>
                  </a>
                </li>
                <li class="item">
                  <a href="place.html#aboutus">
                    <span>園の概要</span>
                  </a>
                </li>
                <li class="item">
                  <a href="place.html#image">
                    <span>施設内観</span>
                  </a>
                </li>
              </ul>
            </li>
            <li class="list">
              <a class="parent" href="contact.php">
                <span class="ja">お問い合わせ</span>
                <span class="en">contact</span>
              </a>
            </li>
          </ul>
        </div>
      </div>
      <div class="under"><small class="copyright" style="color: #e8004a;">© Sawa co</small>
      </div>
    </footer>
    
    <!-- スマホ専用の下部ヘッダー -->
    <div class="mobile-bottom-header">
      <div class="mobile-nav">
        <a href="index.html" class="mobile-nav-item">
          <div class="mobile-nav-icon">🏠</div>
          <span class="mobile-nav-text">トップ</span>
        </a>
        <a href="diary.html" class="mobile-nav-item">
          <div class="mobile-nav-icon">📝</div>
          <span class="mobile-nav-text">ブログ</span>
        </a>
        <a href="about.html" class="mobile-nav-item">
          <div class="mobile-nav-icon">👶</div>
          <span class="mobile-nav-text">保育理念</span>
        </a>
        <a href="schedule.html" class="mobile-nav-item">
          <div class="mobile-nav-icon">⏰</div>
          <span class="mobile-nav-text">1日の流れ</span>
        </a>
        <a href="contact.php" class="mobile-nav-item">
          <div class="mobile-nav-icon">📞</div>
          <span class="mobile-nav-text">お問い合わせ</span>
        </a>
      </div>
    </div>
</div>
<script src="taniyama.js"></script>
<script src="sunny.js"></script>
<script src="https://cdn.jsdelivr.net/npm/lazyload@2.0.0-rc.2/lazyload.min.js"></script>
<script type="text/javascript">
  $(function(){
      var
      $container = $('#container');
  
      $(document).ready(function () {
          // メインビジュアル
          var mySwiper = new Swiper ('.introduction-history .swiper-container', {
              speed: 1000,
              loop: true,
              slidesPerView: 1,
              spaceBetween: 10,
              autoplay: {
                  delay: 5000,
                  disableOnInteraction: false
              },
              navigation: {
                  nextEl: '.introduction-history .swiper-button-next',
                  prevEl: '.introduction-history .swiper-button-prev'
              },
              pagination: {
                  el: '.swiper-pagination',
                  type: 'bullets',
                  clickable: true
              }
          })
      });
  
  });
</script>
<script>
  //ドロップダウンの設定を関数でまとめる
  function mediaQueriesWin(){
    var width = $(window).width();
    if(width <= 768) {//横幅が768px以下の場合
      $(".has-child>a").off('click'); //has-childクラスがついたaタグのonイベントを複数登録を避ける為offにして一旦初期状態へ
      $(".has-child>a").on('click', function() {//has-childクラスがついたaタグをクリックしたら
        var parentElem =  $(this).parent();// aタグから見た親要素の<li>を取得し
        $(parentElem).toggleClass('active');//矢印方向を変えるためのクラス名を付与して
        $(parentElem).children('ul').stop().slideToggle(500);//liの子要素のスライドを開閉させる※数字が大きくなるほどゆっくり開く
        return false;//リンクの無効化
      });
    }else{//横幅が768px以上の場合
      $(".has-child>a").off('click');//has-childクラスがついたaタグのonイベントをoff(無効)にし
      $(".has-child").removeClass('active');//activeクラスを削除
      $('.has-child').children('ul').css("display","");//スライドトグルで動作したdisplayも無効化にする
    }
  }

  // ページがリサイズされたら動かしたい場合の記述
  $(window).resize(function() {
    mediaQueriesWin();/* ドロップダウンの関数を呼ぶ*/
  });

  // ページが読み込まれたらすぐに動かしたい場合の記述
  $(window).on('load',function(){
    mediaQueriesWin();/* ドロップダウンの関数を呼ぶ*/
  });
</script>
<script>
  'use strict';

  {
    // DOM取得
    // 親メニューのli要素
    const parentMenuItem = document.querySelectorAll('.menu__item');
    console.log(parentMenuItem);
    // イベントを付加
    for (let i = 0; i < parentMenuItem.length; i++) {
      parentMenuItem[i].addEventListener('mouseover', dropDownMenuOpen);
      parentMenuItem[i].addEventListener('mouseleave', dropDownMenuClose);
    }
    // ドロップダウンメニューを開く処理
    function dropDownMenuOpen(e) {
      // 子メニューa要素
      const childMenuLink = e.currentTarget.querySelectorAll('.drop-menu__link');
      console.log(childMenuLink);

      // is-activeを付加
      for (let i = 0; i < childMenuLink.length; i++) {
        childMenuLink[i].classList.add('is-active');
      }

    }
    // ドロップダウンメニューを閉じる処理
    function dropDownMenuClose(e) {
      // 子メニューリンク
      const childMenuLink = e.currentTarget.querySelectorAll('.drop-menu__link');
      console.log(childMenuLink);

      // is-activeを削除
      for (let i = 0; i < childMenuLink.length; i++) {
        childMenuLink[i].classList.remove('is-active');
      }
    }
  }
</script>
<script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="  crossorigin="anonymous"></script>
<script src="https://coco-factory.jp/ugokuweb/wp-content/themes/ugokuweb/data/5-1-1/js/5-1-1.js"></script>

<script>
  // スマホ専用の下部ヘッダーの機能
  document.addEventListener('DOMContentLoaded', function() {
    // 現在のページに応じてアクティブな状態を設定
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    const mobileNavItems = document.querySelectorAll('.mobile-nav-item');
    
    mobileNavItems.forEach(item => {
      const href = item.getAttribute('href');
      if (href === currentPage || (currentPage === '' && href === 'index.html')) {
        item.classList.add('active');
      }
    });
    
    // スマホでのみ固定表示（スクロール制御を無効化）
    const mobileHeader = document.querySelector('.mobile-bottom-header');
    
    // スマホでのみ固定表示を維持
    if (window.innerWidth <= 768) {
      mobileHeader.style.transform = 'translateY(0)';
      mobileHeader.style.position = 'fixed';
      mobileHeader.style.bottom = '0';
    }
  });
</script>

<script>
// reCAPTCHA v3 token generation
function generateRecaptchaToken() {
  grecaptcha.ready(function() {
    grecaptcha.execute('6LfYourSiteKeyHere', {action: 'contact_form'}).then(function(token) {
      document.getElementById('recaptcha_token').value = token;
    });
  });
}

// Spam detection patterns
function detectSpamPatterns() {
  const name = document.querySelector('input[name="c-name"]').value;
  const email = document.querySelector('input[name="c-mail"]').value;
  const subject = document.querySelector('input[name="c-subject"]').value;
  const message = document.querySelector('textarea[name="c-message"]').value;
  
  // Common spam patterns
  const spamPatterns = [
    /credit.*account/i,
    /transfer.*here/i,
    /confirm.*transfer/i,
    /\$[\d,]+\.\d{2}/i,
    /http[s]?:\/\/[^\s]+/i,
    /click.*here/i,
    /verify.*account/i,
    /suspended.*account/i,
    /urgent.*action/i
  ];
  
  const allText = name + ' ' + email + ' ' + subject + ' ' + message;
  
  for (let pattern of spamPatterns) {
    if (pattern.test(allText)) {
      alert('スパムと検出された内容が含まれています。内容を確認してください。');
      return false;
    }
  }
  
  return true;
}

// Form validation with spam detection
document.addEventListener('DOMContentLoaded', function() {
  const form = document.querySelector('form[method="post"]');
  if (form) {
    form.addEventListener('submit', function(e) {
      // Check honeypot field
      const honeypot = document.querySelector('input[name="website"]').value;
      if (honeypot !== '') {
        e.preventDefault();
        return false;
      }
      
      // Check for spam patterns
      if (!detectSpamPatterns()) {
        e.preventDefault();
        return false;
      }
      
      // Generate reCAPTCHA token
      generateRecaptchaToken();
    });
  }
});
</script>
</body></html>
<style>

iframe {
  width: 100% !important;
}

.pconly a {
  color: #e8004a !important;
}

*, *::after, *::before {
  box-sizing: border-box;
}

body {
  margin: 0;
  padding: 0;
}

a {
  color: #333;
  text-decoration: none;
}

ul {
  padding: 0;
  margin: 0;
}

li {
  list-style: none;
  padding: 0;
  margin: 0;
}

/* main {
  width: 100%;
  margin: 0px auto 10px;
  background-color: lightcyan;
} */

.menu {
  display: flex;
  justify-content: center;
}

.menu__link {
  display: block;
  padding: 10px 20px;
}

.menu__link:hover {
  background-color: #88a6a9;
  color: #666;
}

.drop-menu {
  position: absolute;
  top: 43px;
  transition: all .3s;
}

.drop-menu__link {
  display: block;
  display: none;
  background-color: #88a6a9;
  transition: all .3s;
  padding: 5px 20px;
}

.drop-menu__link:hover {
  background-color: lightcyan;
}

/* ドロップダウン出現後のスタイル */
.drop-menu__link.is-active {
  display: block;
}

  .header {
    background: #88a6a9;
    padding-left: 0px;
  }

  .header.attraction::after {
    width: 0px !important;
  }

  .footer {
    background: #70c5c5;
    color: #e8004a !important;
  }

  .footer-nav a {
    color: #e8004a;
  }
  
  /* スマホ専用の下部ヘッダー */
  .mobile-bottom-header {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    width: 100%;
    background-color: #70c5c5;
    border-top: 1px solid #e0e0e0;
    z-index: 9999;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
    transform: translateY(0);
    transition: transform 0.3s ease;
  }

  .mobile-nav {
    display: flex;
    justify-content: space-around;
    align-items: center;
    padding: 8px 0;
    max-width: 100%;
  }

  .mobile-nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-decoration: none;
    color: #333;
    padding: 8px 4px;
    min-width: 60px;
    transition: all 0.3s ease;
    border-radius: 8px;
  }

  .mobile-nav-item:hover {
    background-color: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
  }

  .mobile-nav-item:active {
    transform: translateY(0);
    background-color: rgba(255, 255, 255, 0.3);
  }

  .mobile-nav-item.active {
    background-color: rgba(255, 255, 255, 0.3);
    color: #e8004a;
    font-weight: 600;
  }

  .mobile-nav-item.active .mobile-nav-icon {
    transform: scale(1.1);
  }

  .mobile-nav-icon {
    font-size: 20px;
    margin-bottom: 4px;
    line-height: 1;
  }

  .mobile-nav-text {
    font-size: 10px;
    font-weight: 500;
    text-align: center;
    line-height: 1.2;
    letter-spacing: 0.05em;
  }

  /* スマホでのみ表示 */
  @media only screen and (max-width: 768px) {
    .mobile-bottom-header {
      display: block !important;
      position: fixed !important;
      bottom: 0 !important;
      left: 0 !important;
      right: 0 !important;
      width: 100% !important;
    }
    
    /* スマホで下部ヘッダーがある分、bodyに余白を追加 */
    body {
      padding-bottom: 120px !important;
    }
    
    /* フッターの上に表示されるように調整 */
    .footer {
      margin-bottom: 120px;
      padding-bottom: 40px;
    }
    
    /* copyrightが隠れないように調整 */
    .footer .under {
      margin-bottom: 40px;
    }
  }

  /* タブレット以上では非表示 */
  @media only screen and (min-width: 769px) {
    .mobile-bottom-header {
      display: none !important;
    }
    
    body {
      padding-bottom: 0 !important;
    }
  }

  @media only screen and (max-width: 480px) {
    iframe {
      max-width: 100%;
    }
  }

  @media only screen and (max-width: 736px) {
    .for-large {
      display: none;
    }

    .for-small {
      display: block;
    }

    .home-attraction>.image {
      padding-right: 0 !important;
    }

    .home-attraction>.head {
      margin-bottom: 25px;
      padding: 0 20px;
      text-align: left;
    }

    .headding-primary .en {
      font-size: 35px;
      letter-spacing: .026em;
    }

    .headding-primary .ja {
      font-size: 11px;
    }

    .button-flat span {
      font-size: 13px;
    }

    .text-primary {
      font-size: 13px;
      text-align: justify;
      line-height: 2;
      letter-spacing: .08em;
    }

    .home-policy .content {
      width: 100%;
    }

    .toplogo {
      max-width: 20%;
    }

    video {
      max-height: 1200px;
    }

    .pconly {
      display: none !important;
    }

    .global-nav .inner .box>.wrap .list .child {
      display: block;
    }

    .global-nav>.wrapper {
      max-height: 100%;
      overflow-y: visible;
    }
  }


  @media only screen and (min-width: 1080px) {
    .hamburger {
      display: none;
    }
  }
</style>