<?php
// stock_notification_utils.php
// Fonctions pour envoyer email/SMS et journaliser (version FR)

require_once 'config.php';

function envoyerNotificationEmail($destinataire, $sujet, $corps, $typeEvenement) {
    // === CONFIGURATION MAILGUN ===
    $apiKey = '77c6c375-25e86afa'; // À remplacer par votre clé API Mailgun
    $domain = 'sandbox2abe1faa7a6a4773b9538a5196ce4e01.mailgun.org'; // ex: sandboxXXXX.mailgun.org
    $from = 'Notification <mailgun@' . $domain . '>';

    error_log('Appel Mailgun: to=' . $destinataire . ', sujet=' . $sujet);

    $url = "https://api.mailgun.net/v3/$domain/messages";
    $data = [
        'from' => $from,
        'to' => $destinataire,
        'subject' => $sujet,
        'html' => $corps,
        'text' => strip_tags($corps)
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, 'api:' . $apiKey);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    error_log('Mailgun result: ' . $result);
    error_log('Mailgun HTTP code: ' . $httpCode);
    $statut = ($httpCode === 200) ? 'envoye' : 'echec';
    if ($statut === 'echec') {
        error_log('Mailgun error: ' . $result);
    }
    curl_close($ch);
    journaliserNotification('email', $typeEvenement, $corps, $statut);
}

function journaliserNotification($type, $typeEvenement, $message, $statut) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO stock_notification_logs (type, type_evenement, message, statut) VALUES (?, ?, ?, ?)");
    $stmt->execute([$type, $typeEvenement, $message, $statut]);
}

function envoyerNotificationSMS($telephone, $message, $typeEvenement) {
    // À implémenter selon ton fournisseur SMS
    $statut = 'envoye'; // ou 'echec' si erreur
    journaliserNotification('sms', $typeEvenement, $message, $statut);
}
?>
