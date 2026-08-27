<?php
// En prod (Railway) : defini via la variable d'environnement JWT_SECRET.
// Le fallback ci-dessous ne sert QUE pour le dev local WAMP.
// TODO: une fois JWT_SECRET confirme sur Railway, remplacer ce fallback par une valeur bidon.
define('JWT_SECRET',     getenv('JWT_SECRET') ?: 'SmartIrrigation_S3cr3t_K3y_2026!');
define('JWT_EXPIRY',     86400);       // 24 heures
define('JWT_REFRESH',    604800);      // 7 jours
define('BCRYPT_COST',    12);
define('API_VERSION',    'v1');
define('ALLOWED_ORIGIN', '*');         // restreindre en prod
