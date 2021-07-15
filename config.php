<?php
    define('PHP_SID', 'CLOUD_SID');  //  Имя идентификатора сессии PHP
    define('ENCRIPTION_KEY', 'vGACu6wWz1EqD8VdO9X9NEoI1gIbHeaf');  // Ключ для шифрования cookie
    define('LOG_FILE', '/var/log/www/cloud.log'); // Путь к лог файлу
    define('HI_DIR', '/home/ftp/'); // Директория выше которой подниматься нельзя

    // DB Connection
    define('PDO_DSN', 'mysql:unix_socket=/tmp/mysql.sock');
    define('MYSQL_LOGIN', 'DB_LOGIN');
    define('MYSQL_PASS', 'DB_PASS');
    define('MYSQL_BASE', 'DB_NAME');
    define('FS_CP', 'UTF-8');
    define('BROWSER_CP', 'UTF-8');

    // Максимальный размер файлов для групповой загрузки (сжатие в zip) 3Gb
    define('MAX_ZIP_SIZE', 3221225472);

    // Настройки аккаунтов
    define('PROFTPD_UID', '1003');
    define('PROFTPD_GID', '80');
    define('PROFTPD_GROUP_NAME', 'proftp');
    define('PROFTPD_SHELL', '/sbin/nologin');

    // Uploads
    define('CHMOD', 0770);
    define('TMP_DIR','/tmp/cloud/');

    // Почта админа облака
    define('CONFIG_ADM_MAIL', 'admin@host.com');

    // Сроки хранения
    define('FILE_LIFETIME', '3 month');
    define('DIR_LIFETIME', '6 month');
    define('FILE_LIFETIME_PROLONGATION', '1 month');
    define('DIR_LIFETIME_PROLONGATION', '3 month');