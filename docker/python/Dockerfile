FROM python:3.13

# 必要なパッケージをインストール
RUN apt-get update && apt-get install -y cron

# 作業ディレクトリを設定
WORKDIR /app

# 必要ファイルコピー
COPY ./docker/python/requirements.txt /app/requirements.txt

# 必要な依存関係のインストール
RUN pip install --no-cache-dir -r /app/requirements.txt


# cronを起動
CMD ["cron", "-f"]
