<?php
/**
 * ajax
 * @return false
 */
function chatgpt_auto_article_wppost_ajax_handler()
{
    try {
        if ($_COOKIE['chatgpt_auto_csrf_token'] === $_POST["chatgpt_auto_csrf_token"]) {

            if (chatgpt_auto_article_wppost_ajax(strip_tags($_POST["title"]), strip_tags($_POST["keyword"]), strip_tags($_POST["datetime"]))) {
                print json_encode(["res" => "OK"]);
            } else {
                print json_encode(["res" => "error"]);
            }
        }else{
            print json_encode(["res" => "NG"]);
        }
    } catch (\Throwable $th) {
        //throw $th;
        // print $th->getMessage();
        print json_encode(["res" => "error"]);
    }
    wp_die();
}

/**
 * OpenAI APIを使用して記事を生成
 * @param string $title
 * @param string $keyword
 * @param string $datetime
 * @return false
 */
function chatgpt_auto_article_wppost_ajax(string $title = "", string $keyword = "", string $datetime = "")
{
    try {
        require "vendor/autoload.php";

        if (!$title) return false;
        if (!$keyword) return false;
        if (!$datetime) return false;

        $client = OpenAI::client(get_option("chatgpt-auto-article-group-api-key"));
        $result = $client->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ["role" => "system", "content" => 'あなたはブロガーです、キーワードを元に記事を書いてください。'],
                ["role" => "system", "content" => '出来れば500文字以上の記事を書いてください。'],
                ['role' => 'user', 'content' => $keyword],
            ]
        ]);
        if (isset($result->choices[0]->message->content)) {
            $textdata = explode("\n",$result->choices[0]->message->content);
            foreach($textdata as $key=>$val){
                $textdata[$key] = "<p>".$val."</p>";
            }
            $my_post = array(
                'post_title' => $title,
                'post_content' =>"<!-- wp:paragraph -->".implode("",$textdata)."<!-- /wp:paragraph -->",
                'post_status' => 'future',
                'post_author' => 1,
                'post_category' => array(0),
                'post_date' => $datetime
            );
            $post_id = wp_insert_post($my_post, null);
            return true;
        }
        return false;
    } catch (\Throwable $th) {
        //throw $th;
        //print $th->getMessage();
        return false;
    }
}
