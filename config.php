<?php
/* Database Configuration */
define("DB_TYPE", "mysql");           // Database type (mysql or sqlite)
define("DB_HOST", "127.0.0.1");       // Database host (ignored if using sqlite)
define("DB_USER", "root");            // Database username
define("DB_PASS", "123456");          // Database password
define("DB_NAME", "tinystat");        // Database name (for sqlite, this is the absolute path to the database file)

/* General Configuration */
define("LOCALE", "en");               // Language, en or zh
define("THEME", "light");             // Theme, light or dark
define("WARN_FAILURE", 3);            // Maximum failure count before service is marked as warning
define("ERR_FAILURE", 10);            // Maximum failure count before service is marked as error
define("TIMEOUT_SEC", 5);             // Timeout in seconds
define("CHECK_INTERVAL", 10);         // Check interval in seconds

/* SMTP Configuration */
define("SMTP_HOST", "smtp.example.com");     // SMTP host
define("SMTP_PORT", 25);                     // SMTP port
define("SMTP_MODE", "");                     // SMTP security, tls or ssl
define("SMTP_VERI", false);                  // Verify SMTP certificate (disable if using self-signed certificate
define("SMTP_USER", "noreply@example.com");  // SMTP username
define("SMTP_PASS", "password");             // SMTP password
define("SMTP_FROM", "noreply@example.com");  // SMTP from address

/* Notification Configuration */
define("NOTIFY_EMAIL", false);    // Send notification email
define("NOTIFY_KOOK", false);     // Send notification to Kook channel
define("NOTIFY_DISCORD", false);  // Send notification to Discord webhook
define("NOTIFY_DINGTALK", false); // Send notification to DingTalk webhook
define("NOTIFY_WECOM", false);    // Send notification to WeCom webhook

/* Discord Webhook Configuration */
define("DISCORD_CHANNEL", "xxxxx");
define("DISCORD_TOKEN", "xxxxx");
define("DISCORD_USERNAME", "TinyStat");

/* Kook Configuration */
define("KOOK_CHANNEL", "xxxxx");
define("KOOK_TOKEN", "xxxxx");

/* DingTalk Configuration */
define("DINGTALK_TOKEN", "xxxxxx");
define("DINGTALK_SECRET", "xxxxxx");

/* WeCom Configuration */
define("WECOM_KEY", "xxxxxx");

/* FreeMobile Configuration */
define("FREEMOBILE_USER", "xxxxx");
define("FREEMOBILE_PASS", "xxxxx");