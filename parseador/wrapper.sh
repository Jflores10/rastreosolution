#!/bin/bash

export NVM_DIR=/root/.nvm
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"  # This loads nvm
[ -s "$NVM_DIR/bash_completion" ] && \. "$NVM_DIR/bash_completion"  # This loads nvm bash_completion

/root/.nvm/versions/node/v12.13.1/bin/node /var/www/html/parseador/trackingsystem.js
