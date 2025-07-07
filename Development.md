# テスト手順

## 環境構築

### wp-envを使用した環境構築

1. **依存関係のインストール**
   ```bash
   npm install
   ```

2. **wp-env環境の起動**
   ```bash
   npm run start
   ```
   
   **注意**: `npm run start`を実行すると、wp-env環境の起動後に自動的にBasic認証設定が適用されます。

3. **Basic認証の自動設定**
   - wp-adminディレクトリに自動でBasic認証が設定されます
   - 認証情報: `admin` / `admin`
   - スクリプト: `scripts/setup-auth.sh`

### 手動でBasic認証を設定する場合

```bash
# Basic認証設定のみ実行
npm run setup-auth
```

### 環境確認

- WordPressフロントエンド: http://localhost:8080
- WordPress管理画面: http://localhost:8080/wp-admin/ (Basic認証あり)
- プラグインディレクトリ: wp-content/plugins/taro-bypass-password-protection

## テスト手順

### 1. Basic認証の動作確認

```bash
# 認証なしでアクセス（401エラーが発生することを確認）
curl -I http://localhost:8080/wp-admin/

# Basic認証でアクセス（成功することを確認）
curl -I -u admin:admin http://localhost:8080/wp-admin/
```

### 2. プラグインの有効化

1. http://localhost:8080/wp-admin/ にアクセス
2. Basic認証ダイアログで `admin` / `admin` を入力
3. WordPress管理画面にログイン（初回は setup が必要）
4. プラグイン > インストール済みプラグイン で「Taro Bypass Password Protection」を有効化

### 3. パスワード保護投稿の作成

1. 投稿 > 新規追加
2. 投稿を作成
3. 公開設定で「パスワード保護」を選択
4. パスワードを設定（例：`test123`）
5. 投稿を公開

### 4. プラグインの動作確認

1. **Basic認証なしの環境での動作確認**
   - 通常のWordPress環境では、パスワード保護投稿のフォームが正常に動作することを確認

2. **Basic認証ありの環境での動作確認**
   - Basic認証下では、通常のパスワード保護フォームが機能しない
   - プラグインが有効化されていると、AJAXを通じてパスワード認証が行われる
   - 投稿ページでパスワード入力フォームが正常に動作することを確認

### 5. REST APIエンドポイントの確認

```bash
# プラグインのREST APIエンドポイントが利用可能か確認
curl -X POST http://localhost:8080/wp-json/taro-bypass-password-protection/v1/check-password \
  -H "Content-Type: application/json" \
  -d '{"post_id": 1, "password": "test123"}'
```

### 6. JavaScript動作確認

1. ブラウザの開発者ツールを開く
2. パスワード保護投稿ページでパスワードを入力
3. コンソールログでAJAXリクエストが実行されることを確認
4. 正しいパスワードを入力すると投稿内容が表示されることを確認

## 環境のクリーンアップ

```bash
# wp-env環境の停止
npm run stop

# wp-env環境の削除（完全にクリーンアップ）
npm run destroy
```

## トラブルシューティング

### docker-compose が見つからないエラー

wp-envが `docker-compose` コマンドを見つけられない場合：

```bash
# プロジェクトディレクトリにdocker-composeラッパーを作成
chmod +x docker-compose
PATH="$PWD:$PATH" npm run start
```

### Basic認証が動作しない場合

1. .htaccessファイルが正しく配置されているか確認
2. Apacheのmod_authが有効になっているか確認
3. ファイルの権限が適切に設定されているか確認

### プラグインが読み込まれない場合

1. .wp-env.jsonの`mappings`設定が正しいか確認
2. プラグインファイルがコンテナ内に正しくマップされているか確認
3. wp-env環境を再起動してみる

## 期待される動作

- Basic認証下でWordPressの管理画面にアクセスできる
- パスワード保護投稿がBasic認証環境でも正常に動作する
- プラグインのREST APIエンドポイントが機能する
- JavaScript（AJAX）を通じたパスワード認証が動作する
- CSSやJavaScriptファイルが正しく読み込まれる
