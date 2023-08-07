<?php

class Battle {
    public $id;
    public $player;
    public $adversaire;
    public $actions = [];
    public $winner;

    public function __construct($id, $player, $adversaire)
    {
        $this->id = $id;
        $this->player = $player;
        $this->adversaire = $adversaire;
    }
    public function getWinner()
    {
       $this->winner =  $this->player->currentSante > $this->adversaire->currentSante ? $this->player : $this->adversaire;
       return $this->winner;
    }
}