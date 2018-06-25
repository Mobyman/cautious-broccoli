#!/bin/bash

npm install && npm run move-libs && \

rm -rf /etc/supervisor/conf.d/firstrun.conf && \
rm -rf /tmp/firstrun.sh && \

supervisorctl update
