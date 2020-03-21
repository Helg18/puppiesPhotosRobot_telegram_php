<?php

use App\Services\RequesterService;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\FileUpload\InputFile;

// include composer config
require_once '../vendor/autoload.php';

// Loading .env
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

try {
    // Creando la instancia del bot
    $bot = new Api();
} catch (Exception $e) {
    $res = $e->getMessage();
}

/**
 * Validando webhook
 */
$webhook = $bot->getWebhookInfo();

// Validando si esta en produccion
if (getenv("IS_PRODUCTION") === 'true' ? true : false) {
    // Si no existe se setea el webhook
    if ($webhook['url'] === ''){
        // Seteando el webhook
        try {
            $bot->setWebhook([
                'url' => getenv("TELERGAM_BOT_URL")
            ]);
        } catch (TelegramSDKException $e) {
            error_log("Error: " . $e->getCode() . " | Message: " . $e->getMessage());
            die(500);
        }
    }
} else {
    // Borrar el webhook si esta en desarrollo
    $bot->deleteWebhook();
}

// Obteniendo los mensajes y seteando las variables principales
try {
    $updates = $bot->getUpdates();

    // Si no hay updates, romper el proceso
    if (!isset($updates[0])) { die(500); }

    // Obteniendo variables principales
    $chat_id = $updates[0]->getMessage()->get('chat')->get('id');
    $mesage = $updates[0]->getMessage()->get('text');
    $tipo = "message";

} catch (TelegramSDKException $e) {
    error_log("Error: " . $e->getCode() . " | Message: " . $e->getMessage());
    die(500);
}

// Obtener la respuesta para enviar
if ($mesage === '/start'){
    $respuesta = "Bienvenido yo soy Puppies Photo Robot, me encargare de buscar una imagen aleatoria de un perrito en internet y te la dare. He nacido de @helg18, puedes usar /help para conseguir mas ayuda.";
}
elseif ($mesage === '/donar') {
    $respuesta = "Puedes hacer tu donacion a:
        
". getenv("WALLET_BTC") ."
        
Tu aporte sera de gran ayuda para seguir trayendo mas bots, con mas utilidades y cada vez mejor";;
}
elseif ($mesage === '/desarrolladopor') {
    $respuesta = "@loremPicsumRobot fue desarrollado por @helg18, solo por diversion";
}
elseif ($mesage === '/random') {
    $tipo = 'photo';
    $respuesta = "";
}
elseif ($mesage === '/help') {
    $respuesta = "Lista de comandos
/help - muestra los comandos disponibles 
/donar - muestra un wallet BTC para hacer donaciones 
/random - devuelve una imagen random de internet";
}

// funcion para devolver el arreglo con el nombre de imagen y el caption en un arreglo
function linkAndFilename($str){
    return array_reverse(explode('/',  $str));
}

// bajar la imagen de internet y guardarla en local
function getImg($link){
    file_put_contents('img.jpg', file_get_contents($link));
    return true;
}

// Enviar Respuesta
try {
    // Validando el tipo de respuesta
    if ($tipo === 'message') {
        // Enviando mensaje
        $bot->sendMessage([
            'chat_id'=>$chat_id,
            'text'=>$respuesta
        ]);
    }

    // Validando el tipo de respuesta
    if ($tipo === 'photo') {
        $client = new RequesterService();

        $link = $client->getImgLink();

        list($filename, $caption) = linkAndFilename($link->message);

        getImg($link->message);

        $bot->sendPhoto([
            'chat_id' => $chat_id,
            'photo' => InputFile::create('./img.jpg', $filename),
            'caption' => $caption
        ]);

    }
} catch (Exception $exception) {
    error_log("Error: " . $exception->getCode() . " | Message: " . $exception->getMessage());
} finally {
    // eliminando la imagen
    unlink('img.jpg');
}
