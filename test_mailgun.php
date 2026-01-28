<?php
// test_mailgun.php
// Placez ce fichier à la racine de votre serveur et ouvrez-le dans le navigateur

$apiKey = 'key-6200b5f09fd0e3089bdd3001f9e6ee26'; // Remplacez par votre clé API Mailgun
$domain = 'sandbox2abe1faa7a6a4773b9538a5196ce4e01.mailgun.org'; // Remplacez par votre domaine Mailgun
$from = 'Notification <mailgun@' . $domain . '>';
$to = 'mariamberthe214@gmail.com'; // Remplacez par votre adresse email pour le test
$subject = 'Test Mailgun PHP';
$text = 'Ceci est un test d\'envoi via Mailgun API en PHP.';
$html = '<b>Ceci est un test d\'envoi via Mailgun API en PHP.</b>';

$url = "https://api.mailgun.net/v3/$domain/messages";
$data = [
    'from' => $from,
    'to' => $to,
    'subject' => $subject,
    'text' => $text,
    'html' => $html
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
curl_close($ch);

echo '<h2>Résultat Mailgun</h2>';
echo '<pre>';
echo 'HTTP code: ' . $httpCode . "\n";
echo htmlspecialchars($result);
echo '</pre>';
