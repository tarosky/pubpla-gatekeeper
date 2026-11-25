#!/bin/bash

echo "🔐 Basic認証設定を開始します..."

# 不要なプラグインを削除する
echo "🗑️  不要なプラグインを削除中..."
npm run cli plugin delete hello 2>/dev/null || echo "hello プラグインは既に削除済みです"

# パーマリンク設定（WordPressが.htaccessを生成）
echo "🔗 パーマリンク設定を更新中..."
npm run cli option update permalink_structure '/%postname%/'
npm run cli rewrite flush

# WordPressが.htaccessを生成するまで少し待つ
sleep 2

# 現在の.htaccessファイルを確認
echo "📄 現在の.htaccessファイルを確認中..."
npm run cli bash -c "cat /var/www/html/.htaccess" 2>/dev/null || echo "（まだ.htaccessファイルが生成されていません）"

# .htpasswdファイルの存在確認
if [ ! -f ".htpasswd" ]; then
    echo "❌ .htpasswdファイルが見つかりません"
    exit 1
fi

# wp-envコンテナ名を取得
WP_CONTAINER=$(docker ps --filter "name=wordpress-1" --format "{{.Names}}" | grep -E "^[a-f0-9]+-wordpress-1$" | head -1)

if [ -z "$WP_CONTAINER" ]; then
    echo "❌ wp-envコンテナが見つかりません。wp-envが起動していることを確認してください。"
    exit 1
fi

echo "📦 コンテナ名: $WP_CONTAINER"

# セパレータの定義
START_MARKER="# //pubpla-gatekeeper START"
END_MARKER="# //pubpla-gatekeeper END"

# wp-login.php保護ルールの定義
PROTECTION_RULES="$START_MARKER
<Files \"wp-login.php\">
    AuthType Basic
    AuthName \"WordPress Admin Area\"
    AuthUserFile /var/www/html/.htpasswd
    Require valid-user
</Files>
$END_MARKER"

# .htaccessファイルの処理
echo "🔍 .htaccessファイルを処理中..."

# .htaccessファイルが存在するかチェック
HTACCESS_EXISTS=$(docker exec "$WP_CONTAINER" test -f /var/www/html/.htaccess && echo "true" || echo "false")

if [ "$HTACCESS_EXISTS" = "true" ]; then
    echo "📄 既存の.htaccessファイルを確認中..."
    
    # セパレータが既に存在するかチェック
    MARKER_EXISTS=$(docker exec "$WP_CONTAINER" bash -c "grep -q '$START_MARKER' /var/www/html/.htaccess && echo 'true' || echo 'false'")
    
    if [ "$MARKER_EXISTS" = "true" ]; then
        echo "🔄 既存のpubpla-gatekeeper設定を更新中..."
        
        # 既存のセパレータ間の内容を削除して、新しいルールを挿入
        docker exec "$WP_CONTAINER" bash -c "
            # 一時ファイルを作成
            sed '/$START_MARKER/,/$END_MARKER/d' /var/www/html/.htaccess > /tmp/htaccess_temp
            
            # 新しいルールを追加
            cat /tmp/htaccess_temp > /var/www/html/.htaccess
            echo '' >> /var/www/html/.htaccess
            echo '$PROTECTION_RULES' >> /var/www/html/.htaccess
            
            # 一時ファイルを削除
            rm /tmp/htaccess_temp
        "
    else
        echo "🔒 .htaccessファイルにwp-login.php保護ルールを追加中..."
        
        # 既存の.htaccessファイルに追記
        docker exec "$WP_CONTAINER" bash -c "
            echo '' >> /var/www/html/.htaccess
            echo '$PROTECTION_RULES' >> /var/www/html/.htaccess
        "
    fi
else
    echo "🔒 新しい.htaccessファイルを作成中..."
    
    # 新しい.htaccessファイルを作成
    docker exec "$WP_CONTAINER" bash -c "
        echo '$PROTECTION_RULES' > /var/www/html/.htaccess
    "
fi

# 設定後の.htaccessファイルを表示
echo "📄 設定後の.htaccessファイル:"
docker exec "$WP_CONTAINER" bash -c "cat /var/www/html/.htaccess"

# 設定確認
echo "✅ Basic認証設定が完了しました！"
echo "🌐 保護対象: http://localhost:8888/wp-login.php (ログインページのみ)"
echo "🔓 フロントエンド: http://localhost:8888/ (保護されていません)"
echo "🔐 認証情報: admin / admin"

# 動作確認
echo "📝 動作確認中..."

# フロントエンドの確認（保護されていないことを確認）
echo "🔍 フロントエンドアクセス確認中..."
HTTP_STATUS_FRONT=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8888/)
if [ "$HTTP_STATUS_FRONT" = "200" ]; then
    echo "✅ フロントエンドは正常にアクセス可能です"
else
    echo "⚠️  フロントエンドのアクセスに問題があります（HTTP: $HTTP_STATUS_FRONT）"
fi

# wp-login.phpの確認
echo "🔍 wp-login.php保護確認中..."
HTTP_STATUS_LOGIN=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8888/wp-login.php)
if [ "$HTTP_STATUS_LOGIN" = "401" ]; then
    echo "✅ wp-login.php Basic認証が正常に動作しています"
else
    echo "⚠️  wp-login.php Basic認証の設定に問題がある可能性があります（HTTP: $HTTP_STATUS_LOGIN）"
fi

# Basic認証でのアクセス確認
echo "🔍 Basic認証でのアクセス確認中..."
HTTP_STATUS_LOGIN_AUTH=$(curl -s -o /dev/null -w "%{http_code}" -u admin:admin http://localhost:8888/wp-login.php)
if [ "$HTTP_STATUS_LOGIN_AUTH" = "200" ]; then
    echo "✅ Basic認証でのwp-login.phpアクセスが正常に動作しています"
else
    echo "⚠️  Basic認証でのwp-login.phpアクセスに問題があります（HTTP: $HTTP_STATUS_LOGIN_AUTH）"
fi

echo "🎉 Basic認証設定完了！"
