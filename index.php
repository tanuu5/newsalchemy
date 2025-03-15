<?php
// index.php - フロントエンド部分 v1.4
// ※ config.php は webroot 外に配置している場合は、絶対パスで require してください。
// 例: require '/var/www/secure/config.php';
require_once 'config.php';

// テストモード設定は config.php から取得

// 現在の日付と時刻を取得
$current_datetime = date('Y年m月d日 H:i:s');
$current_year = date('Y');

// URLからキーワードパラメータを取得（なければデフォルト値を使用）
$keywords_input = isset($_GET['keywords']) ? $_GET['keywords'] : 'OpenAI,Anthropic,生成AI';
$keywords = array_filter(array_map('trim', explode(',', $keywords_input)));
$keywords_string = implode(', ', $keywords);

// フォーム送信されたかどうかをチェック
$is_search_submitted = isset($_GET['search_submitted']) && $_GET['search_submitted'] === 'true';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NewsAlchemy | AI駆動型コンテンツ生成プラットフォーム</title>
    <style>
        :root {
            --primary-color: #3b82f6;
            --primary-dark: #2563eb;
            --secondary-color: #f8f9fa;
            --accent-color: #7c3aed;
            --accent-dark: #6d28d9;
            --text-color: #1e293b;
            --light-text: #64748b;
            --border-color: #e2e8f0;
            --success-color: #10b981;
            --loading-color: #ec4899;
            --steps-color: #0ea5e9;
            --warning-color: #f59e0b;
            --footer-bg: #f1f5f9;
            --header-bg: #f8fafc;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .site-header {
            background-color: var(--header-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);
        }
        
        .test-mode-banner {
            background-color: var(--warning-color);
            color: white;
            text-align: center;
            padding: 0.5rem;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .test-mode-banner a {
            color: white;
            text-decoration: underline;
            font-weight: bold;
        }
        
        .test-mode-banner a:hover {
            text-decoration: none;
        }
        
        .container {
            max-width: 850px;
            margin: 2rem auto;
            padding: 0 20px;
            flex: 1;
        }
        
        header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.5rem;
            width: 100%;
            text-align: center;
        }
        
        .logo-icon {
            margin-right: 0.5rem;
            color: var(--primary-color);
        }
        
        h1 {
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }
        
        .version {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
            font-size: 0.9rem;
            margin-left: 0.5rem;
            vertical-align: middle;
        }
        
        .subtitle {
            color: var(--light-text);
            font-size: 1.1rem;
            margin-top: 0.5rem;
            font-weight: 300;
            text-align: center;
            width: 100%;
            display: block;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 2rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        /* キーワードフォーム */
        .keywords-form {
            background: var(--secondary-color);
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .keywords-form label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .keywords-form input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 1rem;
        }
        
        .keywords-form button {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        
        .keywords-form button:hover {
            background: var(--primary-dark);
        }
        
        .keywords-display {
            background: #f0f7ff;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary-color);
        }
        
        .keywords-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        /* ステップコンテナ */
        .step-container {
            margin-bottom: 1.5rem;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            width: 100%; /* 常に親要素の幅いっぱいに */
        }
        
        .step-header {
            background: var(--primary-color);
            color: white;
            padding: 1rem 1.5rem;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }
        
        .step-header.active {
            background: var(--accent-color);
        }
        
        .step-header.completed {
            background: var(--steps-color);
        }
        
        .step-toggle {
            background: transparent;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
        }
        
        .step-content {
            padding: 0;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.5s ease, padding 0.5s ease;
            background: white;
            min-height: 0;
            position: relative;
        }
        
        .step-content.active {
            padding: 1.5rem;
            max-height: none; /* 最大高さの制限を解除 */
            display: flex;
            flex-direction: column;
            min-height: 300px; /* 最小高さを設定 */
            position: relative;
            width: 100%; /* 横幅を常に100%に維持 */
            box-sizing: border-box; /* パディングを含めた幅計算 */
        }
        
        .step-content.minimized {
            max-height: 100px;
            overflow: hidden;
            position: relative;
        }
        
        .step-content.minimized::after {
            content: "...";
            position: absolute;
            bottom: 0;
            right: 0;
            width: 100%;
            text-align: center;
            background: linear-gradient(transparent, white);
            padding: 10px 0;
        }
        
        /* ステップ内のコンテンツ */
        .result {
            background: var(--secondary-color);
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 5px solid var(--primary-color);
            line-height: 1.8;
            overflow-y: auto; /* 縦方向のスクロールを有効化 */
            max-height: 600px; /* 最大高さを設定 - 画面サイズに応じて調整可能 */
            margin-bottom: 1rem; /* ボタンとの間隔を確保 */
        }
        
        /* コンテンツの末尾に余白を追加してスクロール時にボタンと重ならないようにする */
        .result > *:last-child {
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
        }
        
        .loading {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 1rem;
            font-weight: 500;
            color: var(--loading-color);
            width: 100%; /* 常に親要素の幅いっぱいに */
            box-sizing: border-box;
        }
        
        #loading-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            box-sizing: border-box;
            padding: 1.5rem;
            min-width: 100%; /* 最小幅を親要素と同じに */
            opacity: 1;
            visibility: visible;
            transition: opacity 0.3s, visibility 0.3s;
        }
        
        #result-container {
            width: 100%;
            position: relative;
        }
        
        .loading-spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            border-top: 4px solid var(--loading-color);
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }
        
        .loading-text {
            font-size: 1.1rem;
        }
        
        .progress-container {
            width: 100%;
            max-width: 300px;
            margin-top: 15px;
            background-color: #f3f3f3;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 8px;
            background-color: var(--loading-color);
            width: 0%;
            border-radius: 10px;
            transition: width 0.3s ease;
        }
        
        @keyframes pulse {
            0% { opacity: 0.6; }
            50% { opacity: 1; }
            100% { opacity: 0.6; }
        }
        
        .loading-text-status {
            font-size: 0.9rem;
            margin-top: 10px;
            color: var(--light-text);
            animation: pulse 1.5s infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* アクションボタン */
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            position: sticky; /* 親要素内での固定位置 */
            bottom: 0; /* 下部に固定 */
            background: white; /* 背景色を設定してコンテンツと区別 */
            padding-top: 0.75rem; /* 上部の余白 */
            z-index: 10; /* 他の要素より前面に */
        }
        
        .action-button {
            background: var(--accent-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .action-button:hover {
            background: var(--accent-dark);
        }
        
        .action-button.secondary {
            background: var(--light-text);
        }
        
        .action-button.secondary:hover {
            background: #5a6268;
        }
        
        .output-format-select {
            padding: 0.75rem;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            font-size: 1rem;
            margin-right: 1rem;
        }
        
        /* コンテンツ結果コンテナのマージン調整 */
        #content-result-container {
            display: flex;
            flex-direction: column;
        }
        
        /* デバッグセクション */
        .debug-section {
            margin-top: 2rem;
            border-top: 1px solid var(--border-color);
            padding-top: 1.5rem;
        }
        
        .debug-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .debug-toggle {
            background: var(--accent-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: background 0.2s ease;
        }
        
        .debug-toggle:hover {
            background: var(--accent-dark);
        }
        
        .debug-content {
            background: #1e293b;
            color: #e2e8f0;
            padding: 1.5rem;
            border-radius: 8px;
            overflow-x: auto;
            display: none;
            margin-top: 1rem;
            white-space: pre-wrap;
            font-family: 'Monaco', monospace;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        
        .timestamp {
            color: var(--light-text);
            font-size: 0.95rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }
        
        .timestamp-icon {
            margin-right: 6px;
        }
        
        .test-mode-badge {
            display: inline-block;
            background-color: var(--warning-color);
            color: #fff;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 10px;
            vertical-align: middle;
        }
        
        .error-message {
            color: #e63946;
            background-color: rgba(230, 57, 70, 0.1);
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #e63946;
        }
        
        .welcome-message {
            padding: 2rem;
            text-align: center;
            font-size: 1.1rem;
            color: var(--light-text);
        }
        
        .welcome-message svg {
            margin-bottom: 1rem;
            color: var(--primary-color);
        }
        
        /* スクロールバーのスタイル (WebKit系ブラウザ向け) */
        .result::-webkit-scrollbar {
            width: 8px;
        }

        .result::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 8px;
        }

        .result::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 8px;
        }

        .result::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }
        
        /* フッタースタイル */
        .site-footer {
            background-color: var(--footer-bg);
            padding: 2rem 0;
            border-top: 1px solid var(--border-color);
            margin-top: 2rem;
            color: var(--light-text);
        }
        
        .footer-content {
            max-width: 850px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .footer-creator {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        
        .footer-tech-stack {
            margin-bottom: 1rem;
            text-align: center;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        
        .footer-tech-tag {
            display: inline-block;
            background: var(--secondary-color);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            margin: 0 0.25rem;
            font-size: 0.8rem;
            color: var(--text-color);
        }
        
        .footer-license {
            font-size: 0.85rem;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .footer-copyright {
            font-size: 0.85rem;
            text-align: center;
        }
        
        /* レスポンシブスタイル */
        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
                margin: 1rem auto;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .card {
                padding: 1.5rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .debug-buttons {
                flex-direction: column;
            }
            
            .result {
                max-height: 400px; /* モバイル向けに高さを調整 */
            }
            
            .footer-content {
                padding: 0 15px;
            }
        }
    </style>
</head>
<body>
    <?php if (TEST_MODE): ?>
    <div class="test-mode-banner">
        ソースコード全量はnoteで公開中。気になる方は <a href="https://note.com/tank_ai" target="_blank">@tank_ai</a> で検索ください。
    </div>
    <?php endif; ?>

    <div class="site-header">
        <div class="container">
            <div class="logo-container">
                <svg class="logo-icon" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10 2v7.31"></path>
                    <path d="M14 9.3V1.99"></path>
                    <path d="M8.5 2c0 5.52-2 8-7 9.67"></path>
                    <path d="M14.5 2c0 5.52 2 8 7 9.67"></path>
                    <path d="M12 22v-5.7"></path>
                    <path d="M12.85 13.12L18 16l-5.15 2.88.34-5.96"></path>
                    <path d="M11.15 13.12L6 16l5.15 2.88-.34-5.96"></path>
                </svg>
                <h1>NewsAlchemy</h1>
                <span class="version">v1.4</span>
                <?php if (TEST_MODE): ?>
                <span class="test-mode-badge">テストモード</span>
                <?php endif; ?>
            </div>
            <p class="subtitle">AI駆動型ニュース分析・コンテンツ生成プラットフォーム</p>
        </div>
    </div>

    <div class="container">
        <main class="card">
            <form class="keywords-form" method="GET" action="index.php">
                <label for="keywords">検索キーワード（カンマ区切りで複数指定可能）:</label>
                <input type="text" id="keywords" name="keywords" value="<?php echo htmlspecialchars($keywords_input); ?>" placeholder="例: OpenAI, Anthropic, 生成AI">
                <input type="hidden" name="search_submitted" value="true">
                <button type="submit">検索開始</button>
            </form>
            
            <?php if ($is_search_submitted): ?>
            <div class="keywords-display">
                <div class="keywords-title">現在の検索キーワード:</div>
                <div id="current-keywords"><?php echo htmlspecialchars($keywords_string); ?></div>
            </div>
            
            <div class="timestamp">
                <svg class="timestamp-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                取得時刻: <span id="current-datetime"><?php echo $current_datetime; ?></span>
            </div>
            
            <!-- ステップ1: ニュース要約 -->
            <div id="step1" class="step-container">
                <div class="step-header active">
                    <span>ステップ1: AIニュース分析</span>
                    <button class="step-toggle">▼</button>
                </div>
                <div class="step-content active">
                    <div id="loading-container" class="loading">
                        <div class="loading-spinner"></div>
                        <span class="loading-text">AIが最新情報を分析中...</span>
                        <div class="progress-container">
                            <div id="progress-bar" class="progress-bar"></div>
                        </div>
                        <div id="loading-text-status" class="loading-text-status">OpenAI APIに接続しています...</div>
                    </div>
                    
                    <div id="result-container" style="display:none;">
                        <div id="result-content" class="result">
                            <!-- APIからの結果がここに表示されます -->
                        </div>
                        
                        <div class="action-buttons">
                            <button id="next-step-button" class="action-button">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M5 12h14"></path>
                                    <path d="M12 5l7 7-7 7"></path>
                                </svg>
                                コンテンツ作成へ進む
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ステップ2: コンテンツ生成 -->
            <div id="step2" class="step-container">
                <div class="step-header">
                    <span>ステップ2: AIコンテンツ生成</span>
                    <button class="step-toggle">▼</button>
                </div>
                <div class="step-content">
                    <div class="output-selector">
                        <label for="output-format">出力フォーマット選択:</label>
                        <select id="output-format" class="output-format-select">
                            <option value="blog">ブログ記事</option>
                            <option value="newsletter">ニュースレター</option>
                            <option value="executive">エグゼクティブサマリー</option>
                            <option value="socialmedia">SNS投稿</option>
                            <option value="technical">技術レポート</option>
                        </select>
                        
                        <button id="generate-content-button" class="action-button">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M8 12h8"></path>
                                <path d="M12 8v8"></path>
                            </svg>
                            コンテンツを生成
                        </button>
                    </div>
                    
                    <div id="step2-loading" class="loading" style="display:none;">
                        <div class="loading-spinner"></div>
                        <span class="loading-text">AIがコンテンツを作成中...</span>
                        <div class="progress-container">
                            <div id="progress-bar-step2" class="progress-bar"></div>
                        </div>
                        <div id="loading-text-status-step2" class="loading-text-status">コンテンツを生成しています...</div>
                    </div>
                    
                    <div id="content-result-container" style="display:none;">
                        <div id="content-result" class="result">
                            <!-- 生成コンテンツがここに表示されます -->
                        </div>
                        
                        <div class="action-buttons">
                            <button id="content-copy-button" class="action-button secondary">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                    <path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"></path>
                                </svg>
                                コピー
                            </button>
                            <button id="content-download-button" class="action-button secondary">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"></path>
                                    <polyline points="7 10 12 15 17 10"></polyline>
                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                </svg>
                                ダウンロード
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- 初期状態のウェルカムメッセージ -->
            <div class="welcome-message">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p>検索キーワードを入力して「検索開始」ボタンをクリックすると、<br>AIが最新のニュースを分析し、様々なフォーマットのコンテンツを生成します。</p>
            </div>
            <?php endif; ?>
            
            <div class="debug-section">
                <div class="debug-buttons">
                    <button id="debug-toggle-step1" class="debug-toggle">ステップ1のデバッグ情報</button>
                    <button id="debug-toggle-step2" class="debug-toggle">ステップ2のデバッグ情報</button>
                </div>
                <div id="debug-content-step1" class="debug-content">
                    <!-- ステップ1のデバッグ情報がここに表示されます -->
                </div>
                <div id="debug-content-step2" class="debug-content">
                    <!-- ステップ2のデバッグ情報がここに表示されます -->
                </div>
            </div>
        </main>
    </div>

    <footer class="site-footer">
        <div class="footer-content">
            <div class="footer-creator">
                <span>制作：たぬ</span>
            </div>
            <div class="footer-tech-stack">
                <span>開発技術:</span>
                <span class="footer-tech-tag">PHP</span>
                <span class="footer-tech-tag">JavaScript</span>
                <span class="footer-tech-tag">OpenAI API</span>
                <span class="footer-tech-tag">ChatGPT</span>
                <span class="footer-tech-tag">Claude</span>
            </div>
            <div class="footer-license">
                このアプリケーションは <a href="https://opensource.org/licenses/MIT" target="_blank">MIT License</a> の下で公開されています。
                OpenAI API の利用に関しては <a href="https://openai.com/policies/terms-of-use" target="_blank">OpenAI の利用規約</a> に準拠しています。
            </div>
            <div class="footer-copyright">
                &copy; <?php echo $current_year; ?> NewsAlchemy. All Rights Reserved.
            </div>
        </div>
    </footer>
    
    <script>
        // ステップ管理のための変数
        let currentStep = 1;
        let newsData = null;
        let generatedContent = null;
        
        // ローディング状態を制御する変数
        let loadingProgress = 0;
        let loadingInterval;
        const loadingStatuses = [
            "OpenAI APIに接続しています...",
            "AIモデルが起動中...",
            "最新のニュースを取得中...",
            "情報を分析しています...",
            "要約を生成中..."
        ];
        
        // ステップ2のローディング状態
        let loadingProgressStep2 = 0;
        let loadingIntervalStep2;
        const loadingStatusesStep2 = [
            "コンテンツを生成しています...",
            "情報を整理しています...",
            "文章を構築中...",
            "仕上げの調整をしています...",
            "コンテンツを最終化しています..."
        ];
        
        // ローディングアニメーション
        function startLoadingAnimation(elementId, progressVar, statusArray, intervalVar) {
            let statusIndex = 0;
            
            // プログレスバーの更新
            return setInterval(() => {
                window[progressVar] += 1;
                
                // 状態テキストの更新
                if (window[progressVar] % 20 === 0) {
                    statusIndex = (statusIndex + 1) % statusArray.length;
                    document.getElementById(elementId).textContent = statusArray[statusIndex];
                }
                
                // プログレスバーは90%まで自動で進む
                if (window[progressVar] <= 90) {
                    document.getElementById(progressVar).style.width = window[progressVar] + '%';
                }
            }, 100);
        }
        
        // コンテンツをコピーする関数（Safariにも対応）
        function copyToClipboard(text) {
            // テキストエリアを作成して選択する方法（Safari対応）
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';  // ページ内のスクロールを防止
            textarea.style.opacity = '0';      // ユーザーには見えなくする
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();
            
            let successful = false;
            try {
                // モダンブラウザ向け
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(() => {
                        alert('クリップボードにコピーしました');
                    }).catch(err => {
                        // フォールバック
                        document.execCommand('copy');
                        alert('クリップボードにコピーしました');
                    });
                    successful = true;
                } else {
                    // 古いブラウザやSafari向け
                    successful = document.execCommand('copy');
                    if (successful) {
                        alert('クリップボードにコピーしました');
                    }
                }
            } catch (err) {
                console.error('コピーに失敗しました:', err);
                alert('コピーに失敗しました。手動でテキストを選択してコピーしてください。');
            }
            
            // テキストエリアを削除
            document.body.removeChild(textarea);
        }
        
        // ダウンロード関数
        function downloadContent(content, filename) {
            const element = document.createElement('a');
            const file = new Blob([content], {type: 'text/plain'});
            element.href = URL.createObjectURL(file);
            element.download = filename;
            document.body.appendChild(element);
            element.click();
            document.body.removeChild(element);
        }
        
        // ステップヘッダーのトグル機能
        function setupStepToggle() {
            const stepHeaders = document.querySelectorAll('.step-header');
            
            stepHeaders.forEach(header => {
                header.addEventListener('click', function() {
                    const stepContent = this.nextElementSibling;
                    const isActive = stepContent.classList.contains('active');
                    
                    if (isActive) {
                        stepContent.classList.remove('active');
                        this.querySelector('.step-toggle').textContent = '▲';
                    } else {
                        stepContent.classList.add('active');
                        this.querySelector('.step-toggle').textContent = '▼';
                    }
                });
            });
        }
        
        // ステップ1のデータを取得する関数
        async function fetchData() {
            try {
                // 現在のURLからキーワードクエリパラメータを取得
                const urlParams = new URLSearchParams(window.location.search);
                const keywords = urlParams.get('keywords');
                
                // APIリクエスト
                let apiUrl = 'api.php';
                if (keywords) {
                    apiUrl = `api.php?keywords=${encodeURIComponent(keywords)}`;
                }
                
                const response = await fetch(apiUrl);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                newsData = data; // グローバル変数に保存
                
                // ローディングを100%に
                loadingProgress = 100;
                document.getElementById('progress-bar').style.width = '100%';
                
                clearInterval(loadingInterval);
                
                // 少し待ってローディング表示を消す
                setTimeout(() => {
                    // 結果の表示（表示切替時にレイアウト崩れを防ぐ）
                    document.getElementById('loading-container').style.opacity = '0';
                    document.getElementById('loading-container').style.visibility = 'hidden';
                    document.getElementById('result-container').style.display = 'block';
                    document.getElementById('result-container').style.width = '100%';
                    
                    if (data.answer) {
                        document.getElementById('result-content').innerHTML = data.answer.replace(/\n/g, '<br>');
                    } else {
                        document.getElementById('result-content').innerHTML = '<div class="error-message">結果が取得できませんでした。再読み込みしてもう一度お試しください。</div>';
                    }
                    
                    // 取得時刻を設定
                    if (data.timestamp) {
                        document.getElementById('current-datetime').textContent = data.timestamp;
                    }
                    
                    // キーワード情報を表示（APIからの返却値があれば）
                    if (data.keywords && Array.isArray(data.keywords)) {
                        document.getElementById('current-keywords').textContent = data.keywords.join(', ');
                    }
                    
                    // デバッグ用ステップ1情報
                    if (data && data.raw_response !== undefined) {
                        // APIからのJSONレスポンスをそのまま表示（構造化された形式で）
                        try {
                            const jsonObj = typeof data.raw_response === 'string' 
                                ? JSON.parse(data.raw_response) 
                                : data.raw_response;
                            document.getElementById('debug-content-step1').textContent = JSON.stringify(jsonObj, null, 2);
                        } catch (e) {
                            // JSONパースエラー時は文字列としてそのまま表示
                            document.getElementById('debug-content-step1').textContent = 
                                typeof data.raw_response === 'string' 
                                    ? data.raw_response 
                                    : JSON.stringify(data.raw_response);
                        }
                    }
                    
                    // ステップ1のヘッダーを「完了」状態に
                    document.querySelector('#step1 .step-header').classList.add('completed');
                }, 500);
                
            } catch (error) {
                console.error('Error fetching data:', error);
                document.getElementById('loading-container').style.display = 'none';
                document.getElementById('result-container').style.display = 'block';
                document.getElementById('result-content').innerHTML = `
                    <div class="error-message">
                        データの取得中にエラーが発生しました。<br>
                        ${error.message}<br>
                        再読み込みしてもう一度お試しください。
                    </div>
                `;
            }
        }
        
        // ステップ2のコンテンツ生成関数
        async function generateContent() {
            try {
                // ステップ1の結果がなければエラー
                if (!newsData || !newsData.answer) {
                    throw new Error('ニュース要約データがありません。ステップ1を完了してください。');
                }
                
                // ローディング表示
                document.getElementById('step2-loading').style.display = 'flex';
                document.querySelector('.output-selector').style.display = 'none';
                document.getElementById('content-result-container').style.display = 'none';
                
                // ローディングアニメーション開始
                loadingProgressStep2 = 0;
                loadingIntervalStep2 = startLoadingAnimation(
                    'loading-text-status-step2', 
                    'loadingProgressStep2', 
                    loadingStatusesStep2, 
                    'loadingIntervalStep2'
                );
                
                // 選択された出力フォーマット
                const outputFormat = document.getElementById('output-format').value;
                
                // APIリクエスト
                const response = await fetch('content_generator.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        newsData: newsData,
                        format: outputFormat
                    })
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                generatedContent = data; // グローバル変数に保存
                
                // ローディングを100%に
                loadingProgressStep2 = 100;
                document.getElementById('progress-bar-step2').style.width = '100%';
                
                clearInterval(loadingIntervalStep2);
                
                // 少し待ってローディング表示を消す
                setTimeout(() => {
                    // 結果の表示
                    document.getElementById('step2-loading').style.display = 'none';
                    
                    // コンテンツ表示エリアを表示
                    const contentResultContainer = document.getElementById('content-result-container');
                    contentResultContainer.style.display = 'block';
                    
                    if (data.content) {
                        const contentResult = document.getElementById('content-result');
                        contentResult.innerHTML = data.content.replace(/\n/g, '<br>');
                        
                        // 長いコンテンツの場合はスクロールバーを表示
                        if (contentResult.scrollHeight > 600) {
                            // コンテンツが長い場合の処理
                            contentResult.style.overflowY = 'auto';
                            contentResult.style.maxHeight = '600px';
                            
                            // スクロール最上部に移動
                            contentResult.scrollTop = 0;
                        }
                    } else {
                        document.getElementById('content-result').innerHTML = '<div class="error-message">コンテンツを生成できませんでした。もう一度お試しください。</div>';
                    }
                    
                    // アクションボタンを確実に表示
                    const actionButtons = document.querySelector('#content-result-container .action-buttons');
                    if (actionButtons) {
                        actionButtons.style.display = 'flex';
                    }
                    
                    // デバッグ用ステップ2情報
                    document.getElementById('debug-content-step2').textContent = JSON.stringify(data, null, 2);
                    
                    // ステップ2のヘッダーを「完了」状態に
                    document.querySelector('#step2 .step-header').classList.add('completed');
                }, 500);
                
            } catch (error) {
                console.error('Error generating content:', error);
                document.getElementById('step2-loading').style.display = 'none';
                document.querySelector('.output-selector').style.display = 'block';
                document.getElementById('content-result-container').style.display = 'block';
                document.getElementById('content-result').innerHTML = `
                    <div class="error-message">
                        コンテンツの生成中にエラーが発生しました。<br>
                        ${error.message}<br>
                        もう一度お試しください。
                    </div>
                `;
            }
        }
        
        // 次のステップに進む処理
        function goToNextStep() {
            // ステップ1を最小化
            const step1Content = document.querySelector('#step1 .step-content');
            step1Content.classList.remove('active');
            document.querySelector('#step1 .step-toggle').textContent = '▲';
            
            // ステップ2をアクティブに
            const step2Header = document.querySelector('#step2 .step-header');
            const step2Content = document.querySelector('#step2 .step-content');
            
            step2Header.classList.add('active');
            step2Content.classList.add('active');
            document.querySelector('#step2 .step-toggle').textContent = '▼';
            
            currentStep = 2;
        }
        
        // ページ読み込み時の処理
        document.addEventListener('DOMContentLoaded', function() {
            // ステップトグル機能の設定
            setupStepToggle();
            
            // 検索が送信された場合のみデータ取得
            <?php if ($is_search_submitted): ?>
            // ローディングアニメーションの開始
            loadingInterval = startLoadingAnimation(
                'loading-text-status', 
                'loadingProgress', 
                loadingStatuses, 
                'loadingInterval'
            );
            
            // データ取得の開始
            fetchData();
            <?php endif; ?>
            
            // 次のステップボタンのイベントリスナー
            document.getElementById('next-step-button')?.addEventListener('click', goToNextStep);
            
            // コンテンツ生成ボタンのイベントリスナー
            document.getElementById('generate-content-button')?.addEventListener('click', generateContent);
            
            // コピーボタンのイベントリスナー
            document.getElementById('content-copy-button')?.addEventListener('click', function() {
                if (generatedContent && generatedContent.content) {
                    copyToClipboard(generatedContent.content);
                }
            });
            
            // ダウンロードボタンのイベントリスナー
            document.getElementById('content-download-button')?.addEventListener('click', function() {
                if (generatedContent && generatedContent.content) {
                    const format = document.getElementById('output-format').value;
                    const formatNames = {
                        'blog': 'ブログ記事',
                        'newsletter': 'ニュースレター',
                        'executive': 'エグゼクティブサマリー',
                        'socialmedia': 'SNS投稿',
                        'technical': '技術レポート'
                    };
                    
                    const filename = `NewsAlchemy_${formatNames[format] || format}_${new Date().toISOString().slice(0, 10)}.txt`;
                    downloadContent(generatedContent.content, filename);
                }
            });
            
            // デバッグ情報の表示/非表示切り替え（ステップ1）
            document.getElementById('debug-toggle-step1').addEventListener('click', function() {
                var debugContent = document.getElementById('debug-content-step1');
                if (debugContent.style.display === 'none' || debugContent.style.display === '') {
                    debugContent.style.display = 'block';
                    this.textContent = 'ステップ1のデバッグ情報を隠す';
                } else {
                    debugContent.style.display = 'none';
                    this.textContent = 'ステップ1のデバッグ情報';
                }
            });
            
            // デバッグ情報の表示/非表示切り替え（ステップ2）
            document.getElementById('debug-toggle-step2').addEventListener('click', function() {
                var debugContent = document.getElementById('debug-content-step2');
                if (debugContent.style.display === 'none' || debugContent.style.display === '') {
                    debugContent.style.display = 'block';
                    this.textContent = 'ステップ2のデバッグ情報を隠す';
                } else {
                    debugContent.style.display = 'none';
                    this.textContent = 'ステップ2のデバッグ情報';
                }
            });
        });
    </script>
</body>
</html>