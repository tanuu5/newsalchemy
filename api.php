<?php
// api.php - バックエンド部分
// ※ config.php は webroot 外に配置している場合は、絶対パスで require してください。
// 例: require '/var/www/secure/config.php';
require_once 'config.php';

// テストモード設定は config.php から取得

// レスポンスのContent-Typeをapplication/jsonに設定
header('Content-Type: application/json');

// 現在の日付と時刻を取得
$current_datetime = date('Y年m月d日 H:i:s');

// API キーは config.php で定義されている定数から取得
$api_key = OPENAI_API_KEY;

// APIリクエスト開始時の時刻を記録
$request_datetime = date('Y年m月d日 H:i:s');

// GET パラメータから複数のキーワードを取得（カンマ区切りで指定）
// 指定がなければデフォルトは "OpenAI,Anthropic,生成AI" とする
$keywords_input = isset($_GET['keywords']) ? $_GET['keywords'] : 'OpenAI,Anthropic,生成AI';
$keywords = array_filter(array_map('trim', explode(',', $keywords_input)));

// 複数キーワードに対応したプロンプトの作成
if (count($keywords) > 1) {
    $prompt = "{$current_datetime}時点での最新のAIに関するニュースについて、以下の各キーワードごとに個別の要約を作成してください。\n";
    $prompt .= "各キーワードごとに見出しと要約を番号付きで示してください。\n\n";
    $prompt .= "【キーワード】: " . implode(", ", $keywords);
} else {
    $prompt = "{$current_datetime}時点での最新のAIに関するニュースを、キーワード「" . $keywords[0] . "」に関して簡潔に要約してください。";
}

// API エンドポイント
$url = "https://api.openai.com/v1/responses";

// リクエストペイロード
$data = array(
    "model" => defined('MODEL_NAME') ? MODEL_NAME : "gpt-4o",
    "input" => $prompt,
    "tools" => array(
        array(
            "type" => "web_search_preview",
            "search_context_size" => "medium",
            "user_location" => array(
                "type" => "approximate",
                "country" => "JP"
            )
        )
    )
);
$payload = json_encode($data);

// 変数の初期化
$answer = "";
$raw_response = "";

// テストモードまたはAPI呼び出し
if (TEST_MODE) {
    // テストモードではローカルJSONファイルを読み込む
    $json_file = defined('TEST_DATA_FILE') ? TEST_DATA_FILE : 'test_data.json';
    if (file_exists($json_file)) {
        $response = file_get_contents($json_file);
        $raw_response = $response;
    } else {
        $raw_response = '{"error": "テストデータファイル (' . $json_file . ') が見つかりません"}';
    }
} else {
    // HTTP ヘッダー設定
    $headers = array(
        "Content-Type: application/json",
        "Authorization: Bearer " . $api_key
    );

    // cURL で POST リクエストを実行
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    // 生のレスポンスをデバッグ用に保存
    $raw_response = $response;
}

// API レスポンスをデコード
$result = json_decode($raw_response, true);

// 変数の初期化
$answer = "";
$annotations = [];

// "output" 配列内から回答テキストとアノテーションを抽出
if (isset($result["output"]) && is_array($result["output"])) {
    foreach ($result["output"] as $item) {
        if (isset($item["type"]) && $item["type"] === "message") {
            if (isset($item["content"]) && is_array($item["content"])) {
                foreach ($item["content"] as $content) {
                    if (isset($content["type"]) && $content["type"] === "output_text") {
                        $answer = $content["text"];
                        
                        // アノテーション情報も取得
                        if (isset($content["annotations"]) && is_array($content["annotations"])) {
                            $annotations = $content["annotations"];
                        }
                        
                        break 2; // 最初の回答を採用
                    }
                }
            }
        }
    }
}

// 単純に安全な処理を行う
try {
    // マークダウンの太字（**テキスト**）をHTMLの<strong>タグに変換
    $answer = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $answer);
    
    // 一般的なマークダウンリンク [text](url) を処理
    $answer = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank">$1</a>', $answer);
    
} catch (Exception $e) {
    // エラーが発生した場合はログに記録
    error_log('Error processing markdown: ' . $e->getMessage());
}

// 結果をJSONで返す
echo json_encode([
    'answer' => $answer,
    'keywords' => $keywords,
    'raw_response' => json_decode($raw_response),
    'timestamp' => $request_datetime // APIリクエスト時の時刻を使用
]);