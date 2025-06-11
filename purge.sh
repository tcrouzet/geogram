#!/bin/bash
#chmod +x purge.sh
sudo truncate -s 0 logs/robot.log
sudo truncate -s 0 logs/error_php.log
sudo rm -f logs/*.txt

# Find and sort the _backup_ files, then delete all but the latest one
backup_files=$(ls -1 app/database/_backup_*.sql | sort)
latest_backup=$(echo "$backup_files" | tail -n 1)
for file in $backup_files; do
    if [ "$file" != "$latest_backup" ]; then
        sudo rm -f "$file"
    fi
done

#Reset rights

sudo chmod 775 logs
sudo chown apache:apache logs

sudo chmod 775 userdata
sudo chown -R apache:apache userdata

# Nettoyer les logs anciens
sudo journalctl --vacuum-time=1d > /dev/null 2>&1

