<?php
return [
    // Nom de l'association affiché dans l'interface et les exports.
    'association_name' => 'My Association',

    // DSN PDO de la base de données.
    // SQLite : 'sqlite:' . __DIR__ . '/data/attendance.db'
    //   → chemin absolu recommandé pour éviter les ambiguïtés selon le répertoire courant.
    //   → placer le fichier hors de la racine web (ex. /data/, pas /public/).
    // MySQL  : 'mysql:host=localhost;dbname=sps;charset=utf8mb4'
    'db_dsn'      => 'sqlite:' . __DIR__ . '/data/attendance.db',
    'db_user'     => null,   // null pour SQLite
    'db_password' => null,   // null pour SQLite

    // Chemin absolu vers le fichier cache du flux iCal.
    // Le dossier doit être accessible en écriture par PHP (chmod 755 ou 700).
    // Placer hors de la racine web pour éviter l'accès direct.
    'cache_path' => __DIR__ . '/cache/agenda.ics.cache',

    // Durée de validité du cache iCal en secondes (défaut : 900 = 15 min).
    // Augmenter si le calendrier est peu mis à jour ; diminuer pour plus de réactivité.
    // 'cache_ttl' => 900,

    // URL publique iCal de l'agenda Google Calendar.
    // Google Calendar → Paramètres de l'agenda → Intégrer l'agenda
    //   → "Adresse publique au format iCal"
    // Tout flux iCal public est accepté (Nextcloud, Apple Calendar, etc.).
    'calendar_url' => 'https://calendar.google.com/calendar/ical/CALENDAR_ID/public/basic.ics',

    // Optionnel — format du libellé des séances dans le sélecteur.
    // Tokens disponibles :
    //   {title}          → titre de l'événement (SUMMARY dans l'iCal)
    //   {location}       → lieu de l'événement (LOCATION dans l'iCal)
    //   {date}           → date + heure au format long localisé
    //                      (ex : "lundi 23 juin 2026 à 19:00")
    //                      Requiert l'extension PHP intl ; sinon : "23/06/2026 19:00".
    //   {date:PATTERN}   → date avec un pattern ICU personnalisé
    //                      (ex : {date:EEEE d MMMM} → "lundi 23 juin")
    // Défaut : '{date} — {title}'
    'session_label_format' => '{date} — {title}',

    // Optionnel — affichage du lieu sous le sélecteur de séance.
    // false        : lieu masqué, aucun géocodage effectué.
    // true         : nom du lieu affiché en texte seul.
    // 'only_link'  : nom du lieu sous forme de lien direct vers OpenStreetMap.
    // 'with_map'   : nom du lieu cliquable + carte Leaflet/OSM au clic
    //                (le chargement des tuiles n'a lieu qu'après consentement explicite).
    // Défaut : 'with_map'
    'show_location' => 'with_map',

    // Optionnel — filtre les événements du calendrier par titre et/ou lieu (regex PHP).
    // Une séance est incluse si son titre correspond à au moins un pattern de titre
    // ET (si des patterns de lieu sont définis) si son lieu correspond à au moins un
    // pattern de lieu. Supprimer la clé ou laisser les listes vides pour tout inclure.
    'event_filter' => [
        'title_patterns'    => [],   // ex : ['/séance/i', '/réunion/i']
        'location_patterns' => [],   // ex : ['/salle A/i']
    ],
];
