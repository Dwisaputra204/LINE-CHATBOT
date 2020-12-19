<?php
require __DIR__ . '/../vendor/autoload.php';
 
 
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
 
 
use \LINE\LINEBot;
use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
use \LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use \LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use \LINE\LINEBot\MessageBuilder\AudioMessageBuilder;
use \LINE\LINEBot\MessageBuilder\ImageMessageBuilder;
use \LINE\LINEBot\MessageBuilder\VideoMessageBuilder;
use \LINE\LINEBot\SignatureValidator as SignatureValidator;
 
 
$pass_signature = true;
 
 
// set LINE channel_access_token and channel_secret
$channel_access_token = "vLli9ReCB/hxGC5DIloxwg6rXmkxVfh582b6G7SaOhUilVtlftETTw5gMwvQQ+c+U2fqwOufDSqxRFOELVln/ZV3mt1g1v1TAOtjAhAJ/EI3FA+cq6znMgBAq7iIFO2ZY/rgIuwGtAWwTl+6hf26+gdB04t89/1O/w1cDnyilFU=";
$channel_secret = "a328ba5e376bac17ad59fac016d37ee1";
 
 
// inisiasi objek bot
$httpClient = new CurlHTTPClient($channel_access_token);
$bot = new LINEBot($httpClient, ['channelSecret' => $channel_secret]);
 
 
 
 
$app = AppFactory::create();
$app->setBasePath("/public");
 
 
 
 
$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hello World!");
    return $response;
});
 
 
// buat route untuk webhook
$app->post('/webhook', function (Request $request, Response $response) use ($channel_secret, $bot, $httpClient, $pass_signature) {
    // get request body and line signature header
    $body = $request->getBody();
    $signature = $request->getHeaderLine('HTTP_X_LINE_SIGNATURE');
 
 
    // log body and signature
    file_put_contents('php://stderr', 'Body: ' . $body);
 
 
    if ($pass_signature === false) {
        // is LINE_SIGNATURE exists in request header?
        if (empty($signature)) {
            return $response->withStatus(400, 'Signature not set');
        }
 
 
        // is this request comes from LINE?
        if (!SignatureValidator::validateSignature($body, $channel_secret, $signature)) {
            return $response->withStatus(400, 'Invalid signature');
        }
    }
 
 
    $data = json_decode($body, true);
    if(is_array($data['events'])){
        foreach ($data['events'] as $event)
        {
            if ($event['type'] == 'message')
            {
                if($event['message']['type'] == 'text')
                {
                    if (strtolower($event['message']['text']) == "start" ) {
                        $textMessageBuilder1 = new TextMessageBuilder("Hi, Nama saya Dwi. \nSaya adalah BOT yang akan membantu kamu untuk memesan. \n\nCarousel dibawah adalah menu yang tersedia. \n\nTekan ORDER untuk mulai memesan");
                        
                        // $text = "Hi, Nama saya Dwi. \nSaya adalah BOT yang akan membantu kamu untuk memesan. \n\nCarousel dibawah adalah menu yang tersedia. \n\nTekan ORDER untuk mulai memesan";

                        // $result = $bot->replyText($event['replyToken'], $text);   
                        $flexTemplate = file_get_contents("../flex_message.json"); // template flex message
                        $flexResult = $httpClient->post(LINEBot::DEFAULT_ENDPOINT_BASE . '/v2/bot/message/reply', [
                            'replyToken' => $event['replyToken'],
                            'messages'   => [
                                [
                                    'type'     => 'flex',
                                    'altText'  => 'Test Flex Message',
                                    'contents' => json_decode($flexTemplate)
                                ]
                            ],
                        ]); 
                        $textMessageBuilder2 = new TextMessageBuilder($flexResult);

                        $result = new MultiMessageBuilder();
                        $result->add($textMessageBuilder1);
                        // $result->add($textMessageBuilder2);
                    } else {
                        $text = "Ketik START untuk menggunakan BOT";
                        $result = $bot->replyText($event['replyToken'], $text);    
                    }
                     
                    $response->getBody()->write(json_encode($result->getJSONDecodedBody()));
                    return $response
                        ->withHeader('Content-Type', 'application/json')
                        ->withStatus($result->getHTTPStatus());
                }
            }
        }
        return $response->withStatus(200, 'for Webhook!'); 
    }
    return $response->withStatus(400, 'No event sent!');
});

$app->run();
     
















    