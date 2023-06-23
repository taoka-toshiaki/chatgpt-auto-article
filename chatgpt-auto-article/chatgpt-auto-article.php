<?php
/*
  Plugin Name: chatgpt-auto-article
  Plugin URI: https://taoka-toshiaki.com/
  Description: chatGPTで記事を量産する
  Version: 1.0.0
  Author: @toshiaki_taoka
  Author URI: https://twitter.com/toshiaki_taoka
 */

if (!defined('ABSPATH')) exit;
require "common/post-chatgpt.php";

add_action('admin_init', 'save_chatgpt_auto_article');
add_action('admin_menu', 'chatgpt_auto_article_menu');

function chatgpt_auto_article_menu()
{
    add_menu_page(
        'chatGPTで記事を量産する', // ページのタイトル
        'chatgpt-AutoArticle', // メニューのタイトル
        'manage_options', // 必要な権限
        'chatgpt-auto-article', // ページの識別子
        'chatgpt_auto_article_page', // ページのコールバック関数
    );
}

function save_chatgpt_auto_article()
{
    register_setting('chatgpt-auto-article-group', 'chatgpt-auto-article-group-api-key');
}

function chatgpt_auto_article_page()
{
    $toke_byte = openssl_random_pseudo_bytes(16);
    $csrf_token = bin2hex($toke_byte);
    setcookie('chatgpt_auto_csrf_token', $csrf_token, time() + 3600,"/",$_SERVER['SERVER_NAME']);

    $csrf_token = $_COOKIE['chatgpt_auto_csrf_token'];
    $chatgptApiKey = get_option("chatgpt-auto-article-group-api-key") ?: "";
?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <div class="container">
        <div class="row">
            <form action="options.php" method="post">
                <?php
                settings_fields('chatgpt-auto-article-group');
                do_settings_sections('chatgpt-auto-article-group');
                ?>
                <div class="col-12">
                    chatGPT_APIKEY:
                </div>
                <div class="col-12">
                    <input class="form-control" type="password" name="chatgpt-auto-article-group-api-key" value="<?= esc_attr($chatgptApiKey) ?>">
                </div>
                <?php submit_button(); ?>
            </form>
            <?php
            $i = 0;
            $datatime = new DateTime();
            while ($i < 7) {
                $datatime->modify('+1 days');
                $i++;
            ?>
                <input type="hidden" name="token" value="<?= $csrf_token ?>">
                <div class="col-12">

                    <span class="reslut"></span><br>投稿予定日:<?= $datatime->format('Y年m月d日') ?><input type="time" name="appt[]" required>
                    タイトル:
                    <input class="form-control" type="hidden" name="date[]" value="<?= $datatime->format('Y-m-d ') ?>">

                </div>
                <div class="col-12">
                    <input class="form-control" type="text" name="title[]" value="" required>
                </div>
                <div class="col-12">
                    生成させたい記事に対してのキーワード:
                </div>
                <div class="col-12">
                    <input class="form-control" type="text" name="keyword[]" value="" required>
                </div>
                <hr>
            <?php
            }
            ?>
            <button class="btn btn-success btn-lg " id="make" type="button" role="button">記事を量産する</button>
        </div>
    </div>
    <script>
        document.getElementById("make").addEventListener("click", make2023);

        function make2023() {
            document.querySelectorAll(".reslut").forEach(el => {
                el.innerHTML = "";
            });
            document.querySelectorAll("[name^=keyword]").forEach((el, keynum) => {
                let formData = new FormData();
                formData.append('chatgpt_auto_csrf_token', document.querySelector("[name^=token]").value);
                formData.append('title', document.querySelectorAll("[name^=title]")[keynum].value);
                formData.append('keyword', document.querySelectorAll("[name^=keyword]")[keynum].value);
                formData.append('datetime', document.querySelectorAll("[name^=date]")[keynum].value + document.querySelectorAll("[name^=appt]")[keynum].value + ":00");
                let init = {
                    method: 'POST',
                    body: formData,
                };
                fetch("https://<?= $_SERVER['SERVER_NAME'] ?>/wp-admin/admin-ajax.php?action=chatgpt_auto_article_wppost_action", init).then(response => response.json()).then(data => {
                    if (data.res === "OK") {
                        document.querySelectorAll(".reslut")[keynum].innerHTML = "■ 完了 ■";
                    }else{
                        document.querySelectorAll(".reslut")[keynum].innerHTML = "■ 失敗 ■";
                    }
                });
            });
        }
    </script>
<?php

}

add_action('wp_ajax_chatgpt_auto_article_wppost_action', 'chatgpt_auto_article_wppost_ajax_handler');
add_action('wp_ajax_nopriv_chatgpt_auto_article_wppost_action', 'chatgpt_auto_article_wppost_ajax_handler');
