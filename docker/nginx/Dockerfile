FROM nginx:1.20-alpine

ENV TZ Asia/Tokyo

RUN chmod 755 -R /root

COPY ./docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# copy ssl key
COPY ./docker/nginx/ssl/server.crt /etc/nginx/server.crt
COPY ./docker/nginx/ssl/server.key /etc/nginx/server.key
