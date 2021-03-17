<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <title>Install baserCMS</title>
    <style>
        .ng {font-weight: bold;color: red;}
        p {line-height: 1.7;}
    </style>
</head>

<body>
    <div style="width:80%;border:1px solid #ccc;border-radius:8px;padding:2rem;margin:2rem auto 0;">
        <h1>baserCMS Pre Installer</h1>
        <?= content() ?>
    </div>
</body>

</html>
<?php
function remove_baser() {
    $dirs = ['app', 'css', 'files', 'img', 'js', 'lib', 'theme', 'vagrant'];
    $files = ['.editorconfig', 'apple-touch-icon-precomposed.png', 'favicon.ico', 'index.php', 'INSTALL.md', 'LICENSE'];
    foreach ($dirs as $dir) {
        if (!is_dir(__DIR__ . '/' . $dir)) {
            continue;
        }
        exec(sprintf('rm -rf %s', __DIR__ . '/' . $dir));
    }
    foreach ($files as $file) {
        if (!is_file(__DIR__ . '/' . $file)) {
            continue;
        }
        exec(sprintf('rm -f %s', __DIR__ . '/' . $file));
    }
}

function postv($key, $default='') {
    if(!isset($_POST[$key])) {
        return $default;
    }
    return $_POST[$key];
}

function content()
{
    if (filemtime(__FILE__)+1800 < time()) {
        return '<p>時間切れです。必要な場合は当ファイルをアップロードし直してください。</p>';
    }
    if (postv('mode') === 'dl') {
        ob_start();
        dl_baser();
        return ob_get_clean();
    }
    if (postv('mode') === 'remove') {
        remove_baser();
        return 'baserCMSのファイルを削除しました。';
    }

    if (!delete_ok()) {
        return "<p> - " . basename(__FILE__) . " writable check ... <span class='ng'>NG</span> </p>"
        . "<div style='padding:20px;font-size:bold;font-size:150%'>Install error, Please check permission. </div>";
    }
    return sprintf(
        '<p>このインストーラーは30分間(%sまで)有効です。</p><form method="post" action="%s" >
    <label><input type="radio" name="mode" value="dl" checked /> ダウンロード</label><br>
    <label><input type="radio" name="mode" value="remove" /> 削除</label><br>
    <button type="submit" style="padding:20px;cursor:pointer;">実行</button></form>',
        date('H時M分', (filemtime(__FILE__)+1800)),
        basename(__FILE__)
    );
}

function put_htpasswd()
{
    $text = <<< END
<Files ~ "^\.(htaccess|htpasswd)$">
deny from all
</Files>
AuthUserFile {DIR}/.htpasswd
AuthGroupFile /dev/null
AuthName "Please enter your ID and password"
AuthType Basic
require valid-user 
order deny,allow

END;
    file_put_contents('.htaccess', str_replace('{DIR}', __DIR__, $text) . file_get_contents('.htaccess'));
}

function delete_ok()
{
    if (basename(__FILE__) !== 'install.php') {
        return false;
    }

    $f = __DIR__ . '/tmp' . md5(time());
    if (!@copy(__FILE__, $f)) {
        return false;
    }

    if (!@unlink(__FILE__)) {
        return false;
    }

    if (!@copy($f, __FILE__)) {
        return false;
    }

    if (!@unlink($f)) {
        return false;
    }

    return true;
}

function dl_baser()
{
    if (!class_exists('ZipArchive')) {
        echo "ZIP support error<br>";
        return;
    }

    if (!is_writable(__DIR__)) {
        echo "file permission error<br>";
        return;
    }

    $zip_path = __DIR__ . "/baser.zip";
    if (!is_file($zip_path)) {
        echo "file download start. <br>";
        $content = @file_get_contents("https://basercms.net/packages/download/basercms/latest_version");
        if ($content) {
            echo ("file download end. <br>");
            if (!@file_put_contents($zip_path, $content)) {
                echo "file save error. ($zip_path)<br>";
                return;
            }
            echo "file : $zip_path <br>";
        } else {
            echo "file download error. <br>";
        }
    }

    exec(sprintf('unzip %s', $zip_path));
    exec('mv ./basercms/* ./');
    exec('mv ./basercms/.htaccess ./');
    exec('rm -rf ./basercms');
    put_htpasswd();

    if (!@unlink($zip_path)) {
        echo "Delete error " . $zip_path . ".<br>";
    }
    unlink(__FILE__);
    
    echo '<p><a href="index.php">baserCMSのインストーラを開く</a></p>';
    echo '<p>ベーシック認証は <strong>username:basercms / password:55</strong> で。</p>';
    echo '<p>このファイルは自動的に削除されます。</p>';
}
