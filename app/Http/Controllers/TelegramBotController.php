<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Telegram;
use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Carbon\Carbon;
use App\FindArchieve;
use App\LatestArchieve;
use function GuzzleHttp\json_decode;

class TelegramBotController extends Controller
{
    public function getMe()
    {
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        $response = $telegram->getMe();
        return $response;
    }

    public function getUpdates()
    {
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        $response = $telegram->getUpdates(); //get updates telegram messages
        return $response;
    }

    public function getResponse()
    {
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        $telegram->isAsyncRequest(true);
        $response = $telegram->getUpdates();
        $req = collect(end($response));

        //check if there is callback_query or not
        if(isset($req['callback_query'])){
            $text = $req['callback_query']['data'];
            $chatid = $req['callback_query']['message']['chat']['id'];
            $callback_id = $req['callback_query']['id'];
            $message_id = $req['message']['message_id'];
            $reply = (isset($req['reply_to_message']) ? $req['reply_to_message']: '' );
        }else{
            $fromid = $req['message']['from']['id'];
            $chatid = $req['message']['chat']['id'];
            $text = $req['message']['text']; // get the user sent text
            $name = $req['message']['from']['first_name'];
            $fromUsername = $req['message']['from']['username']; //get username telegram
            $callback_id = 0;
            $message_id = $req['message']['message_id'];
            $reply = (isset($req['reply_to_message']) ? $req['reply_to_message']: '' );
        }

        /*
            Table
            ---------------------------------------------------------
            |  id   |  user_telegram  |   chat_id   | last_command  |
            ---------------------------------------------------------
        */
        $lastCommand = '';
        $telegram = new Telegram();
        if($text == 'Arsip Terbaru' || $text == 'Cari Arsip' || $text == '/cariarsip' || $text == '/arsipterbaru'){
            $telegram->user_telegram = $fromUsername;
            $telegram->chat_id = $chatid;
            $telegram->last_command = $text;
            $telegram->added_find_keyword = '';
            $telegram->save();
        }
        
        $lastCommand = Telegram::where('chat_id',$chatid)->where('user_telegram',$fromUsername)->latest()->first();
        if($text == '/start'){
            $this->commandWelcomingText($chatid, $name, $fromUsername, $message_id);
        }elseif($text == '/stop'){
            $this->commandremoveAccessUser($chatid, $name, $fromUsername);
        }elseif($text == 'Arsip Terbaru'){
            $this->commandLatestArchieve($chatid, $text, $fromUsername);
        }elseif($text == 'Cari Arsip'){
            $this->commandShowFindArchieve($chatid, $name, $fromUsername);
        }elseif(!$lastCommand){
            if($lastCommand->last_command == 'Arsip Terbaru' || $lastCommand->last_command == '/arsipterbaru'){
                $lastLatestCommand = $lastCommand->last_command;
                switch ($text) {
                    case '1':
                        $this->commandDetailArchive($chatid, $name, $fromUsername, $message_id, $text);
                        break;
                    case '2':
                        $this->commandDetailArchive($chatid, $name, $fromUsername, $message_id, $text);
                        break;
                    case '3':
                        $this->commandDetailArchive($chatid, $name, $fromUsername, $message_id, $text);
                        break;
                    case '4':
                        $this->commandDetailArchive($chatid, $name, $fromUsername, $message_id, $text);
                        break;
                    case '5':
                        $this->commandDetailArchive($chatid, $name, $fromUsername, $message_id, $text);
                        break;
                    case '6':
                        $this->commandDetailArchive($chatid, $name, $fromUsername, $message_id, $text);
                        break;
                    case 'Menu':
                        $this->commandDefaultMenu($chatid, $name, $fromUsername, $message_id, $text,$lastLatestCommand);
                        break;
                    case '<< Kembali' :
                        $this->commandGetLastLatestArchieve($chatid, $name, $fromUsername, $message_id, $text,$lastLatestCommand);
                        break;
                    default:
                        $this->commandDefault($chatid, $name, $fromUsername, $message_id);
                        break;
                }
            }elseif($lastCommand->last_command == 'Cari Arsip' || $lastCommand->last_command == '/cariarsip') {
                $lastLatestCommand = $lastCommand->last_command;
                if($lastCommand->added_find_keyword == ''){
                    //tidak ada added find keyword
                    $this->commandFindArchieve($chatid, $name, $fromUsername, $text, $message_id);
                }else{
                    //show detail cari arsip
                    switch ($text) {
                        case '1':
                            $this->commandDetailFindArchive($chatid, $name, $fromUsername, $message_id, $text);
                            break;
                        case '2':
                            $this->commandDetailFindArchive($chatid, $name, $fromUsername, $message_id, $text);
                            break;
                        case '3':
                            $this->commandDetailFindArchive($chatid, $name, $fromUsername, $message_id, $text);
                            break;
                        case '4':
                            $this->commandDetailFindArchive($chatid, $name, $fromUsername, $message_id, $text);
                            break;
                        case '5':
                            $this->commandDetailFindArchive($chatid, $name, $fromUsername, $message_id, $text);
                            break;
                        case '6':
                            $this->commandDetailFindArchive($chatid, $name, $fromUsername, $message_id, $text);
                            break;
                        case 'Menu':
                            $this->commandDefaultMenu($chatid, $name, $fromUsername, $message_id, $text,$lastLatestCommand);
                            break;
                        case '<< Kembali' :
                            $this->commandGetLastFindArchieve($chatid, $name, $fromUsername, $message_id, $text,$lastLatestCommand);
                            break;
                        default:
                            $this->commandDefault($chatid, $name, $fromUsername, $message_id);
                            break;
                    }
                    //$this->commandDetailFindArchive($chatid, $name, $fromUsername, $message_id, $text);
                }
            }else{
                $this->commandDefault($chatid, $name, $fromUsername, $message_id);    
            }
        }elseif ($text == '/help') {
            $this->commandHelp($chatid, $name, $fromUsername, $text, $message_id);
        }else{
            $this->commandDefault($chatid, $name, $fromUsername, $message_id);
        }
    }

    //function command
    public function commandWelcomingText($chatid, $name, $fromUsername, $message_id)
    {
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        
        $this->keyTyping($telegram,$chatid);

        //cek authorized or not
        $auth = $this->isAuthorized($fromUsername);
        if($auth == 'success'){ //user is Authorized
            //$this->keyboard('show','custom');
            $response = $telegram->sendMessage([
                'chat_id' => $chatid,
                'text'  => 'Hai '.$name.', Selamat datang di KotakArsip. Masukkan Kata Kunci ',
                'reply_markup'  => $this->keyboard('show','custom'),
                //'reply_to_message_id'   => $message_id,
                'force_reply'   => true
            ]);
            //////dd($response);
        }elseif($auth == 'failed'){
            //show inline keyboard authorized
            $keyboard = [
                [
                    [
                        'text' => 'Authorize me',
                        'url'   => 'http://beta.kotakarsip.com/api/v1/auth/login?u='.$fromUsername//url login
                    ]
                ]
            ];
    
            $reply_markup = $telegram->replyKeyboardMarkup([
                'inline_keyboard'   => $keyboard
            ]);
    
            $response = $telegram->sendMessage([
                'chat_id'   => $chatid,
                'text'  => 'Untuk dapat mengakses fitur Kotak Arsip, silahkan klik tombol dibawah ini',
                'reply_markup'  => $reply_markup
            ]);

            //////dd($response);
        }
    }

    public function commandremoveAccessUser($chatid, $name, $fromUsername)
    {
        # code...
    }

    public function keyboard($status = 'show', $keyboardType = 'custom')
	{
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        if($keyboardType == 'custom'){
            $keyboard = [
                ['Cari Arsip','Arsip Terbaru']
            ];

            $reply_markup = $telegram->replyKeyboardMarkup([
                'keyboard' => $keyboard, 
                'resize_keyboard' => true,
                'one_time_keyboard' => true
            ]);
    
            if ($status == 'hide') {
                $reply_markup = $telegram->replyKeyboardHide();
            }
    
            if ($status == 'reply') {
                $reply_markup = $telegram->forceReply();
            }
    
            return $reply_markup;
        }elseif($keyboardType == 'detail'){
            $keyboard = [
                ['1','2','3'],
                ['4','5','6'],
                ['Menu']
            ];

            $reply_markup = $telegram->replyKeyboardMarkup([
                'keyboard' => $keyboard, 
                'resize_keyboard' => true,
                'one_time_keyboard' => true
            ]);
    
            if ($status == 'hide') {
                $reply_markup = $telegram->replyKeyboardHide();
            }
    
            if ($status == 'reply') {
                $reply_markup = $telegram->forceReply();
            }
    
            return $reply_markup;
        }elseif($keyboardType == 'latestarchieve'){
            $keyboard = [
                ['<< Kembali','Arsip Terbaru']
            ];

            $reply_markup = $telegram->replyKeyboardMarkup([
                'keyboard' => $keyboard, 
                'resize_keyboard' => true,
                'one_time_keyboard' => true
            ]);
    
            if ($status == 'hide') {
                $reply_markup = $telegram->replyKeyboardHide();
            }
    
            if ($status == 'reply') {
                $reply_markup = $telegram->forceReply();
            }
    
            return $reply_markup;
        }elseif($keyboardType == 'findarchieve'){
            $keyboard = [
                ['<< Kembali','Cari Arsip']
            ];

            $reply_markup = $telegram->replyKeyboardMarkup([
                'keyboard' => $keyboard, 
                'resize_keyboard' => true,
                'one_time_keyboard' => true
            ]);
    
            if ($status == 'hide') {
                $reply_markup = $telegram->replyKeyboardHide();
            }
    
            if ($status == 'reply') {
                $reply_markup = $telegram->forceReply();
            }
    
            return $reply_markup;
        }
    }

    public function keyTyping($telegram,$chatid)
    {
        $keyTyping = $telegram->sendChatAction([
            'chat_id'   => $chatid,
            'action'    => 'typing'
        ]);

        return $keyTyping;
    }

    public function isAuthorized($username)
    {
        //call request post to check if username is registered/authorized
        $client = new Client();
        $url = 'http://beta.kotakarsip.com/api/v1/authorized?u='; //set url for request

        $response = $client->request('GET', $url.$username);
        //and return true or false

        $code = $response->getStatusCode(); //get status code
        $status = '';
        $res = json_decode($response->getBody());
        switch ($res->status) {
            case 'success':
                return $status='success';
                break;
            case 'failed':
                return $status='failed';
                break;
            default:
                return $status='failed';
                break;
        }
    }

    public function commandLatestArchieve($chatid, $text, $username)
    {
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));

        $this->keyTyping($telegram,$chatid);

        $auth = $this->isAuthorized($username);

        if($auth == 'success'){ //user is authorized

            //find new archieve of the user
            $client = new Client();
            
            $url = 'http://beta.kotakarsip.com/api/v1/latest-archieve?u='; //set url for request

            $response = $client->request('GET', $url.$username);
            //and return true or false

            $code = $response->getStatusCode(); //get status code
            //send message/result to the user
            $archieves = json_decode($response->getBody());
            $listArchives = '';
            $arrayListArchieves = [];
            foreach ($archieves->data as $key => $archive) {
                if($archive->type == 'incoming_mail'){
                    $listArchives .= ($key + 1)."️⃣  ".$archive->from."\n\n";
                    array_push($arrayListArchieves, ['key' => $key+1 , 'value' => $archive->from,'id' => $archive->_id]);
                    if ($key >= 7) {
                        break;
                    }
                }elseif($archive->type == 'outgoing_mail'){
                    $listArchives .= ($key + 1)."️⃣  ".$archive->to."\n\n";
                    array_push($arrayListArchieves, ['key' => $key+1 , 'value' => $archive->to,'id' => $archive->_id]);
                    if ($key >= 7) {
                        break;
                    }
                }elseif($archive->type == 'file'){
                    $listArchives .= ($key + 1)."️⃣  ".$archive->name."\n\n";
                    array_push($arrayListArchieves, ['key' => $key+1 , 'value' => $archive->name,'id' => $archive->_id]);
                    if ($key >= 7) {
                        break;
                    }
                }
            }
            //save to db
            $latestArchieve = new LatestArchieve();
            $latestArchieve->last_archieve = $listArchives."\n\nMasukkan nomor urut arsip untuk melihat detail";
            $latestArchieve->user_telegram = $username;
            $latestArchieve->chat_id = $chatid;
            $latestArchieve->last_command = $text;
            $latestArchieve->array_value = json_encode($arrayListArchieves);
            $latestArchieve->save();
            

            $result = $telegram->sendMessage([
                'chat_id' => $chatid,
                'text' => $listArchives."\n\nMasukkan nomor urut arsip untuk melihat detail",
                'parse_mode' => 'html',
                'reply_markup' => $this->keyboard('show','detail'),
                'force_reply'   => true
            ]);

            ////dd("berhasil cuy",$result, $latestArchieve);
        }else{
            //user is not authorized
            $this->setAuthorization($chatid, $username);
        }
    }

    public function commandDetailArchive($chatid, $name, $fromUsername, $message_id, $text)
    {
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        $this->keyTyping($telegram,$chatid);

        $auth = $this->isAuthorized($fromUsername);

        if($auth == 'success'){ //user is authorized
            //find new archieve of the user
            $client = new Client();

            $lastLatestArchieve = LatestArchieve::where('user_telegram',$fromUsername)
                                ->where('chat_id', $chatid)
                                ->latest()
                                ->first();
            $detail = 0;
            foreach (json_decode($lastLatestArchieve->array_value) as $key => $value) {
                if( (int)$text == $key+1){
                    $detail = $value->id;
                    break;
                }
            }
            ////dd(json_decode($lastLatestArchieve->array_value),$detail);
            
            $url = 'http://beta.kotakarsip.com/api/v1/latest/'.$detail.'/detail?u='; //set url for request

            $response = $client->request('GET', $url.$fromUsername);
            //and return true or false

            $code = $response->getStatusCode(); //get status code
    
            //send message/result to the user
            $detailArchives = json_decode($response->getBody());
            $listArchives = '';
            if($detailArchives->data != '0'){
                if($detailArchives->data->type == 'incoming_mail'){
                    $listArchives .= "Asal Surat "."\n".$detailArchives->data->search."\n\n"."Nomor Surat "."\n".$detailArchives->data->reference_number."\n\n"."Perihal"."\n".$detailArchives->data->subject."\n\n"."Nomor Surat"."\n".$detailArchives->data->reference_number."\n\n"."Tanggal Masuk"."\n".$detailArchives->data->date->{'$date'}->{'$numberLong'};
                }elseif ($detailArchives->data->type == 'outgoing_mail') {
                    $listArchives .= "Tujuan Surat "."\n".$detailArchives->data->search."\n\n"."Nomor Surat "."\n".$detailArchives->data->reference_number."\n\n"."Perihal"."\n".$detailArchives->data->subject."\n\n"."Nomor Surat"."\n".$detailArchives->data->reference_number."\n\n"."Tanggal Keluar"."\n".$detailArchives->data->date->{'$date'}->{'$numberLong'};
                }elseif ($detailArchives->data->type == 'file') {
                    $listArchives .= "Judul Berkas "."\n".$detailArchives->data->search."\n\n"."Keterangan "."\n".$detailArchives->data->desc."\n\n"."Tanggal Unggah"."\n".$detailArchives->data->date->{'$date'}->{'$numberLong'};
                }
            }else{
                $listArchives = 'Detail Arsip Tidak Ditemukan';
            }

            $result = $telegram->sendMessage([
                'chat_id' => $chatid, 
                'text' => $listArchives,
                'parse_mode' => 'html',
                'reply_markup' => $this->keyboard('show','latestarchieve'),
                'force_reply'   => true,
                'reply_to_message_id'   => $message_id
            ]);
            ////dd("berhasil di bagian detail arsip",$result);
        }else{
            //user is not authorized
            $this->setAuthorization($chatid, $username);
        }
    }

    public function setAuthorization($chatid, $username)
    {
        $keyboard = [
            [
                [
                    'text' => 'Authorize me',
                    'url'   => 'http://beta.kotakarsip.com/api/v1/auth/login?u='.$username//url login
                ]
            ]
        ];

        $reply_markup = $telegram->replyKeyboardMarkup([
            'inline_keyboard'   => $keyboard
        ]);

        $response = $telegram->sendMessage([
            'chat_id'   => $chatid,
            'text'  => 'Untuk dapat mengakses fitur Kotak Arsip, silahkan klik tombol dibawah ini',
            'reply_markup'  => $reply_markup
        ]);

        ////dd($response);
    }

    public function commandDefault($chatid, $name, $fromUsername, $message_id)
    {
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        
        $this->keyTyping($telegram,$chatid);

        $response = $telegram->sendMessage([
            'chat_id' => $chatid,
            'text'  => 'Maaf. Kata tidak ditemukan. /help untuk informasi tentang bot ini',
            'reply_markup'  => $this->keyboard('hide'),
            'reply_to_message_id'   => $message_id,
            'force_reply'   => true
        ]);
        ////dd($response);
    }

    public function commandDeleteLatestMessage($chatid, $name, $fromUsername, $message_id, $text)
    {
        # code...
    }

    public function commandShowFindArchieve($chatid, $name, $fromUsername)
    {
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));

        $this->keyTyping($telegram,$chatid);

        $auth = $this->isAuthorized($fromUsername);

        if($auth == 'success'){ //user is authorized

            $result = $telegram->sendMessage([
                'chat_id' => $chatid, 
                'text' => 'Masukkan Keyword berupa perihal surat atau nama file',
                'parse_mode' => 'html',
                'reply_markup' => $this->keyboard('hide'),
                'force_reply'   => true
            ]);

            ////dd("aa",$result);
        }else{
            //user is not authorized
            $this->setAuthorization($chatid, $fromUsername);
        }
    }

    public function commandFindArchieve($chatid, $name, $fromUsername, $text,$message_id)
    {
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));

        $this->keyTyping($telegram,$chatid);

        $auth = $this->isAuthorized($fromUsername);

        if($auth == 'success'){ //user is authorized

            //find new archieve of the user
            $client = new Client();
            
            $url = 'http://beta.kotakarsip.com/api/v1/find?q='.$text.'&u='.$fromUsername; //set url for request
            $response = $client->request('GET', $url);
            //and return true or false

            $code = $response->getStatusCode(); //get status code
    
            //send message/result to the user
            $data = json_decode($response->getBody());
            //$pathUrl = 'http://beta.kotakarsip.com/public/files/'.$data->data->archieve[0]->id_company.'/'.$data->data->archieve[0]->type.'/'.$data->data->archieve[0]->files[0];
            /* $response = $telegram->sendPhoto([
                'chat_id' => $chatid, 
                'photo' => 'http://localhost:8000/contoh.jpg',
                'caption' => 'Halo'
                ]);
                
            $messageId = $response->getMessageId();
            ////dd($response); */
            
            $result = "Hasil Pencarian : ".$text."\n\n";
            $listArchives = [];
            foreach ($data->data->archieve as $key => $value) {
                if($value->type == 'incoming_mail'){
                    $result .= ($key + 1)."️⃣  "." Surat Masuk - ".$value->from."\n\n";
                    array_push($listArchives,['key' => $key +1, 'value' => $value->from,'id'=>$value->_id]);
                    if ($key >= 7) {
                        break;
                    }
                }elseif($value->type == 'outgoing_mail'){
                    $result .= ($key + 1)."️⃣  "." Surat Keluar - ".$value->to."\n\n";
                    array_push($listArchives,['key' => $key +1, 'value' => $value->to,'id'=>$value->_id]);
                    if ($key >= 7) {
                        break;
                    }
                }elseif($value->type == 'file'){
                    $result .= ($key + 1)."️⃣  "." Berkas - ".$value->name."\n\n";
                    array_push($listArchives,['key' => $key +1, 'value' => $value->name,'id'=>$value->_id]);
                    if ($key >= 7) {
                        break;
                    }
                }
            }

            $lastFindKeyword = new FindArchieve();
            $lastFindKeyword->user_telegram = $fromUsername;
            $lastFindKeyword->chat_id = $chatid;
            $lastFindKeyword->last_command = $text."\n\n Masukkan Nomor Arsip Untuk Detail";
            $lastFindKeyword->last_find_archieve = $result;
            $lastFindKeyword->array_value = json_encode($listArchives);
            $lastFindKeyword->save();

            //last find keyword
            $lastCommandOnFindArchieve = Telegram::where('chat_id',$chatid)->where('user_telegram',$fromUsername)->latest()->first();
            if($lastCommandOnFindArchieve->last_command == 'Cari Arsip' || $lastCommandOnFindArchieve->last_command == '/cariarsip'){
                $lastCommandOnFindArchieve->added_find_keyword = $text;
                $lastCommandOnFindArchieve->save();
            }
            //////dd("last Keyword",$lastFindKeyword, $lastCommandOnFindArchieve);
            /*
            $listArchives = '';
            if($detailArchives != '0'){
                if($detailArchives->type == 'incoming_mail'){
                    $listArchives .= "Asal Surat "."\n".$detailArchives->search."\n\n"."Nomor Surat "."\n".$detailArchives->reference_number."\n\n"."Perihal"."\n".$detailArchives->subject."\n\n"."Nomor Surat"."\n".$detailArchives->reference_number."\n\n"."Tanggal Masuk"."\n".$detailArchives->date->{'$date'}->{'$numberLong'};
                }elseif ($detailArchives->type == 'outgoing_mail') {
                    $listArchives .= "Tujuan Surat "."\n".$detailArchives->search."\n\n"."Nomor Surat "."\n".$detailArchives->reference_number."\n\n"."Perihal"."\n".$detailArchives->subject."\n\n"."Nomor Surat"."\n".$detailArchives->reference_number."\n\n"."Tanggal Keluar"."\n".$detailArchives->date->{'$date'}->{'$numberLong'};
                }elseif ($detailArchives->type == 'file') {
                    $listArchives .= "Judul Berkas "."\n".$detailArchives->search."\n\n"."Keterangan "."\n".$detailArchives->desc."\n\n"."Tanggal Unggah"."\n".$detailArchives->date->{'$date'}->{'$numberLong'};
                }
    
            }
            $listArchives = 'Detail Arsip Tidak Ditemukan'; */


            $result = $telegram->sendMessage([
                'chat_id' => $chatid, 
                'text' => $result,
                'parse_mode' => 'html',
                'reply_markup' => $this->keyboard('show','detail'),
                'force_reply'   => true,
                'reply_to_message_id'   => $message_id
            ]);

            ////dd("aa",$result);
        }else{
            //user is not authorized
            $this->setAuthorization($chatid, $fromUsername);
        }
    }

    public function commandHelp($chatid, $name, $fromUsername, $text, $message_id)
    {
        
    }

    public function commandGetLastLatestArchieve($chatid, $name, $fromUsername, $message_id, $text, $lastLatestCommand)
    {
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));

        $this->keyTyping($telegram,$chatid);

        $lastLatestArchieve = LatestArchieve::where('user_telegram',$fromUsername)
                                ->where('chat_id', $chatid)
                                ->where('last_command',$lastLatestCommand)
                                ->orderBy('id','desc')
                                ->get();
        //////dd($lastLatestArchieve[0]->last_archieve);

        $result = $telegram->sendMessage([
            'chat_id' => $chatid,
            'text' => $lastLatestArchieve[0]->last_archieve,
            'parse_mode' => 'html',
            'reply_markup' => $this->keyboard('show','detail'),
            'force_reply'   => true
        ]);

        ////dd("berhasil cuy",$result, $lastLatestArchieve);
    }

    public function commandGetLastFindArchieve($chatid, $name, $fromUsername, $message_id, $text, $lastLatestCommand)
    {
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));

        $this->keyTyping($telegram,$chatid);

        $lastLatestArchieve = FindArchieve::where('user_telegram',$fromUsername)
                                ->where('chat_id', $chatid)
                                //->where('last_command',$lastLatestCommand)
                                ->latest()
                                ->first();
        //////dd($lastLatestArchieve);

        $result = $telegram->sendMessage([
            'chat_id' => $chatid,
            'text' => $lastLatestArchieve->last_find_archieve,
            'parse_mode' => 'html',
            'reply_markup' => $this->keyboard('show','detail'),
            'force_reply'   => true
        ]);

        ////dd("berhasil cuy",$result, $lastLatestArchieve);
    }

    public function commandDefaultMenu($chatid, $name, $fromUsername, $message_id, $text,$lastLatestCommand)
    {
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));

        $this->keyTyping($telegram,$chatid);

        $response = $telegram->sendMessage([
            'chat_id' => $chatid,
            'text'  => 'Hai '.$name.', Selamat datang di KotakArsip. Masukkan Kata Kunci ',
            'reply_markup'  => $this->keyboard('show','custom'),
            //'reply_to_message_id'   => $message_id,
            'force_reply'   => true
        ]);
        ////dd($response);
    }

    public function commandDetailFindArchive($chatid, $name, $fromUsername, $message_id, $text)
    {
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        $this->keyTyping($telegram,$chatid);

        $auth = $this->isAuthorized($fromUsername);

        if($auth == 'success'){ //user is authorized
            //find new archieve of the user
            $client = new Client();

            $lastFindArchieve = FindArchieve::where('user_telegram',$fromUsername)
                                ->where('chat_id', $chatid)
                                ->latest()
                                ->first();
            $detail = 0;
            foreach (json_decode($lastFindArchieve->array_value) as $key => $value) {
                if( (int)$text == $key+1){
                    $detail = $value->id;
                    break;
                }
            }
            //////dd($lastFindArchieve, $text, $detail);
            $url = 'http://beta.kotakarsip.com/api/v1/latest/'.$detail.'/detail?u='; //set url for request

            $response = $client->request('GET', $url.$fromUsername);
            //and return true or false

            $code = $response->getStatusCode(); //get status code
    
            //send message/result to the user
            $detailArchives = json_decode($response->getBody());
            $listArchives = '';
            if($detailArchives->data != '0'){
                if($detailArchives->data->type == 'incoming_mail'){
                    $listArchives .= "Asal Surat "."\n".$detailArchives->data->search."\n\n"."Nomor Surat "."\n".$detailArchives->data->reference_number."\n\n"."Perihal"."\n".$detailArchives->data->subject."\n\n"."Nomor Surat"."\n".$detailArchives->data->reference_number."\n\n"."Tanggal Masuk"."\n".$detailArchives->data->date->{'$date'}->{'$numberLong'};
                }elseif ($detailArchives->data->type == 'outgoing_mail') {
                    $listArchives .= "Tujuan Surat "."\n".$detailArchives->data->search."\n\n"."Nomor Surat "."\n".$detailArchives->data->reference_number."\n\n"."Perihal"."\n".$detailArchives->data->subject."\n\n"."Nomor Surat"."\n".$detailArchives->data->reference_number."\n\n"."Tanggal Keluar"."\n".$detailArchives->data->date->{'$date'}->{'$numberLong'};
                }elseif ($detailArchives->data->type == 'file') {
                    $listArchives .= "Judul Berkas "."\n".$detailArchives->data->search."\n\n"."Keterangan "."\n".$detailArchives->data->desc."\n\n"."Tanggal Unggah"."\n".$detailArchives->data->date->{'$date'}->{'$numberLong'};
                }
            }else{
                $listArchives = 'Detail Arsip Tidak Ditemukan';
            }
            $result = $telegram->sendMessage([
                'chat_id' => $chatid, 
                'text' => $listArchives,
                'parse_mode' => 'html',
                'reply_markup' => $this->keyboard('show','findarchieve'),
                'force_reply'   => true,
                'reply_to_message_id'   => $message_id
            ]);

            ////dd("aa",$result);
        }else{
            //user is not authorized
            $this->setAuthorization($chatid, $username);
        }        
    }


    //webhook
    public function setWebhook()
    {
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        $response = $telegram->setwebhook(['url'   => 'https://553da241.ngrok.io/telegram/webhook']);

        return 'ok';
    }
    public function webhook(Request $req)
    {
        return $req;
    }
    /* public function webhook(Request $req)
    {
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        
        $fromid = $req['message']['from']['id'];
        $chatid = $req['message']['chat']['id'];
        $text = $req['message']['text']; // get the user sent text
        $name = $req['message']['from']['first_name'];
        $fromUsername = $req['message']['from']['username']; //get username telegram
        $callback_id = 0;
        $message_id = $req['message']['message_id'];
        $reply = (isset($req['reply_to_message']) ? $req['reply_to_message']: '' );
        $lastCommand = '';
        $telegram = new Telegram();
        if($text == 'Arsip Terbaru' || $text == 'Cari Arsip' || $text == '/cariarsip' || $text == '/arsipterbaru'){
            $telegram->user_telegram = $fromUsername;
            $telegram->chat_id = $chatid;
            $telegram->last_command = $text;
            $telegram->added_find_keyword = '';
            $telegram->save();
        }
        
        $lastCommand = Telegram::where('chat_id',$chatid)->where('user_telegram',$fromUsername)->latest()->first();
        if($text == '/start'){
            $this->commandWelcomingText($chatid, $name, $fromUsername, $message_id);
        }elseif($text == '/stop'){
            $this->commandremoveAccessUser($chatid, $name, $fromUsername);
        }elseif($text == 'Arsip Terbaru'){
            $this->commandLatestArchieve($chatid, $text, $fromUsername);
        }elseif($text == 'Cari Arsip'){
            $this->commandShowFindArchieve($chatid, $name, $fromUsername);
        }elseif(!$lastCommand){
            if($lastCommand->last_command == 'Arsip Terbaru' || $lastCommand->last_command == '/arsipterbaru'){
                $lastLatestCommand = $lastCommand->last_command;
                switch ($text) {
                    case '1':
                        $this->commandDetailArchive($chatid, $name, $fromUsername, $message_id, $text);
                        break;
                    case '2':
                        $this->commandDetailArchive($chatid, $name, $fromUsername, $message_id, $text);
                        break;
                    case '3':
                        $this->commandDetailArchive($chatid, $name, $fromUsername, $message_id, $text);
                        break;
                    case '4':
                        $this->commandDetailArchive($chatid, $name, $fromUsername, $message_id, $text);
                        break;
                    case '5':
                        $this->commandDetailArchive($chatid, $name, $fromUsername, $message_id, $text);
                        break;
                    case '6':
                        $this->commandDetailArchive($chatid, $name, $fromUsername, $message_id, $text);
                        break;
                    case 'Menu':
                        $this->commandDefaultMenu($chatid, $name, $fromUsername, $message_id, $text,$lastLatestCommand);
                        break;
                    case '<< Kembali' :
                        $this->commandGetLastLatestArchieve($chatid, $name, $fromUsername, $message_id, $text,$lastLatestCommand);
                        break;
                    default:
                        $this->commandDefault($chatid, $name, $fromUsername, $message_id);
                        break;
                }
            }elseif($lastCommand->last_command == 'Cari Arsip' || $lastCommand->last_command == '/cariarsip') {
                $lastLatestCommand = $lastCommand->last_command;
                if($lastCommand->added_find_keyword == ''){
                    //tidak ada added find keyword
                    $this->commandFindArchieve($chatid, $name, $fromUsername, $text, $message_id);
                }else{
                    //show detail cari arsip
                    switch ($text) {
                        case '1':
                            $this->commandDetailFindArchive($chatid, $name, $fromUsername, $message_id, $text);
                            break;
                        case '2':
                            $this->commandDetailFindArchive($chatid, $name, $fromUsername, $message_id, $text);
                            break;
                        case '3':
                            $this->commandDetailFindArchive($chatid, $name, $fromUsername, $message_id, $text);
                            break;
                        case '4':
                            $this->commandDetailFindArchive($chatid, $name, $fromUsername, $message_id, $text);
                            break;
                        case '5':
                            $this->commandDetailFindArchive($chatid, $name, $fromUsername, $message_id, $text);
                            break;
                        case '6':
                            $this->commandDetailFindArchive($chatid, $name, $fromUsername, $message_id, $text);
                            break;
                        case 'Menu':
                            $this->commandDefaultMenu($chatid, $name, $fromUsername, $message_id, $text,$lastLatestCommand);
                            break;
                        case '<< Kembali' :
                            $this->commandGetLastFindArchieve($chatid, $name, $fromUsername, $message_id, $text,$lastLatestCommand);
                            break;
                        default:
                            $this->commandDefault($chatid, $name, $fromUsername, $message_id);
                            break;
                    }
                    //$this->commandDetailFindArchive($chatid, $name, $fromUsername, $message_id, $text);
                }
            }else{
                $this->commandDefault($chatid, $name, $fromUsername, $message_id);    
            }
        }elseif ($text == '/help') {
            $this->commandHelp($chatid, $name, $fromUsername, $text, $message_id);
        }else{
            $this->commandDefault($chatid, $name, $fromUsername, $message_id);
        }
    } */

    public function removeWebhook()
    {
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        $response = $telegram->removeWebhook();
    }
}
