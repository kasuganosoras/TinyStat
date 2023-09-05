# TinyStat
Une page de status simple, écrite en PHP

[English](README.md) | [中文介绍](README_zh.md)

![image](https://github.com/kasuganosoras/TinyStat/assets/34357771/1c5b2abb-6e84-47d1-a66c-298eaf45ac27)

## Fonctionnalités
* Support des services TCP/UDP/ICMP/HTTP
* Multilingue (français, anglais, chinois)
* Description des incidents avec prise en charge du Markdown
* Notifications Email/Discord/Kook/DingTalk/WeCom
* Support de MySQL/SQLite3

## Prérequis
* PHP >= 7.0
* Extension : cURL, Socket, PDO
* MySQL / Mariadb avec InnoDB
* SQLite3 (optionnel)
* Accès console (pour la console et le cron)

## Installation
1. Cloner le dépot à la racine de votre serveur web
```bash
cd /data/wwwroot/my-website.com/
git clone https://github.com/kasuganosoras/TinyStat .
```
2. Créer une nouvelle base de données en utilisant l'encodage `utf8mb4`. (Sautez cette étape si vous utilisez SQLite3)
3. Modifier le fichier config.php avec vos informations
4. Exécuter les commandes suivantes dans votre console pour initialiser la base de données
```bash
php console.php install
```
5. Créer un nouvel utilisateur
```bash
php console.php createuser
```
6. Ouvrer votre navigateur web et visiter votre site web pour vérifier l'installation
7. Utiliser `screen` ou un autre terminal pour exécuter `cron.php` en arrière-plan.
```bash
cd /data/wwwroot/my-website.com/
screen -Dms tinystat /usr/local/php/bin/php cron.php
```
Vous pouvez également utiliser systemd pour gérer le script.

Fichier : `/etc/systemd/system/tinystat.service`
```text
[Unit]
Description=TinyStat Service
After=network.target

[Service]
WorkingDirectory=/data/wwwroot/my-website.com/

ExecStart=/usr/local/php/bin/php cron.php

Restart=always
RestartSec=5s

[Install]
WantedBy=multi-user.target
```

## TO-DO
* See [global ReadMe](README.md)

## License
Ce projet est open source sous la licence MIT.