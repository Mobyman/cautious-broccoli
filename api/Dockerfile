FROM debian:stretch

ENV DEBIAN_FRONTEND noninteractive
ENV PHP_VER="7.2"

RUN apt-get update \
    && apt-get install --no-install-recommends --no-install-suggests -y \
       cron supervisor tzdata ca-certificates locales wget curl apt-transport-https git-core vim mc less \
    && mkdir -p /var/log/supervisor \
    && echo "Europe/Moscow" > /etc/timezone && dpkg-reconfigure tzdata \
    && echo "en_US.UTF-8 UTF-8" > /etc/locale.gen && locale-gen

ENV LANG="en_US.UTF-8" LC_ALL="en_US.UTF-8" TERM="xterm"

RUN wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg \
    && echo "deb https://packages.sury.org/php/ stretch main" | tee /etc/apt/sources.list.d/php.list

RUN apt-get update \
    && mkdir -p /run/php \
    && apt-get install --no-install-recommends --no-install-suggests -y \
    nginx php${PHP_VER}-cli php${PHP_VER}-fpm php${PHP_VER}-mysql php${PHP_VER}-memcache php${PHP_VER}-mbstring php${PHP_VER}-curl php${PHP_VER}-dom php${PHP_VER}-zip php${PHP_VER}-uuid

RUN rm -rf /var/lib/apt/lists/* /var/cache

RUN useradd --uid 1001 -g www-data -s /bin/bash web -d /var/www/default \
    && mkdir -p /var/www/default \
    && touch /var/log/php.error.log \
    && chown web:adm /var/log/php.error.log

COPY deploy/conf/php/ /etc/php/${PHP_VER}/
COPY deploy/conf/supervisor/*.conf /etc/supervisor/conf.d/
COPY deploy/conf/nginx/*.conf /etc/nginx/

WORKDIR /var/www/default

COPY deploy/entrypoint.sh /root/
RUN chmod +x /root/entrypoint.sh

CMD ["/root/entrypoint.sh"]