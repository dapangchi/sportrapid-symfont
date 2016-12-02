FROM busybox

ARG BUILD_ENV

RUN mkdir -p /var/www/symfony
ENV HOME /var/www/symfony
WORKDIR /var/www/symfony

# Copy authentication certificates.
RUN mkdir -p ./app/var/cert
COPY docker/dashboards_api_src/cert ./app/var/cert

# Copy all app binaries
COPY ./app ./app
COPY ./bin ./bin
COPY ./src ./src
COPY ./vendor ./vendor
COPY ./web ./web

COPY docker/dashboards_api_src/parameters.yml.$BUILD_ENV ./app/config/parameters.yml

VOLUME /log
VOLUME /var/log/snaprapid
