#!/bin/bash
#chmod +x git.sh
#git config --global credential.helper store
current_date=$(date +"%Y-%m-%d %H:%M:%S")
git add .
git commit -m "sync $current_date"
git push -u origin main
