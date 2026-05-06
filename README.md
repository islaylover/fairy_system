# Fairy System

OpenAI APIの利用額制限付きリクエスト管理システム。


## Screenshots

### Login

![Login](docs/images/login.png)

### Dashboard

![Dashboard](docs/images/request.png)


---

## 1. 概要

本システムは以下を目的とする。

* Laravel 12 + Vue 3 による SPA 構築の学習
* Python による OpenAI API バッチ処理の実装
* Docker による統合開発環境の構築

---

## 2. システム構成

| 種別    | 技術                   |
| ----- | -------------------- |
| API   | Laravel 12 / PHP 8.2 |
| Front | Vue 3                |
| Batch | Python 3.13          |
| DB    | MySQL 8              |
| Cache | Redis                |
| Web   | Nginx                |
| Mail  | Mailpit              |

---

## 3. OpenAI API 利用コスト計算

OpenAI API の利用額は以下の式で算出する。

```
利用額($) =
(input_tokens / 1,000,000) × input単価
+
(output_tokens / 1,000,000) × output単価
```

※ output token は生成処理（推論）が発生するため高コスト
※ input単価 < output単価 となるのが一般的

単価は以下で管理する：

* `laravel/config/chatgpt.php`

---

## 4. 利用制限

以下の制限を設ける。

* リクエスト単位の最大トークン数
* 日次トークン制限
* ユーザーごとの月間利用額制限
* システム全体の月間利用額制限

---

### 利用制限の設定

`.env` と `laravel/config/chatgpt.php` で管理する。

```env
CHATGPT_DAILY_MAX_TOKENS=0
CHATGPT_MONTHLY_USER_LIMIT_USD=0.00500
CHATGPT_MONTHLY_GLOBAL_LIMIT_USD=10.00000
```

| 設定値                              | 内容                   |
| -------------------------------- | -------------------- |
| CHATGPT_DAILY_MAX_TOKENS         | 1日あたりの総トークン上限（0=無制限） |
| CHATGPT_MONTHLY_USER_LIMIT_USD   | ユーザーごとの月間利用額上限       |
| CHATGPT_MONTHLY_GLOBAL_LIMIT_USD | システム全体の月間利用額上限       |

---

### モデル単価設定

```php
'price_per_million_tokens' => [
    'input' => 2.5,
    'output' => 10.0,
],
```

---

## 🚀 5. セットアップ（初回のみ）

### 5.1 .env 作成

プロジェクト直下に `.env` を作成する。

```env
OPENAI_API_KEY=your_api_key_here

BATCH_API_KEY=super-secret-batch-key
BATCH_BASE_URL=http://nginx

DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=fairy_db
DB_USERNAME=user
DB_PASSWORD=password

CHATGPT_DAILY_MAX_TOKENS=0
CHATGPT_MONTHLY_USER_LIMIT_USD=0.00500
CHATGPT_MONTHLY_GLOBAL_LIMIT_USD=10.00000
```

※ `.env` は Git に含めないこと

---

## 🔑 OpenAI API Key 設定

本システムは OpenAI API を使用します。

### 1. APIキー取得

https://platform.openai.com/

から API Key を発行します。

---

### 2. .env に設定

```env
OPENAI_API_KEY=your_api_key_here
```

---

### 3. 注意

* APIキーは絶対に公開しないこと
* `.env` はGit管理しないこと

---

### 5.2 Docker 起動

```bash
docker compose up -d --build
```

---

### 5.3 Laravel 初期化

```bash
docker compose exec web_app bash

composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

---

### 5.4 Vue 初期化（必要な場合のみ）

```bash
docker compose exec vue3_frontend sh
npm install
```

---

### 5.5 Python 初期化

不要（Docker build 時に完了）

---

## ▶ 6. 起動方法（日常運用）

```bash
docker compose up -d
```

---

## 🤖 7. バッチ実行

```bash
docker compose exec python_batch bash
python main.py
```

---

## 🌐 8. アクセス

### Web

https://fairy_system.com

### Mail（Mailpit）

http://localhost:8025

---

## 🛠 9. よく使うコマンド

### 停止

```bash
docker compose down
```

### 再ビルド

```bash
docker compose up -d --build
```

### ログ確認

```bash
docker compose logs -f
```

---

## 🔐 10. SSL証明書について

自己署名証明書は各自で生成してください。

```bash
openssl req -x509 -nodes -days 365 \
  -newkey rsa:2048 \
  -keyout docker/nginx/ssl/server.key \
  -out docker/nginx/ssl/server.crt
```

※ `server.key`（秘密鍵）はGitに含めないこと

---

## ⚠ 11. 注意点

### Laravel

`config/chatgpt.php` に自分のOpen AI APIで許容する制限額を定義

### Python

Open AI API関連の設定情報[制限額、MAXトークン数等]は Laravel API から取得している

```
GET /api/batch/chatgpt/config
```

---

## 💡 Tips

* OpenAIは「outputコストが高い」
* 利用制限は必ずテストすること
* バッチは「止まる設計」が重要
