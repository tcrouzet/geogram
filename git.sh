#!/bin/bash
#chmod +x git.sh
current_date=$(date +"%Y-%m-%d %H:%M:%S")
git add .
git commit -m "sync $current_date"
git push -u origin main

rsync -avz -e "ssh -i /Users/thierrycrouzet/Documents/python/aws/GeogramZefal.pem" ec2-user@ec2-35-180-41-206.eu-west-3.compute.amazonaws.com://var/www/html/geogram /Users/thierrycrouzet/Documents/python/geogramZefal

