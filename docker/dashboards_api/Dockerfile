FROM nginx:alpine
MAINTAINER Andrew Taranik me@pureclouds.net

ARG BUILD_ENV

ADD docker/dashboards_api/nginx.conf /etc/nginx/nginx.conf
ADD docker/dashboards_api/symfony.conf.$BUILD_ENV /etc/nginx/conf.d/default.conf

RUN echo "upstream php-upstream { server php:9000; }" > /etc/nginx/conf.d/upstream.conf
RUN adduser -D -H -u 1000 www-data

EXPOSE 80 443

CMD ["nginx"]
