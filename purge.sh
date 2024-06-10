#!/bin/bash
#chmod +x purge.sh
sudo truncate -s 0 /var/www/html/geogram/logs/robot.log
sudo rm -f /var/www/html/geogram/logs/*.txt

