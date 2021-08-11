<?php

namespace roi611\pmmptolobi;

use pocketmine\event\plugin\PluginEvent;

class LobiChatEvent extends PluginEvent{

    public function __construct($name,$message){

        $this->name = $name;
        $this->message = $message;

    }

    public function getName(): string{
        return $this->name;
    }

    public function getMessage(){
        return $this->message;
    }

}