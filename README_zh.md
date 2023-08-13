# TinyStat
一个简单的服务状态监控程序，使用 PHP 开发

![image](https://github.com/kasuganosoras/TinyStat/assets/34357771/1c5b2abb-6e84-47d1-a66c-298eaf45ac27)

## 功能特性
* TCP/UDP/ICMP/HTTP 服务监控
* 多语言支持
* 事件描述支持使用 Markdown 语法
* 支持邮件/Discord/Kook（开黑啦）/钉钉/企业微信通知服务状态变化
* 支持 MySQL/SQLite3 数据库

## 环境需求
* PHP >= 7.0
* 扩展: cURL, Socket, PDO
* 支持 InnoDB 的 MySQL / Mariadb
* SQLite3 (可选)
* 终端访问权限（需要命令行运行程序，因此不支持建站主机）

## 安装程序
1. 将本项目 clone 或下载到网站根目录
```bash
cd /data/wwwroot/my-website.com/
git clone https://github.com/kasuganosoras/TinyStat .
```
2. 创建一个新的数据库，使用 `utf8mb4` - `utf8mb4_unicode_ci` 编码格式（如使用 SQLite 可跳过此步骤）
3. 编辑 config.php 并根据你的实际信息修改配置参数
4. 在终端中运行以下命令来初始化数据库
```bash
php console.php install
```
5. 创建一个新的用户
```bash
php console.php createuser
```
6. 打开浏览器检查安装是否成功
7. 使用 `screen` 或者其他终端管理软件来运行 `cron.php` 并将其放在后台运行
```bash
cd /data/wwwroot/my-website.com/
screen -Dms tinystat /usr/local/php/bin/php cron.php
```
你也可以使用 Systemd 来管理服务

文件：`/etc/systemd/system/tinystat.service`
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

## 未来计划
- [x] 邮件通知（已完成）
- [x] 服务列表排序功能
- [ ] 状态更新通知订阅

## 许可证
本项目使用 MIT 协议开源。
