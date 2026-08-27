<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

headers();
$payload = requireAuth();
$db      = Database::connect();
$method  = $_SERVER['REQUEST_METHOD'];
$isAdmin = $payload['role'] === 'admin';

// ── GET : liste des utilisateurs (admin) ou profil perso ──
if ($method === 'GET') {
    $id = (int)($_GET['id'] ?? 0);

    if ($id && ($isAdmin || $id === (int)$payload['sub'])) {
        $stmt = $db->prepare(
            'SELECT id, nom, prenom, email, telephone, role, statut,
                    date_inscription, derniere_connexion
             FROM utilisateurs WHERE id = ?'
        );
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user) error(404, 'Utilisateur non trouvé');
        ok(['utilisateur' => $user]);
    }

    if ($isAdmin) {
        $stmt = $db->prepare(
            'SELECT id, nom, prenom, email, telephone, role, statut,
                    date_inscription, derniere_connexion
             FROM utilisateurs ORDER BY date_inscription DESC'
        );
        $stmt->execute();
        $users = $stmt->fetchAll();

        // Statistiques
        $stats = $db->query(
            "SELECT
               COUNT(*) total,
               SUM(statut='actif') actifs,
               SUM(role='admin')   admins,
               SUM(statut='banni') bannis
             FROM utilisateurs"
        )->fetch();

        ok(['utilisateurs' => $users, 'statistiques' => $stats]);
    }

    // Utilisateur simple : son propre profil
    $stmt = $db->prepare(
        'SELECT id, nom, prenom, email, telephone, role, statut,
                date_inscription, derniere_connexion
         FROM utilisateurs WHERE id = ?'
    );
    $stmt->execute([$payload['sub']]);
    ok(['utilisateur' => $stmt->fetch()]);
}

// ── PUT : modifier un compte (admin = tout, user = son profil) ──
if ($method === 'PUT') {
    $id = (int)($_GET['id'] ?? $payload['sub']);
    if (!$isAdmin && $id !== (int)$payload['sub']) error(403, 'Action non autorisée');

    $b = body();
    $champs = [];
    $vals   = [];

    if (isset($b['nom']))       { $champs[] = 'nom = ?';       $vals[] = $b['nom']; }
    if (isset($b['prenom']))    { $champs[] = 'prenom = ?';    $vals[] = $b['prenom']; }
    if (isset($b['telephone'])) { $champs[] = 'telephone = ?'; $vals[] = $b['telephone']; }

    // Admin seulement
    if ($isAdmin) {
        if (isset($b['role']))   { $champs[] = 'role = ?';   $vals[] = $b['role']; }
        if (isset($b['statut'])) { $champs[] = 'statut = ?'; $vals[] = $b['statut']; }
    }

    if (empty($champs)) error(422, 'Aucun champ à modifier');

    $vals[] = $id;
    $db->prepare('UPDATE utilisateurs SET ' . implode(', ', $champs) . ' WHERE id = ?')
       ->execute($vals);

    ok([], 'Profil mis à jour');
}

// ── DELETE : supprimer un compte (admin uniquement) ──
if ($method === 'DELETE') {
    requireAdmin();
    $id = (int)($_GET['id'] ?? 0);
    if ($id === (int)$payload['sub']) error(400, 'Impossible de supprimer votre propre compte');
    $db->prepare('DELETE FROM utilisateurs WHERE id = ?')->execute([$id]);
    ok([], 'Compte supprimé');
}

error(405, 'Méthode non autorisée');
