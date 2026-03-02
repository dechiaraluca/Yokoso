<?php
session_start();
require_once 'includes/config.php';
$user_id = $_SESSION['user_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>YOKOSO - Politique de confidentialité</title>
  <meta name="description" content="Politique de confidentialité de YOKOSO concernant la gestion de vos données personnelles.">
  <?php include 'includes/head.php'; ?>
</head>
<body>
  <div class="page">
    <?php include 'includes/sidebar.php'; ?>

    <main class="content">
      <?php include 'includes/header.php'; ?>

      <div class="legal-page">
        <h1>Politique de confidentialité</h1>

        <section class="legal-section">
          <h2>1. Responsable du traitement</h2>
          <p>YOKOSO Corp. est responsable du traitement des données personnelles collectées sur la plateforme YOKOSO, conformément au Règlement Général sur la Protection des Données (RGPD — UE 2016/679).</p>
        </section>

        <section class="legal-section">
          <h2>2. Données collectées</h2>
          <p>Dans le cadre de l'utilisation de la plateforme, YOKOSO collecte les données suivantes :</p>
          <ul>
            <li><strong>Données d'identification</strong> : prénom, nom, adresse email</li>
            <li><strong>Données de profil</strong> : photo de profil (optionnelle)</li>
            <li><strong>Données de réservation</strong> : dates, logement, statut de la réservation</li>
            <li><strong>Données de messagerie</strong> : messages échangés entre utilisateurs</li>
            <li><strong>Données de connexion</strong> : date d'inscription, session active</li>
          </ul>
        </section>

        <section class="legal-section">
          <h2>3. Finalités du traitement</h2>
          <p>Les données personnelles sont traitées pour les finalités suivantes :</p>
          <ul>
            <li>Gestion du compte utilisateur et de l'authentification</li>
            <li>Mise en relation entre hôtes et voyageurs</li>
            <li>Traitement et suivi des réservations</li>
            <li>Envoi de notifications relatives à l'activité de la plateforme</li>
            <li>Amélioration des services proposés</li>
          </ul>
        </section>

        <section class="legal-section">
          <h2>4. Base légale</h2>
          <p>Le traitement de vos données repose sur :</p>
          <ul>
            <li>L'exécution du contrat (utilisation des services YOKOSO)</li>
            <li>Votre consentement pour les données optionnelles (photo de profil)</li>
            <li>L'intérêt légitime de YOKOSO pour l'amélioration de la plateforme</li>
          </ul>
        </section>

        <section class="legal-section">
          <h2>5. Conservation des données</h2>
          <p>Vos données sont conservées pendant toute la durée d'activité de votre compte. En cas de suppression de votre compte, vos données personnelles sont effacées dans un délai de 30 jours, à l'exception des données requises par des obligations légales.</p>
        </section>

        <section class="legal-section">
          <h2>6. Vos droits</h2>
          <p>Conformément au RGPD, vous disposez des droits suivants :</p>
          <ul>
            <li><strong>Droit d'accès</strong> : consulter les données vous concernant</li>
            <li><strong>Droit de rectification</strong> : corriger des données inexactes</li>
            <li><strong>Droit à l'effacement</strong> : demander la suppression de vos données</li>
            <li><strong>Droit à la portabilité</strong> : recevoir vos données dans un format lisible</li>
            <li><strong>Droit d'opposition</strong> : vous opposer à certains traitements</li>
          </ul>
          <p>Pour exercer ces droits, contactez-nous à : <strong>contact@yokoso.fr</strong></p>
        </section>

        <section class="legal-section">
          <h2>7. Partage des données</h2>
          <p>YOKOSO ne vend ni ne loue vos données personnelles à des tiers. Certaines données peuvent être partagées avec d'autres utilisateurs dans le cadre normal du service (ex : prénom visible lors d'une réservation).</p>
        </section>

        <section class="legal-section">
          <h2>8. Sécurité</h2>
          <p>YOKOSO met en œuvre des mesures techniques et organisationnelles appropriées pour protéger vos données contre tout accès non autorisé, perte ou divulgation. Les mots de passe sont stockés sous forme hachée (bcrypt).</p>
        </section>

        <section class="legal-section">
          <h2>9. Contact</h2>
          <p>Pour toute question relative à cette politique de confidentialité ou à vos données personnelles, vous pouvez nous contacter à : <strong>contact@yokoso.fr</strong></p>
        </section>

        <p class="legal-update">Dernière mise à jour : février 2025</p>
      </div>

      <?php include 'includes/footer.php'; ?>
    </main>
  </div>
</body>
</html>
