# {GEONAME}

{GEONAME} est une web app pour partager et documenter des **aventures** en semi-temp réel.
Les **aventuriers** se géolocalisent, publient des photos et des commentaires.
Les **spectateurs** suivent leur progression sur la carte.
{GEONAME} est une instance de [Geogram](https://github.com/tcrouzet/geogram), distribuée en open source par [Thierry Crouzet](https://tcrouzet.com/) et développée pour éviter des solutions de tracking payantes (en Europe, la couverture mobile est désormais quasi universelle et les services de secours peuvent repérer n'importe quel mobile à quelques mètres près).  

## Web app

Contrairement à une app classique installée via un store, une web app s'utilise dans le navigateur web du mobile. Pour une meilleure ergonomie, on ajoute la web app à l'écran d'accueil du mobile. Sur iOS, ouvrir {GEONAME} avec un navigateur, puis bouton de partage, menu "Sur l'écran d'accueil". Sur Android, ouvrir {GEONAME} avec un navigateur, puis clic sur les trois points et sélection "Écran d'accueil".

## Spectateurs (non enregistrés)

1. Même déconnecté de {GEONAME}, on peut suivre [les routes publiques](/routes).
1. Une fois sur une route, on découvre la carte avec les dernières positions des aventuriers.
1. <i class="fas fa-book"></i> affiche le mode story : détails de toutes les localisations. Un clic sur une photo bascule en mode diaporama.
1. <i class="fas fa-list"></i> affichage en mode liste : par défaut, dernières positions de tous les aventuriers.
1. En mode liste ou story, cliquer sur le nom d'un aventurier affiche son historique. Un lien avec son nom apparaît au-dessus de la carte.
1. <i class="fas fa-map-marker-alt"></i> renvoie sur la carte à la position exacte (soit sur la carte générale, soit sur celle de l'aventurier s'il a été précédemment sélectionné).
1. <i class="fas fa-map"></i> retour à la carte soit de tous les aventuriers, soit de l'aventurier sélectionné.
1. <i class="fas fa-expand-arrows-alt"></i> affiche la route et tous les aventuriers sur la carte (mode par défaut).
1. <i class="fas fa-compress"></i> zoome uniquement sur les aventuriers ou toutes les positions de l'aventurier sélectionné.

## Spectateurs (enregistrés)

1. [S'enregister via Google ou avec un email.](/login)
1. [Personnalisation du profil](/user) (ajout d'une photo, modification du nom affiché, effacer son historique…).
1. [Connexion à une route.](/routes) [La page d'accueil](/) de {GEONAME} affiche cette route automatiquement.
1. Possibilité de créer des routes.
1. Possibilité d'être aventurier.

## Aventuriers

1. [S'enregister via Google ou avec un email.](/login)
2. [Rejoindre une route publique](/routes) ou accepter une invitation sous forme de lien hypertexte vers route privée ou semi-privée.
1. <i class="fas fa-images"></i> publier plusieurs photos en même temps.
1. <i class="fas fa-map-marker-alt"></i> se géolocaliser.
1. <i class="fas fa-camera"></i> publier une photo.
1. En mode story, possibilité de commenter les géolocalisations et les photos, comme de les supprimer.

## Route administrateur

1. [Création de nouvelles routes.](/routes)
1. Bouton "Edit" permet de paramétrer la route, notamment d'uploader un fichier GPX. Une route peut être privée (faut être invité pour visualiser et publier), semi-privée (faut être invité pour publier) ou ouverte (suffit d'être connecté pour publier).
1. Pour inviter des spectateurs ou des aventuriers faut leur envoyer les liens affichés sous le champ "Status".
1. Possibilité de connecter la route à Telegram.
1. Possibilité de purger ou détruire une route.

## Telegram

On peut lier une route à un groupe Telegram de façon que les géolocalisations, les messages et les photos postés sur le groupe Telegram se retrouvent dans {GEONAME}.

### Aventurier

1. Créer un [compte Telegram](https://telegram.org/apps).
1. Rejoindre le groupe Telegram de la route (lien invitation envoyé par le créateur du groupe).
1. Lors de première géolocalisation, peu importe la localisation, un compte Geogram est créé.
1. La commande "/mail votre_email" affecte le mail au compte créé, ce qui permet d'utiliser [la web app](/).
1. Les messages Telegram peuvent être automatiquement détruits après publication en fonction de la stratégie mise en place par l'administrateur de la route (par défaut, seules les géolocalisations sont détruites).
1. Il est conseillé de mettre le groupe en mode silencieux.

### Administrateur

1. Créer un [compte Telegram](https://telegram.org/apps).
1. [Connecter {GEONAME} à son compte Telegram.](/user) Important de ne pas sauter cette étape.
1. Sur Telegram, créer un groupe Telegram en invitant comme premier utilisateur **{TELEGRAM_BOT}** (attention de ne pas créer un Channel — conversations unidirectionnelles).
1. Dans le groupe, un message vous indique l'ouverture de la connexion avec {GEONAME}.
1. Sur Telegram, définir {TELEGRAM_BOT} comme administrateur du groupe (open the group, click on the group name, select Edit Group, then select Administrators, add {TELEGRAM_BOT}).
1. Définir les permissions du bot (autoriser : envoyer des messages, envoyer des photos, interdire le reste).
1. Sur {GEONAME}, [profil de la route, associer le canal Telegram.](/route) Il apparaît dans une liste déroulante ([à condition que votre profil user soit lui-même connecté à Telegram](/user)).
1. En cas de problème, "/reconnect" retablit la connexion du groupe à {GEONAME}.
1. La suppression de {TELEGRAM_BOT} du groupe Telegram détruit l'association avec {GEONAME}, sans pour autant détruire les messages déjà publiés (pour cela, il faut passer par les options de la route).
