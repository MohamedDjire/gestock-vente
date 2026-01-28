<?php
// notifier_apres_vente.php
// À inclure après l'enregistrement d'une vente
require_once 'config.php';
require_once 'stock_notification_utils.php';

$param = $pdo->query("SELECT * FROM stock_notification_settings ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if ($param) {
    // Notification Email
    if ($param['email_active'] && $param['notifier_vente'] && !empty($param['email_admin'])) {
        $vente = isset($venteDetails) ? $venteDetails : [];
        $sujet = "Nouvelle vente enregistrée";
        $corps = "<b>Nouvelle vente</b><br>Facture : ".$vente['numero'].", Montant : ".$vente['montant']."<br>Date : ".$vente['date']."<br>Utilisateur : ".$vente['utilisateur'];
        envoyerNotificationEmail($param['email_admin'], $sujet, $corps, 'vente');
    }
    // Notification SMS (optionnel)
    if ($param['sms_active'] && $param['notifier_vente'] && !empty($param['telephone_admin'])) {
        $vente = isset($venteDetails) ? $venteDetails : [];
        $sms = "Vente #".$vente['numero']." - ".$vente['montant']."F le ".$vente['date'];
        envoyerNotificationSMS($param['telephone_admin'], $sms, 'vente');
    }
}
?>
