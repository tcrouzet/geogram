# Geogram

Geogram is a web app to track adventures.

Look an implementation at [geo.zefal.com](https://geo.zefal.com/).

You have to import app/database/schema.sql in a MySQL database, then set app/config/config.php file.


### Telegram (optional)

1. Open @BotFather
2. In BotFather, /setdomain@YourBotName yourWebApp (/setdomain@GeoBikepacking_bot https://geo.zefal.com/api/?view=telegram)

Finaly you have to open webhook.php from the web to activate your bot webhook.

### To Do

…

### Bugs

Bien tester publier depuis galerie photo… Fonctionne sur iOS. Semble qu'il y a problème sur Andrtoid.

### Warning

Impossible de sauver les photos prise dans la webapp dans la galerie (interfaçage interdit pour raison de sécurité).

### Done

2025-06-10 Add town and weather with cron every 10 minutes
2025-06-10 Many many changes
2025-06-05 One log per action (location, text, photo…)
