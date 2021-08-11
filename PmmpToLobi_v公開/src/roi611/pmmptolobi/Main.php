<?php

namespace roi611\pmmptolobi;

use pocketmine\plugin\PluginBase;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;

use pocketmine\Server;
use pocketmine\Player;

use pocketmine\utils\Config;

use delion\LobiAPI\LobiAPI;

use pocketmine\utils\TextFormat;

use pocketmine\scheduler\Task;

class Main extends PluginBase implements Listener
{

    /** @var Config */
    public static $config;

    public $api;

    public function onEnable()
    {

        date_default_timezone_set("Asia/Tokyo");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        self::$config = new Config($this->getDataFolder()."DATA.yml", Config::YAML, array(
            "Mail" => "テスト@テスト.gg",//ログイン用
            "Pass" => "aiueo12345",//ログイン用
            "sendGroup" => "https://web.lobi.co/group/  ->  000000000000000000000000000000000000000  <-",//メッセージを送信するグループID
            "getGroup" => "https://web.lobi.co/group/  ->  000000000000000000000000000000000000000  <-",//メッセージを取得するグループID
            "LoadTime" => 10,//メッセージを取得する周期
            "LoadThreads" => 1,//一回のループで取得するメッセージ数
            "UID" => "変更しないでください",//変更しないでください
            "JoinMessage" => "%nameがサーバーに参加しました！<br>(%now/%max)",//プレイヤー参加時のメッセージ
            "QuitMessage" => "%nameがサーバーから退出しました.....<br>(%reason)<br>また来てくださいね！<br>(%now/%max)",//プレイヤー退出時のメッセージ
            "ChatMessage" => "<%name> %message",//LOBIに送信するチャットメッセージ
            "SendMessage" => "[LOBI] <%name> %message",//Minecraftに送信するチャットメッセージ
            "ServerStart" => "サーバーが起動しました！",//サーバー起動時のメッセージ
            "ServerStop" => "サーバーが停止しました.....<br>起動までお待ちください！",//サーバー停止時のメッセージ
            "ReadMessage" => "送信しました",//送信成功時のメッセージ
            "FailureMessage" => "送信できませんでした",//送信失敗時のメッセージ
            "ServerMessageShout" => true,//サーバーの起動・停止時のシャウトのするか
            "MessageShout" => false,//プレイヤーチャットや参加メッセージのシャウトをするか
            "Time" =>"変更しないでください",//変更しないでください
            "Name" => "変更しないでください"//変更しないでください
        ));

        if(Main::$config->get("Mail") !== "テスト@テスト.gg" && Main::$config->get("sendGroup") !== "https://web.lobi.co/group/  ->  000000000000000000000000000000000000000  <-" && Main::$config->get("sendGroup") !== "https://web.lobi.co/group/  ->  000000000000000000000000000000000000000  <-"){

            $api = new LobiAPI();
            $this->api = $api;

            if($api->Login(self::$config->get("Mail"),self::$config->get("Pass"))){
            
                //ログイン成功
                $this->getLogger()->info(TextFormat::AQUA."ログイン成功\nこれ以降はアカウントを変更しないでください\n変更したい場合はconfigファイルごと消去してください");
                $text = Main::$config->get("ServerStart");
                $text = str_replace("<br>","\n",$text);
                $api->MakeThread(Main::$config->get("sendGroup"),$text,Main::$config->get("ServerMessageShout"));//LOBIにメッセージを作成

                /* ↓ UID,Name,Time の設定 ↓ */

                if(Main::$config->get("UID") === "変更しないでください"){

                    $user = $api->GetMe();
                    $uid = $user->{'uid'};
                    Main::$config->set("UID",$uid);
                    Main::$config->save();

                }

                if(Main::$config->get("Name") === "変更しないでください"){

                    $user = $api->GetMe();
                    $uid = $user->{'name'};
                    Main::$config->set("Name",$uid);
                    Main::$config->save();

                }

                Main::$config->set("Time",date("Y-m-d H:i:s",time()));
                Main::$config->save();
                $this->getScheduler()->scheduleRepeatingTask(new sendTask($api),Main::$config->get("LoadTime"));

            } else {

                //ログイン失敗
                $this->getLogger()->info(TextFormat::RED.'ログイン失敗');
                $this->getLogger()->info("§cこのプラグインを無効化致します。§r");
                $this->getServer()->getPluginManager()->disablePlugin($this);

            }

        } else {

            $this->getLogger()->info(TextFormat::RED.'Configを正しく入力してから有効にしてください');
            $this->getLogger()->info("§cこのプラグインを無効化致します。§r");
            $this->getServer()->getPluginManager()->disablePlugin($this);

        }

    }



    public function onDisable(){

        if($this->api !== null){

            $text = Main::$config->get("ServerStop");
            $text = str_replace("<br>","\n",$text);
            $this->api->MakeThread(Main::$config->get("sendGroup"),$text,Main::$config->get("ServerMessageShout"));//メッセージを作成

            $messages = $this->api->getThreads(Main::$config->get("getGroup"),3);//履歴のメッセージを作成

            foreach($messages as $data){

                $message = $data->{"message"};

                if($message === "この投稿（チャット）はグループリーダーもしくはサブリーダーによって削除されました。"){
                    continue;
                }

                if(empty($message)){
                    continue;
                }

                $user = $data->{'user'};
                $uid = $user->{'uid'};

                if($uid === Main::$config->get("UID")){//ログインしているアカウントだったら
                    continue;
                }

                $id = $data->{'id'};
                $replies = $this->api->GetReplies(Main::$config->get("getGroup"),$id);//スレッドの返信メッセージをすべて取得
                
                if(!(empty($replies['chats']))){//返信メッセージがあるか、

                    $bool = true;

                    foreach($replies['chats'] as $reply){

                        $ruser = $reply['user'];
                        $ruid = $ruser['uid'];

                        if($ruid == Main::$config->get("UID")){//返信にログインしているユーザーのメッセージがあるか、

                            $bool = false;

                        }

                    }

                    if($bool){//処理済みか、

                        $this->api->Reply(Main::$config->get("getGroup"),$id,Main::$config->get("FailureMessage"));//スレッドに返信

                    }

                } else {

                    $this->api->Reply(Main::$config->get("getGroup"),$id,Main::$config->get("FailureMessage"));//スレッドに返信

                }

            }

        }

    }



    public function onJoin(PlayerJoinEvent $event){

        /** ↓ 参加時にLOBIにスレッドを立てる ↓ */
        $text = Main::$config->get("JoinMessage");
        $text = str_replace("<br>","\n",$text);
        $text = str_replace("%now",count($this->getServer()->getOnlinePlayers()),$text);
        $text = str_replace("%max",$this->getServer()->getMaxPlayers(),$text);
        $text = str_replace("%name",$event->getPlayer()->getName(),$text);
        $this->api->MakeThread(Main::$config->get("sendGroup"),$text,Main::$config->get("MessageShout"));

    }



    public function onQuit(PlayerQuitEvent $event){

        /** ↓ 退出時にLOBIにスレッドを立てる ↓ */
        $text = Main::$config->get("QuitMessage");
        $text = str_replace("<br>","\n",$text);
        $count = count($this->getServer()->getOnlinePlayers()) - 1;
        $text = str_replace("%now",$count,$text);
        $text = str_replace("%max",$this->getServer()->getMaxPlayers(),$text);
        $text = str_replace("%name",$event->getPlayer()->getName(),$text);
        $text = str_replace("%reason",$event->getQuitReason(),$text);
        $this->api->MakeThread(Main::$config->get("sendGroup"),$text,Main::$config->get("MessageShout"));

    }



    public function onChat(PlayerChatEvent $event){
    
        $player = $event->getPlayer();
        $name = $player->getName();
        /** ↓ 色文字を無効化 ↓ */
        $message = $event->getMessage();
        $message = str_replace("§0","",$message);
        $message = str_replace("§1","",$message);
        $message = str_replace("§2","",$message);
        $message = str_replace("§3","",$message);
    
        $message = str_replace("§4","",$message);
        $message = str_replace("§5","",$message);
        $message = str_replace("§6","",$message);
        $message = str_replace("§7","",$message);
        $message = str_replace("§8","",$message);
        $message = str_replace("§9","",$message);
        $message = str_replace("§a","",$message);
        $message = str_replace("§b","",$message);
        $message = str_replace("§c","",$message);
        $message = str_replace("§d","",$message);
        $message = str_replace("§e","",$message);
        $message = str_replace("§f","",$message);
        $message = str_replace("§k","",$message);
        $message = str_replace("§l","",$message);
        $message = str_replace("§m","",$message);
        $message = str_replace("§n","",$message);
        $message = str_replace("§o","",$message);
        $message = str_replace("§r","",$message);

        /** ↓ チャット時にLOBIにスレッドを立てる ↓ */

        $text = Main::$config->get("ChatMessage");
        $text = str_replace("%name",$name,$text);
        $text = str_replace("%message",$message,$text);
        $text = str_replace("<br>","\n",$text);

        $this->api->MakeThread(Main::$config->get("sendGroup"),$text,Main::$config->get("MessageShout"));

    }



}








class sendTask extends Task{

    public function __construct($api){
        $this->api = $api;
    }
    
    public function onRun(int $currentTick){//定期的にメッセージを取得し処理を行う

        $messages = $this->api->getThreads(Main::$config->get("getGroup"),Main::$config->get("LoadThreads"));//スレッドを取得

        if(!(empty($messages))){//メッセージがあるか、

            foreach($messages as $data){

                $message = $data->{"message"};

                if(empty($message)){
                    continue;
                }

                if($message === "この投稿（チャット）はグループリーダーもしくはサブリーダーによって削除されました。"){
                    continue;
                }

                $user = $data->{'user'};
                $uid = $user->{'uid'};

                if($uid === Main::$config->get("UID")){//ログインしているユーザーか、
                    continue;
                }

                $id = $data->{'id'};
                $replies = $this->api->GetReplies(Main::$config->get("getGroup"),$id);//スレッドの返信をすべて取得
                
                if(!(empty($replies['chats']))){

                    $bool = true;

                    foreach($replies['chats'] as $reply){

                        $ruser = $reply['user'];
                        $ruid = $ruser['uid'];

                        if($ruid === Main::$config->get("UID")){//返信にログインしているユーザーのメッセージがあるか、

                            $bool = false;

                        }

                    }

                    if($bool){

                        $time = $data->{'created_date'};
                        $time = strtotime(date("Y-m-d H:i:s",$time));
                        $old = strtotime(Main::$config->get("Time"));
                        if($old > $time){//サーバー起動より前のメッセージか、

                            $this->api->Reply(Main::$config->get("getGroup"),$id,Main::$config->get("FailureMessage"));
                            continue;

                        } else {

                            /** ↓ Minecraftに送信 ↓ */
                            $name = $user->{'name'};
                            $text = Main::$config->get("SendMessage");
                            $text = str_replace("%name",$name,$text);
                            $text = str_replace("%message",$message,$text);
                            $this->api->Reply(Main::$config->get("getGroup"),$id,Main::$config->get("ReadMessage"));//返信する
                            $api = new LobiChatEvent($name,$message);
                            $api->call();//イベントを発火
                            Server::getInstance()->broadcastMessage($text);//Minecraftに送信
                            continue;

                        }

                    }

                } else {

                    $time = $data->{'created_date'};
                    $time = strtotime(date("Y-m-d H:i:s",$time));
                    $old = strtotime(Main::$config->get("Time"));
                    if($old > $time){//サーバー起動より前のメッセージか、

                        $this->api->Reply(Main::$config->get("getGroup"),$id,Main::$config->get("FailureMessage"));//返信を追加
                        continue;

                    } else {

                        /** ↓ Minecraftに送信 ↓ */
                        $name = $user->{'name'};
                        $text = Main::$config->get("SendMessage");
                        $text = str_replace("%name",$name,$text);
                        $text = str_replace("%message",$message,$text);
                        $this->api->Reply(Main::$config->get("getGroup"),$id,Main::$config->get("ReadMessage"));//返信
                        $api = new LobiChatEvent($name,$message);
                        $api->call();//イベントを発火
                        Server::getInstance()->broadcastMessage($text);//Minecraftに送信
                        continue;

                    }

                }

            }

        }

    }

}