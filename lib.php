<?php

function getAvatarLeft($nom)
{
    echo '<img src="https://api.dicebear.com/6.x/lorelei/svg?flip=false&seed=' . $nom . '" alt="Avatar" class="avatar">';
}
function getAvatarRight($nom)
{
    echo '<img src="https://api.dicebear.com/6.x/lorelei/svg?flip=true&seed=' . $nom . '" alt="Avatar" class="avatar">';
}

function attack(&$from, &$to)
{
    $to['sante'] -= $from['attaque'];

    return "{$from['name']} inflige {$from['attaque']} de dégats à {$to['name']}.";
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

