<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>YOKOSO - Page introuvable</title>
  <meta name="description" content="La page que vous recherchez est introuvable. Retournez à l'accueil YOKOSO.">
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  <style>
    .page-404 {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 40px 20px;
      background: #fafafa;
      font-family: 'Inter', sans-serif;
    }
    .error-code {
      font-size: 120px;
      font-weight: 700;
      color: #111;
      line-height: 1;
      margin-bottom: 8px;
    }
    .error-title {
      font-size: 26px;
      font-weight: 600;
      color: #333;
      margin-bottom: 12px;
    }
    .error-desc {
      font-size: 16px;
      color: #666;
      max-width: 420px;
      line-height: 1.6;
      margin-bottom: 36px;
    }
    .error-actions {
      display: flex;
      gap: 14px;
      flex-wrap: wrap;
      justify-content: center;
    }
    .btn-home {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 28px;
      background: #000;
      color: #fff;
      text-decoration: none;
      border-radius: 30px;
      font-size: 15px;
      font-weight: 600;
      transition: background 0.2s;
    }
    .btn-home:hover { background: #333; }
    .btn-browse {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 28px;
      background: #fff;
      color: #111;
      text-decoration: none;
      border-radius: 30px;
      font-size: 15px;
      font-weight: 600;
      border: 2px solid #e5e5e5;
      transition: border-color 0.2s;
    }
    .btn-browse:hover { border-color: #999; }
    .error-logo {
      margin-bottom: 32px;
      opacity: 0.7;
    }
    .error-logo img { width: 56px; height: auto; }
    @media (max-width: 480px) {
      .error-code { font-size: 80px; }
      .error-title { font-size: 20px; }
    }
  </style>
</head>
<body>
  <div class="page-404">
    <div class="error-logo">
      <img src="images/logo-blanc-seul.png" alt="YOKOSO" style="filter: invert(1);">
    </div>
    <div class="error-code">404</div>
    <h1 class="error-title">Page introuvable</h1>
    <p class="error-desc">La page que vous recherchez n'existe pas ou a été déplacée. Revenez à l'accueil ou parcourez nos logements.</p>
    <div class="error-actions">
      <a href="home.php" class="btn-home">
        <i class="fa-solid fa-house"></i> Accueil
      </a>
      <a href="logement.php" class="btn-browse">
        <i class="fa-solid fa-magnifying-glass"></i> Nos logements
      </a>
    </div>
  </div>
</body>
</html>
