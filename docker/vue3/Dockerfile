# Vue 3 用のベースイメージを使用
FROM node:16-alpine

# 作業ディレクトリの設定
WORKDIR /app

# パッケージのインストール
RUN npm install -g @vue/cli --timeout=120000

# プロジェクト作成（package.json から始める）
COPY ./docker/vue3/package.json ./

# 必要な依存関係のインストール
RUN npm install

# 証明書をコピー
COPY ./vue3/certs /app/certs

# ポートの設定
EXPOSE 8080

# 開発サーバーを起動
CMD ["npm", "run", "serve", "--", "--https", "--cert", "certs/localhost.crt", "--key", "certs/localhost.key", "--port", "8080", "--host", "0.0.0.0"]