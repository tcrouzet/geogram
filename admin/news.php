<?php
html_header("News");
menu();
baseline("news");
?>
<div id="containerText" >

<a href="images/logo.png"><img src="images/logo.png" /></a>

<p><a href="" target="_blank">Geogram</a> est une application de suivi d’aventures en semi-temps réel qui ne nécessite aucun autre outil qu’un téléphone avec l’application <a data-tooltip-position="top" aria-label="https://telegram.org/apps" rel="noopener" class="external-link" href="https://telegram.org/apps" target="_blank">Telegram</a>. Je l’ai créée en mars 2023 pour le <a data-tooltip-position="top" aria-label="https://727.tcrouzet.com/" rel="noopener" class="external-link" href="https://727.tcrouzet.com/" target="_blank">727</a>.
J’étais fatigué de voir des solutions onéreuses et lourdes à mettre en œuvre (surtout pour les organisateurs d’évènements, avec location des balises, demande de caution, assurances…).
Depuis, Geogram a été utilisée par des centaines d’aventuriers.
On peut non seulement se géolocaliser, mais partager des photos, des textes, tant avec les autres aventuriers que ses proches.</p>

<h2>Avril 2024</h3>
<p>Je viens de passer dix jours à réviser le code de Geogram pour le rendre plus réactif et simplifier l’interface.
    <a href="https://github.com/tcrouzet/geogram">J’en profite pour distribuer le code sur GitHub</a> (sans lui associer de documentation).</p>
<p>L’application repose sur <a data-tooltip-position="top" aria-label="https://t.me/GeoBikepacking" rel="noopener" class="external-link" href="https://t.me/GeoBikepacking" target="_blank">@GeoBikepacking_bot</a>, un bot Telegram <a data-tooltip-position="top" aria-label="https://geogram.tcrouzet.com/help#help_admin" rel="noopener" class="external-link" href="https://geogram.tcrouzet.com/help#help_admin" target="_blank">qui peut être invité dans des groupes pour gérer la géolocalisation des utilisateurs, alias aventuriers</a>.</p>
<p>Désormais, dès qu’un message est posté sur le groupe, il est immédiatement pris en charge par Geogram (alors qu’initialement la prise en charge ne s’effectuait que toutes les dix minutes —&nbsp;j’ai remplacé le cron par un webhook).</p>
<p>Cette nouvelle approche permet de faire apparaître dans les groupes un menu pour piloter Geogram.</p>

<a href="images/news/menu-gen.jpg"><img src="images/news/menu-gen.jpg" /></a>

<p>Le menu principal pointe vers le site associé au groupe sur Geogram et renvoie vers les menus utilisateur et administrateur.</p>

<a href="images/news/menu-user.jpg"><img src="images/news/menu-user.jpg" /></a>

<p>L’utilisateur peut mettre à jour son avatar, accéder à son historique de géolocalisation et de message, voire effacer son historique.</p>

<a href="images/news/menu-admin.jpg"><img src="images/news/menu-admin.jpg" /></a>

<p>Le menu administrateur, accessible uniquement par le créateur du groupe, permet de régler le comportement de Geogram. Par exemple, il existe désormais trois modes de fonctionnement&nbsp;: silencieux, tous les messages effacés dès leur prise en compte, normal, seules les géolocalisations sont effacées, verbose, rien n’est effacé.</p>

<a href="images/news/menu-info.jpg"><img src="images/news/menu-info.jpg" /></a>

<p>L’administrateur peut même gérer individuellement les aventuriers.</p>
<p>La commande "/menu" réaffiche le menu en bas du groupe. Il n’y a plus d’autres commandes obscures à connaître.</p>

<a href="images/news/menu-user.jpg"><img src="images/news/i727map.jpg" /></a>

<p>Initialement, uniquement les GPX avec une seule trace étaient acceptés.
    J’ai ouvert la possibilité de traces multiples. J’avais besoin de cette fonction pour <a data-tooltip-position="top" aria-label="https://727bikepacking.fr/i727/" rel="noopener" class="external-link" href="https://727.tcrouzet.com/i727/" target="_blank">le i727&nbsp;2024</a> qui offre un itinéraire principal et trois short cuts. Logiquement le système supporte des traces de plus de 3&nbsp;000&nbsp;km (en fait il n’y a pas vraiment de limites et je peux jouer sur ce paramètre).</p>
<p>Côté interface web, <a href="g727_2023" target="_blank">exemple du g727 2023</a>, je n’ai pas changé grand-chose.
La plupart des aventures sont réservées aux aventuriers disposant du lien d’invitation envoyé par l’administrateur du groupe Telegram,
mais il est possible de créer des aventures ouvertes en ITT, <a href="727itt/info" target="_blank">par exemple le 727</a>.
Tout le monde peut rejoindre librement le groupe et se géolocaliser.
En revanche, après une semaine d’inactivité, les aventuriers disparaissent (leurs historiques ne sont pas pour autant effacés et on peut les retrouver dans <a data-tooltip-position="top" aria-label="https://geogram.tcrouzet.com/727itt/story" rel="noopener" class="external-link" href="https://geogram.tcrouzet.com/727itt/story" target="_blank">la story du groupe</a>).</p>
<p>Cette nouvelle version de Geogram sera mise à l’épreuve lors du i727 qui part le 8 mai 2024.</p>
<p><em>PS&nbsp;: En tant que projet Open Source, Geogram est actuellement hébergé sur mon serveur personnel, un vieux NAS Synology, qui risque de crouler sous les requêtes si des centaines d’aventuriers se mettent à échanger des messages. Si un sponsor veut financer l’hébergement, j’en serai ravi, tout comme développer des versions de Geogram à son image.</em></p>

<h2>Avril 2023</h3>

<p><a href="https://tcrouzet.com/2023/04/13/suivi-gratuit-daventures-bikepacking/">Test de la première version lors du 727.</a></p>

<h2>Mars 2023</h3>

<p><a href="https://tcrouzet.com/2023/03/07/le-bikepacking-pour-les-pauvres/">À l'origine de l'idée…</a></p>
</div>