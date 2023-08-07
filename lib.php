<?php
require_once __DIR__ . '/player.class.php';
require_once __DIR__ . '/battle.class.php';

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

function getSession()
{
    $player = $_SESSION['player'] ?? null;
    $adversaire = $_SESSION['adversaire'] ?? null;
    $battle = $_SESSION['battle'] ?? null;

    return [$player, $adversaire, $battle];
}
function setSession($player, $adversaire, $battle)
{
    $_SESSION['player'] = $player;
    $_SESSION['adversaire'] = $adversaire;

    $battle->player = $player;
    $battle->adversaire = $adversaire;
    $_SESSION['battle'] = $battle;
}
function deleteSession()
{
    unset($_SESSION);
    session_destroy();
}


function getStatColors($data, $palette)
{
    $statColors = [];
    for ($i = 0, $j = 0; $i < sizeof($data); $i++, $j++) {
        if ($j >= sizeof($palette)) {
            $j = 0;
            $statColors[] = $palette[$j];
        } else {
            $statColors[] = $palette[$j];
        }
    }
    return $statColors;
}
