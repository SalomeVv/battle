<?php

function checkForm()
{
    $invalidValues = [];

    foreach ($_POST as $key => $value) {
        if (is_array($value)) {
            if (intval($value['attaque']) < 0) {
                $invalidValues[$key]['attaque'] = "L'attaque ne peut pas être inférieure à 0.";
            }
            if (intval($value['mana']) < 0) {
                $invalidValues[$key]['mana'] = "Le mana ne peut pas être inférieur à 0.";
            }
            if (intval($value['sante']) < 1) {
                $invalidValues[$key]['sante'] = "La santé doit être supérieure à 0.";
            }
        }
    }
    return $invalidValues;
}

function initStats()
{
    $player = $_POST['player'];
    $adversaire = $_POST['adversaire'];
    $player['maxSante'] = $player['sante'];
    $adversaire['maxSante'] = $adversaire['sante'];

    return [$player, $adversaire];
}

function getSession()
{
    $player = $_SESSION['player'] ?? null;
    $adversaire = $_SESSION['adversaire'] ?? null;
    $actions = $_SESSION['actions'] ?? [];

    return [$player, $adversaire, $actions];
}
function setSession($player, $adversaire, $actions)
{
    $_SESSION['player'] = $player;
    $_SESSION['adversaire'] = $adversaire;
    $_SESSION['actions'] = $actions;
}
function deleteSession()
{
    unset($_SESSION);
    session_destroy();
}



function attack(&$self, &$target)
{
    $target['sante'] = max(($target['sante'] - $self['attaque']), 0);

    return "{$self['name']} inflige {$self['attaque']} de dégats à {$target['name']}.";
}

function heal(&$self)
{
    $damage = $self['maxSante'] - $self['sante'];
    if ($damage < $self['mana']) {
        $self['sante'] = $self['maxSante'];
        $self['mana'] -= $damage;
        $recovered = $damage;
    } else {
        $self['sante'] += $self['mana'];
        $recovered = $self['mana'];
        $self['mana'] = 0;
    }

    return "{$self['name']} récupère $recovered de vie.";
}

function autoplay(&$self, &$target)
{
    if (($self['sante'] > $self['maxSante'] / 2) || ($target['sante'] < $self['attaque']) || ($self['mana'] == 0)) {
        return attack($self, $target);
    } else {
        return heal($self);
    }
}
