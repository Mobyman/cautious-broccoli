#!/bin/bash

mkdir -m 1777 -p /tmp/php && \
php composer.phar install && \

rm -rf /etc/supervisor/conf.d/firstrun.conf && \
rm -rf /tmp/firstrun.sh && \

supervisorctl update
