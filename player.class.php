<?php

class Player
{
    public $name;
    public $attaque;
    public $mana;
    public $sante;
    public $currentMana;
    public $currentSante;

    public function __construct($name, $attaque, $mana, $sante)
    {
        $this->name = $name;
        $this->attaque = $attaque;
        $this->mana = $mana;
        $this->sante = $sante;
        $this->currentMana = $mana;
        $this->currentSante = $sante;
    }

    public function attack(&$target)
    {
        $target->currentSante = max(($target->currentSante - $this->attaque), 0);

        return "$this->name inflige $this->attaque de dégats à $target->name.";
    }
    public function heal()
    {
        $damage = $this->sante - $this->currentSante;
        if ($damage < $this->currentMana) {
            $this->currentSante = $this->sante;
            $this->currentMana -= $damage;
            $recovered = $damage;
        } else {
            $this->currentSante += $this->currentMana;
            $recovered = $this->currentMana;
            $this->currentMana = 0;
        }

        return "$this->name récupère $recovered de vie.";
    }
    public function autoplay(&$target)
    {
        if (($this->currentSante > $this->sante / 2) || ($target->currentSante < $this->attaque) || ($this->currentMana == 0)) {
            return $this->attack($target);
        } else {
            return $this->heal();
        }
    }
}
