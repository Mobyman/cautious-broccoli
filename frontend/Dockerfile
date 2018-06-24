FROM debian:stretch

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update \
    && apt-get install --no-install-recommends --no-install-suggests -y \
       cron supervisor tzdata ca-certificates locales wget curl apt-transport-https git-core vim mc less \
    && mkdir -p /var/log/supervisor \
    && echo "Europe/Moscow" > /etc/timezone && dpkg-reconfigure tzdata \
    && echo "en_US.UTF-8 UTF-8" > /etc/locale.gen && locale-gen

ENV LANG="en_US.UTF-8" LC_ALL="en_US.UTF-8" TERM="xterm"

RUN apt-get update \
    && apt-get install --no-install-recommends --no-install-suggests -y nginx

RUN rm -rf /var/lib/apt/lists/* /var/cache

RUN useradd --uid 1001 -g www-data -s /bin/bash web -d /var/www/default \
    && mkdir -p /var/www/default

COPY deploy/conf/supervisor/*.conf /etc/supervisor/conf.d/
COPY deploy/conf/nginx/*.conf /etc/nginx/

WORKDIR /var/www/default

COPY deploy/entrypoint.sh /root/
RUN chmod +x /root/entrypoint.sh

EXPOSE 80

CMD ["/root/entrypoint.sh"]