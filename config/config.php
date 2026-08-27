<?php
// En prod (Railway) : defini via la variable d'environnement JWT_SECRET (obligatoire).
// Le fallback ci-dessous ne sert QUE pour le dev local WAMP — sans valeur secrete reelle.
define('JWT_SECRET',     getenv('JWT_SECRET') ?: 'dev-local-wamp-only-do-not-use-in-prod');
define('JWT_EXPIRY',     86400);       // 24 heures
define('JWT_REFRESH',    604800);      // 7 jours
define('BCRYPT_COST',    12);
define('API_VERSION',    'v1');
define('ALLOWED_ORIGIN', '*');         // restreindre en prod
