<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/lib.php';
session_start();


list($player, $adversaire, $actions) = getSession();

if (isset($_POST['fight'])) {
    $invalid = checkForm();
    if (!empty($invalid)) {
        $player = null;
        $adversaire = null;
    } else {
        list($player, $adversaire) = initStats();
        setSession($player, $adversaire, $actions);
    }
}


if (isset($_POST['attaque'])) {
    $actions[] = attack($player, $adversaire);
    if ($adversaire['sante'] > 0) {
        $actions[] = autoplay($adversaire, $player);
    }
    setSession($player, $adversaire, $actions);
    $winner = $player['sante'] > $adversaire['sante'] ? $player['name'] : $adversaire['name'];
}
if (isset($_POST['soin'])) {
    $actions[] = heal($player);
    $actions[] = autoplay($adversaire, $player);
    setSession($player, $adversaire, $actions);
    $winner = $player['sante'] > $adversaire['sante'] ? $player['name'] : $adversaire['name'];
}
if (isset($_POST['restart'])) {
    deleteSession();
    list($player, $adversaire) = getSession();
}

dump($GLOBALS);
?>

<html lang="fr">

<head>
    <title>Battle</title>
    <link rel="stylesheet" href="public/bootstrap.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js" integrity="sha384-cuYeSxntonz0PPNlHhBs68uyIAVpIIOZZ5JqeqvYYIcEL727kskC66kF92t6Xl2V" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />

</head>

<body>
    <div class="container">
        <audio id="fight-song" src="fight.mp3"></audio>
        <audio id="hadoudken-song" src="Haduken.mp3"></audio>
        <audio id="fatality-song" src="fatality.mp3"></audio>
        <h1 class="animate__animated animate__rubberBand">Battle</h1>

        <?php
        if (!$player || !$adversaire) {

        ?>
            <div id="prematch">
                <form id='formFight' action="index.php" method="post">

                    <div>
                        Joueur <br>
                        <div class="errors">
                            <ul>
                                <?php foreach ($invalid["player"] ?? [] as $error) { ?>
                                    <li class="text-danger"><?php echo $error ?></li>
                                <?php } ?>
                            </ul>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label">Name</label>
                                <input required type="text" class="form-control" name="player[name]" value="<?php if (isset($_POST["player"]["name"])) {
                                                                                                                echo $_POST["player"]["name"];
                                                                                                            } else {
                                                                                                                echo "";
                                                                                                            } ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Attaque</label>
                                <input required type="number" class="form-control <?php if (isset($invalid["player"]["attaque"])) {
                                                                                        echo "is-invalid";
                                                                                    } ?>" value="<?php if (isset($_POST["player"]["attaque"])) {
                                                                                                        echo $_POST["player"]["attaque"];
                                                                                                    } else {
                                                                                                        echo "100";
                                                                                                    } ?>" name="player[attaque]">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Mana</label>
                                <input required type="number" class="form-control  <?php if (isset($invalid["player"]["mana"])) {
                                                                                        echo "is-invalid";
                                                                                    } ?>" value="<?php if (isset($_POST["player"]["mana"])) {
                                                                                                        echo $_POST["player"]["mana"];
                                                                                                    } else {
                                                                                                        echo "100";
                                                                                                    } ?>" name="player[mana]">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Santé</label>
                                <input required type="number" class="form-control  <?php if (isset($invalid["player"]["sante"])) {
                                                                                        echo "is-invalid";
                                                                                    } ?>" value="<?php if (isset($_POST["player"]["sante"])) {
                                                                                                        echo $_POST["player"]["sante"];
                                                                                                    } else {
                                                                                                        echo "100";
                                                                                                    } ?>" name="player[sante]">
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div>
                        Adversaire <br>
                        <div class="errors">
                            <ul>
                                <?php foreach ($invalid["adversaire"] ?? [] as $error) { ?>
                                    <li class="text-danger"><?php echo $error ?></li>
                                <?php } ?>
                            </ul>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label">Name</label>
                                <input required type="text" class="form-control" name="adversaire[name]" value="<?php if (isset($_POST["player"]["name"])) {
                                                                                                                    echo $_POST["player"]["name"];
                                                                                                                } else {
                                                                                                                    echo "";
                                                                                                                } ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Attaque</label>
                                <input required type="number" class="form-control <?php if (isset($invalid["adversaire"]["attaque"])) {
                                                                                        echo "is-invalid";
                                                                                    } ?>" value="<?php if (isset($_POST["adversaire"]["attaque"])) {
                                                                                                        echo $_POST["adversaire"]["attaque"];
                                                                                                    } else {
                                                                                                        echo "100";
                                                                                                    } ?>" name="adversaire[attaque]">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Mana</label>
                                <input required type="number" class="form-control <?php if (isset($invalid["adversaire"]["mana"])) {
                                                                                        echo "is-invalid";
                                                                                    } ?>" value="<?php if (isset($_POST["adversaire"]["mana"])) {
                                                                                                        echo $_POST["adversaire"]["mana"];
                                                                                                    } else {
                                                                                                        echo "100";
                                                                                                    } ?>" name="adversaire[mana]">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Santé</label>
                                <input required type="number" class="form-control <?php if (isset($invalid["adversaire"]["sante"])) {
                                                                                        echo "is-invalid";
                                                                                    } ?>" value="<?php if (isset($_POST["adversaire"]["sante"])) {
                                                                                                        echo $_POST["adversaire"]["sante"];
                                                                                                    } else {
                                                                                                        echo "100";
                                                                                                    } ?>" name="adversaire[sante]">
                            </div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="d-flex justify-content-center">
                            <input name="fight" type="submit" value="FIGHT">
                        </div>
                    </div>
                </form>
            </div>

        <?php

        } else {

        ?>
            <div id="match" class="row gx-5">
                <h2>Match</h2>
                <div class="col-6 ">
                    <div class="position-relative float-end" id="player">
                        <img src="https://api.dicebear.com/6.x/lorelei/svg?flip=false&seed=<?php echo $player['name']; ?>" alt="Avatar" class="avatar">
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $player['sante']; ?>
                        </span>
                        <ul>
                            <li>Name : <?php echo $player['name']; ?> </li>
                            <li>Attaque : <?php echo $player['attaque']; ?> </li>
                            <li>Mana : <?php echo $player['mana']; ?> </li>
                        </ul>
                    </div>
                </div>
                <div class="col-6" id="adversaire">
                    <div class="position-relative float-start">
                        <img src="https://api.dicebear.com/6.x/lorelei/svg?flip=true&seed=<?php echo $adversaire['name']; ?>" alt="Avatar" class="avatar">
                        <span class="position-absolute top-0 start-0 translate-middle badge rounded-pill bg-danger">
                            <?php echo $adversaire['sante']; ?>
                        </span>
                        <ul>
                            <li>Name : <?php echo $adversaire['name']; ?> </li>
                            <li>Attaque : <?php echo $adversaire['attaque']; ?> </li>
                            <li>Mana : <?php echo $adversaire['mana']; ?> </li>
                        </ul>
                    </div>
                </div>
                <div id="combats">
                    <h2>Combat</h2>
                    <ul>
                        <?php

                        foreach ($actions as $action) {
                        ?>
                            <li>
                                <i class="fa-solid fa-khanda p-1"></i> <?php echo $action; ?>
                            </li>

                        <?php
                        }

                        ?>

                    </ul>

                    <?php
                    if ($player['sante'] > 0 && $adversaire['sante'] > 0) {
                    ?>
                        <form id='actionForm' action="index.php" method="post">
                            <div class="d-flex justify-content-center">
                                <input id="attaque" name="attaque" type="submit" value="Attaquer">
                                <input name="soin" type="submit" value="Se soigner">
                            </div>
                            <div class="d-flex justify-content-center">
                                <input id="restart" name="restart" type="submit" value="Stopper le combat">
                            </div>
                        </form>
                    <?php
                    } ?>
                </div>

                <?php

                if ($player['sante'] < 1 || $adversaire['sante'] < 1) {

                ?>
                    <div id="Resultats">
                        <h1>Résultat</h1>
                        <?php
                        echo "$winner est le vainqueur !";
                        ?>
                        <form class="d-flex justify-content-center" action="" method="post">
                            <input name="restart" type="submit" value="Nouveau combat">
                        </form>
                    <?php
                }
                    ?>

                    </div>
            </div>

        <?php
        }

        ?>
    </div>


    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let submitFight = document.querySelector("#fight");
            if (submitFight) {
                submitFight.addEventListener("click", function(event) {
                    event.preventDefault();
                    submitFight.classList.add("animate__animated");
                    submitFight.classList.add("animate__rubberBand");
                    setTimeout(function() {
                        submitFight.classList.remove("animate__rubberBand");
                    }, 1000);
                    let fight_song = document.getElementById("fight-song");
                    fight_song.play();
                    setTimeout(function() {
                        document.forms["formFight"].submit();
                    }, 500);
                })
            }

            let submitAttaque = document.querySelector("#attaque");
            let alreadyPlaySong = false;
            if (submitAttaque) {
                submitAttaque.addEventListener("click", function(event) {
                    if (alreadyPlaySong)
                        return true;
                    event.preventDefault();
                    let player = document.querySelector("#player")
                    player.classList.add("animate__animated");
                    player.classList.add("animate__rubberBand");
                    submitAttaque.classList.add("animate__animated");
                    submitAttaque.classList.add("animate__rubberBand");
                    setTimeout(function() {
                        submitAttaque.classList.remove("animate__rubberBand");
                        player.classList.remove("animate__rubberBand");
                    }, 1000);
                    let hadouken_song = document.getElementById("hadoudken-song");
                    hadouken_song.play();
                    alreadyPlaySong = true;
                    setTimeout(function() {
                        submitAttaque.click();
                    }, 1000);
                })
            }

            let submitRestart = document.querySelector("#restart");
            let alreadyPlaySongRestart = false;
            if (submitRestart) {
                submitRestart.addEventListener("click", function(event) {
                    if (alreadyPlaySongRestart)
                        return true;
                    event.preventDefault();
                    let fatality_song = document.getElementById("fatality-song");
                    fatality_song.play();
                    alreadyPlaySongRestart = true;
                    setTimeout(function() {
                        submitRestart.click();
                    }, 2000);
                })
            }
        });
    </script>
</body>
<style>
    .avatar {
        vertical-align: middle;
        width: 100px;
        border-radius: 50%;
    }
</style>

</html>