<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/sql.php';

$secretRecaptchaKey = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!empty($_POST['recaptcha-response'])) {
        $url = "https://www.google.com/recaptcha/api/siteverify?secret=$secretRecaptchaKey&response={$_POST['recaptcha-response']}";

        $response = file_get_contents($url);

        if (!empty($response) && !is_null($response)) {
            $data = json_decode($response);
            if ($data->success) {
                if (
                    isset($_POST['contact']['name']) && !empty($_POST['contact']['name']) &&
                    isset($_POST['contact']['email']) && !empty($_POST['contact']['email']) &&
                    isset($_POST['contact']['message']) && !empty($_POST['contact']['message'])
                ) {
                    $name = strip_tags($_POST['contact']['name']);
                    $email = strip_tags($_POST['contact']['email']);
                    $message = htmlspecialchars($_POST['contact']['message']);


                    echo "Message envoyé";

                    $dbco = connectDB();
                    saveMessage($dbco, $_POST['contact']);
                }
            }
        }
    }
} else {
    http_response_code(405);
    echo 'Méthode non autorisée';
}

dump($GLOBALS);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact</title>
    <link rel="stylesheet" href="public/bootstrap.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js" integrity="sha384-cuYeSxntonz0PPNlHhBs68uyIAVpIIOZZ5JqeqvYYIcEL727kskC66kF92t6Xl2V" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />

    <script src="https://www.google.com/recaptcha/enterprise.js?render=6LfwsI0nAAAAAA_0hgu7ybOApAXoGLr1_JsJUtv3"></script>
</head>

<body>
    <div class="container py-4">

        <form id="contactForm" method="POST">
            <div>
                <input type="hidden" id="recaptchaResponse" name="recaptcha-response">
            </div>

            <div class="mb-3">
                <label class="form-label" for="name">Name</label>
                <input class="form-control" id="name" name="contact[name]" type="text" placeholder="Name" />
            </div>

            <div class="mb-3">
                <label class="form-label" for="emailAddress">Email Address</label>
                <input class="form-control" id="emailAddress" name="contact[email]" type="email" placeholder="Email Address" />
            </div>

            <div class="mb-3">
                <label class="form-label" for="message">Message</label>
                <textarea class="form-control" id="message" name="contact[message]" type="text" placeholder="Message" style="height: 10rem;"></textarea>
            </div>


            <div class="d-grid">
                <button class="btn btn-primary btn-lg" type="submit">Submit</button>
            </div>

        </form>

    </div>
</body>

<script>
    grecaptcha.enterprise.ready(function() {
        grecaptcha.enterprise.execute('6LfwsI0nAAAAAA_0hgu7ybOApAXoGLr1_JsJUtv3', {
            action: 'homepage'
        }).then(function(token) {
            document.getElementById('recaptchaResponse').value = token
        });
    });
</script>

</html>