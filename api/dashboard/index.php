<?php
// Endpoint admin : statistiques globales du tableau de bord
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

headers();
requireAdmin();
$db = Database::connect();

$stats = [
    'utilisateurs' => $db->query(
        "SELECT COUNT(*) total, SUM(statut='actif') actifs,
                SUM(role='admin') admins FROM utilisateurs"
    )->fetch(),

    'stations' => $db->query(
        "SELECT COUNT(*) total, SUM(statut='actif') actives FROM stations"
    )->fetch(),

    'mesures' => $db->query(
        "SELECT COUNT(*) total,
                COUNT(DISTINCT DATE(horodatage)) jours,
                COUNT(DISTINCT station_id) stations
         FROM mesures"
    )->fetch(),

    'alertes' => $db->query(
        "SELECT COUNT(*) total, SUM(lue=0) non_lues FROM alertes"
    )->fetch(),

    'derniers_inscrits' => $db->query(
        "SELECT id, nom, prenom, email, role, statut, date_inscription
         FROM utilisateurs ORDER BY date_inscription DESC LIMIT 5"
    )->fetchAll(),

    'dernieres_mesures' => $db->query(
        "SELECT m.*, s.nom station_nom FROM mesures m
         JOIN stations s ON s.id = m.station_id
         ORDER BY m.horodatage DESC LIMIT 10"
    )->fetchAll(),
];

ok($stats);
