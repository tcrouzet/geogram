#!/bin/bash
#chmod +x purge.sh
sudo truncate -s 0 logs/robot.log
sudo rm -f logs/*.txt

# Find and sort the _backup_ files, then delete all but the latest one
backup_files=$(ls -1 _backup_*.sql | sort)
latest_backup=$(echo "$backup_files" | tail -n 1)
for file in $backup_files; do
    if [ "$file" != "$latest_backup" ]; then
        sudo rm -f "$file"
    fi
done

#Reset rights

sudo chmod 775 logs
sudo chown apache:apache logs

sudo chmod 775 userimg
sudo chown -R apache:apache userimg

sudo chmod 775 cron.txt
sudo chown apache:apache cron.txt
