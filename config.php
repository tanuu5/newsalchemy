<?php
// config.sample.php
// 実際に使用する前に、このファイルを「config.php」にリネームし、APIキーを設定してください。
// 【重要】このファイルには直接 API キーが記載されています。絶対に公開ディレクトリに配置しないでください。
// もし仕方なく公開ディレクトリに置く場合は、.htaccess 等で外部からの直接アクセスを禁止する設定を行ってください。

// OpenAI API設定
define('OPENAI_API_KEY', 'your_openai_api_key_here');

// テストモード設定
// true: OpenAI APIを呼び出さず、ローカルJSONファイル(test_data.json)を使用
// false: 実際にOpenAI APIを呼び出す
define('TEST_MODE', false);

// テスト用JSONファイルのパス設定（TEST_MODE=trueの場合に使用）
define('TEST_DATA_FILE', 'test_data.json');

// モデル設定
define('MODEL_NAME', 'gpt-4o-mini');

// デバッグ設定
// true: 詳細なエラー情報を表示
// false: 本番環境向けの簡易エラーメッセージを表示
define('DEBUG_MODE', false);

// タイムゾーン設定
date_default_timezone_set('Asia/Tokyo');

// 下記のコードは、直接このファイルにアクセスされた場合に内容を表示させないための対策です。
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access not permitted.');
}
?>