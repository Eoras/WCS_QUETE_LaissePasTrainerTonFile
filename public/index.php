<?php
include 'header.php';

/*  $_FILES['fichier']['name'];         // Contient le nom d'origine du fichier (sur le poste du client)
    $_FILES['fichier']['tmp_name'];     // Nom temporaire du fichier dans le dossier temporaire du système (sur le serveur)
    $_FILES['fichier']['type'];         // Contient le type MIME du fichier (plus fiable que l'extension)
    $_FILES['fichier']['size'];         // Contient la taille du fichier en octets
    $_FILES['fichier']['error'];        // Contient le code de l'erreur (le cas échéant) */

// Tableau des différentes erreurs d'upload PHP
$fileUploadErrors = [
    0 => "Aucune erreur, le téléchargement est correct.",
    1 => "La taille du fichier téléchargé excède la valeur de upload_max_filesize, configurée dans le php.ini",
    2 => "La taille du fichier téléchargé excède la valeur de MAX_FILE_SIZE, qui a été spécifiée dans le formulaire HTML.",
    3 => "Le fichier n'a été que partiellement téléchargé.",
    4 => "Aucun fichier n'a été téléchargé.",
    6 => "Un dossier temporaire est manquant.",
    7 => "Échec de l'écriture du fichier sur le disque.",
    8 => "Une extension PHP a arrêté l'envoi de fichier. PHP ne propose aucun moyen de déterminer quelle extension est en cause. ",
];

// Liste des extentions autorisées
$valideExtensions = [
    'jpg',
    'png',
    'gif',
];

// Définir quelques variable de configuration:
$maxSize = 1048576; // Taille maximum du fichier 1048576 = 1mo.
$maxWidth = 300;    // Hauteur maximum du fichier
$maxHeight = 300;   // Largeur maximum du fichier
$uploadDir = 'upload/';  // Chemin vers un dossier sur le serveur qui va recevoir les fichiers uploadés (attention ce dossier doit être accessible en écriture)

// On verifie si le POST existe.
if (!empty($_POST['sendFile'])) {

    // Si lors du post il n'y a pas de fichier afficher une erreur.
    if (!empty($_FILES['fichier']['name'][0] != '')) {

        // Je boucle sur chaque fichier envoyé
        for ($i = 0; $i < count($_FILES['fichier']['name']); $i++) {
            // J'ajoute dans un tableau l'objet temporaire
            $tmpUploadFile = $_FILES['fichier']['tmp_name'][$i];

            // On récupère l'extention du fichier, strrchr jusqu'au "." et strlolower pour mettre tout en minuscule.
            $fileExtension = strtolower(strrchr($_FILES['fichier']['name'][$i], '.'));

            // Vérification des extentions (substr(string,1) retir le premier caractère (le . ici))
            if (!in_array(substr($fileExtension, 1), $valideExtensions)) {
                $errors[] = "L'extention <b>($fileExtension)</b> du fichier <b>" . $_FILES['fichier']['name'][$i] . "</b> n'est pas valide !";
            }

            // Vérification si on a bien des fichiers et s'il n'y a pas d'erreur.
            if ($tmpUploadFile != "" and empty($errors)) {

                // On sauvegarde les noms des fichiers avant de les déplacer (pour afficher les success)
                $savedNames[] = $_FILES['fichier']['name'][$i];

                // On enregistre le nouvel emplacement et le nouveau nom (uniqid avec un prefix image) et l'extention.
                $uploadFile = $uploadDir . uniqid("image") . $fileExtension;

                // On déplace le fichier du dossier tmp avec le nouveau nom.
                if (move_uploaded_file($tmpUploadFile, $uploadFile)) {

                    $newFiles[] = basename($uploadFile);
                    $msgValidations[] = "L'image <b>" . $savedNames[$i] . "</b> à bien été envoyée.<br/>";

                    // Ajoute en BDD
                    // On peut utiliser $savedNames pour les noms d'origine
                }
            }

            if ($_FILES['fichier']['error'][$i] > 0) {
                $errors[] = "Erreur lors du transfert de " . $_FILES['fichier']['name'][$i] . ".<br/>" . $fileUploadErrors[$_FILES['fichier']['error'][$i]] . ".";
            }
        }

    } else {
        $errors[] = "Vous devez ajouter au minimum 1 image";
    }
} // (!empty($_POST))

if (!empty($_POST['idDelete'])) {
    $id = "upload/" . $_POST['idDelete'];
    if ($dossier = opendir('./upload/')) {
        if (file_exists($id)){
            unlink($id);
            $msgValidations[] = "L'image <b>" . $_POST['idDelete'] . "</b> à été supprimée avec succès!";
            closedir ($dossier);
        } else {
        $errors[] = "L'image <b>" . $_POST['idDelete'] . "</b> à déjà été supprimée!";
        }
    }
}

if (!empty($errors)) { ?>

    <div class="alert alert-danger">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <strong>Erreur<?= count($errors) > 1 ? "s" : "" ?> !!!</strong>
        <ul>
            <?php foreach ($errors as $error) : ?>
                <li><?= $error ?></li>
            <?php endforeach; ?>
        </ul>
    </div>

<?php }
if (!empty($msgValidations)) { ?>

    <div class="alert alert-success">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <strong>Super !!!</strong>
        <ul>
            <?php foreach ($msgValidations as $msgValidation) : ?>
                <li><?= $msgValidation ?></li>
            <?php endforeach; ?>
        </ul>
    </div>

<?php }
?>

<div class="well">
    <h3>Envoyer un fichier:</h3>
    <form action="" method="post" enctype="multipart/form-data">
        <!-- Permet une vérification côté serveur du MAX_FILE_SIZE ATTENTION, il faut vérifier côté serveur aussi
             1048576 = 1mo -->
        <input type="hidden" name="MAX_FILE_SIZE" value="1048576"/>
        <input type="hidden" name="sendFile" value="1"/>
        <input type="file" multiple="multiple" name="fichier[]"/><br/>
        <button class="btn btn-success btn-xs" type="submit">Envoyer</button>
        <p><i>1 MO Maximum! jpg, png ou gif seulement.</i></p>
    </form>
</div>

<div class="row">
    <?php
    $nb_fichier = 0;

    if ($dossier = opendir('./upload/')) {

        while (false !== ($fichier = readdir($dossier))) {

            if ($fichier != '.' && $fichier != '..' && $fichier != 'index.php') { ?>

                <div class="col-xs-3">
                    <div class="thumbnail">
                        <img src="upload/<?= $fichier ?>" alt="Image">
                        <div class="caption">
                            <h4><?= $fichier ?></h4>
                            <form action="" method="POST">
                                <input type="hidden" name="idDelete" value="<?= $fichier ?>" />
                                <button type="submit" class="btn btn-danger btnn btn-xs" role="button">Supprimer</button>
                            </form>
                        </div>
                    </div>
                </div>

            <?php }
        }
        closedir ($dossier);
    }

    ?>
</div>


<?php include 'footer.php'; ?>
