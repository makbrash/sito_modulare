<?php
/**
 * API Endpoint: Newsletter Subscription
 * Gestisce le iscrizioni alla newsletter classica
 */

// Evita output HTML/notice che romperebbero il JSON
ini_set('display_errors', '0');
error_reporting(E_ALL);
ob_start();

// Headers per CORS e JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Gestione preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    // Pulisci eventuale output catturato e termina
    if (ob_get_length()) { ob_end_clean(); }
    exit;
}

// Solo POST accettato
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    if (ob_get_length()) { ob_clean(); }
    echo json_encode([
        'success' => false,
        'message' => 'Metodo non consentito'
    ]);
    exit;
}

// Funzione di validazione email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Funzione di sanitizzazione
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

try {
    // Recupera dati dal form
    $name = isset($_POST['name']) ? sanitizeInput($_POST['name']) : '';
    $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';
    $privacy = isset($_POST['privacy']) ? true : false;

    // Validazione
    $errors = [];

    if (empty($name) || strlen($name) < 2) {
        $errors[] = 'Nome non valido';
    }

    if (empty($email) || !validateEmail($email)) {
        $errors[] = 'Email non valida';
    }

    if (!$privacy) {
        $errors[] = 'Devi accettare la privacy policy';
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => implode(', ', $errors),
            'errors' => $errors
        ]);
        exit;
    }

    // Connessione al database
    require_once __DIR__ . '/../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        http_response_code(500);
        if (ob_get_length()) { ob_clean(); }
        echo json_encode([
            'success' => false,
            'message' => 'Errore di connessione al database'
        ]);
        exit;
    }

    // Verifica se l'email esiste già
    $stmt = $db->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Email già registrata'
        ]);
        exit;
    }

    // Inserisci nuova iscrizione
    $stmt = $db->prepare("
        INSERT INTO newsletter_subscribers (name, email, subscribed_at, ip_address, user_agent) 
        VALUES (?, ?, NOW(), ?, ?)
    ");
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt->execute([$name, $email, $ipAddress, $userAgent]);

    // TODO: Invia email di conferma (double opt-in)
    // sendConfirmationEmail($email, $name);

    // Successo
    http_response_code(200);
    if (ob_get_length()) { ob_clean(); }
    echo json_encode([
        'success' => true,
        'message' => 'Iscrizione completata con successo!',
        'data' => [
            'name' => $name,
            'email' => $email
        ]
    ]);

} catch (PDOException $e) {
    // Errore database
    error_log('Newsletter Subscribe Error: ' . $e->getMessage());
    
    http_response_code(500);
    if (ob_get_length()) { ob_clean(); }
    echo json_encode([
        'success' => false,
        'message' => 'Errore del server. Riprova più tardi.'
    ]);
    
} catch (Exception $e) {
    // Errore generico
    error_log('Newsletter Subscribe Error: ' . $e->getMessage());
    
    http_response_code(500);
    if (ob_get_length()) { ob_clean(); }
    echo json_encode([
        'success' => false,
        'message' => 'Errore durante l\'iscrizione. Riprova.'
    ]);
}

