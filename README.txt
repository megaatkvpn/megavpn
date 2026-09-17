========================================
  Atk VPN — PHP Backend + Admin Panel
========================================

FILES:
  schema.sql          - MySQL table creation query
  config.php           - DB credentials, admin password hash, API key
  db_connect.php        - PDO connection helper
  admin.php             - Premium admin dashboard (password protected)
  api.php                - Secure JSON API endpoint
  hash_generator.php     - Browser theke password hash banate (use kore delete kore din)
  .htaccess              - config.php / db_connect.php direct access block

DEPLOY TO LIVE DOMAIN — SETUP STEPS:

1) Shob file gulo apnar hosting er public_html (ba www) folder e upload korun
   (FTP / cPanel File Manager diye). Chan hole ekta subfolder e rakhte parenv
   (jemon public_html/vpnpanel/) — sekhetre admin.php link hobe
   https://yourdomain.com/vpnpanel/admin.php

2) cPanel/hosting er phpMyAdmin e giye:
   - notun database banan (jemon: yourcpaneluser_atkvpn)
   - "Import" tab e schema.sql upload korun
   - (note: cPanel hosting shared hole database naam onek shomoy prefix soho hoy,
     jemon username_atkvpn — actual naam ta cPanel theke dekhe nin)

3) config.php update korun live DB credentials diye:
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'youractualdbname');
   define('DB_USER', 'youractualdbuser');
   define('DB_PASS', 'youractualdbpassword');

4) hash_generator.php browser e khule password hash banan,
   config.php er ADMIN_PASSWORD_HASH e boshan.
   *** Kaj shesh hole hash_generator.php DELETE kore din server theke ***

5) API_SECRET_KEY ta config.php te change kore ekta lomba random value din
   (eta apnar Android app er MainActivity.java te o same thakte hobe)

6) admin.php open kore login korun, server add korun.

7) API test korun:
   https://yourdomain.com/api.php?api_key=YOUR_SECRET_KEY

SECURITY REMINDER:
  - config.php o db_connect.php .htaccess diye block kora ache (Apache hoste hobe)
  - Live e HTTPS use korun (http na)
  - API_SECRET_KEY r ADMIN_PASSWORD_HASH default rakhben na
