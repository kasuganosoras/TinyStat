# TinyStat
A simple status page service, written in PHP

![image](https://github.com/kasuganosoras/TinyStat/assets/34357771/1c5b2abb-6e84-47d1-a66c-298eaf45ac27)

## Features
* TCP/UDP/ICMP/HTTP Service support
* Locale support
* Incident description with markdown support

## Requirements
* PHP >= 7.0
* Extension: cURL, Socket, PDO
* MySQL / Mariadb with InnoDB support
* Terminal access (for cron/console)

## Installation
1. Clone this repo to your website root folder
```bash
cd /data/wwwroot/my-website.com/
git clone https://github.com/kasuganosoras/TinyStat .
```
2. Create a new database, using `utf8mb4` charset.
3. Edit your config.php and change the database info.
4. Running the following command in your terminal to initialize the database
```bash
php console.php install
```
5. Create a new user
```bash
php console.php createuser
```
6. Open the browser and visit your website to check the installation
7. Using `screen` or other terminal manager to run the `cron.php` in background.
```bash
cd /data/wwwroot/my-website.com/
screen -Dms tinystat /usr/local/php/bin/php cron.php
```
You can also use systemd to manage the service.

File: `/etc/systemd/system/tinystat.service`
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
- [ ] Email notification
- [ ] Service display sort
- [ ] Subscribe the status

## License
This project is open source under the MIT license.
