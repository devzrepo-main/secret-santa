<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>🎅 Secret Santa Form 🎄</title>

  <!-- Bootstrap -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

  <!-- Google Fonts -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap">

  <!-- Custom Christmas CSS -->
  <link rel="stylesheet" href="assets/css/christmas.css?v=6">

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <!-- Main App -->
  <script src="assets/app.js?v=12"></script>
</head>

<body>
  <!-- ✨ Twinkling Lights Bar -->
  <div class="lights">
    <?php for ($i = 0; $i < 25; $i++): ?>
      <div class="bulb"></div>
    <?php endfor; ?>
  </div>

  <!-- 🎄 Snowflakes -->
  <?php for ($i = 0; $i < 40; $i++): ?>
    <div class="snowflake" style="
      left:<?= rand(0, 100) ?>%;
      animation-duration: <?= rand(10, 25) ?>s;
      animation-delay: <?= rand(0, 10) ?>s;
    ">❆</div>
  <?php endfor; ?>

  <div class="container py-5 text-center">
    <h2 class="mb-4 text-success" style="text-shadow: 2px 2px 5px black;">
      🎅 Welcome to the Secret Santa Christmas Edition 🎄
    </h2>

    <!-- Member Claim Section -->
    <div id="memberSection" class="mb-4"></div>

    <!-- Reveal Section -->
    <div id="revealSection" class="mb-4"></div>

    <!-- Admin Panel Section -->
    <div id="adminWrapper" class="mb-4"></div>

    <footer class="mt-5">
      <p>Made with ❤️ and Christmas cheer ✨</p>
      <iframe style="border-radius:12px"
        src="https://open.spotify.com/embed/playlist/37i9dQZF1DX0Yxoavh5qJV?utm_source=generator"
        width="100%" height="80" frameBorder="0" allowfullscreen=""
        allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe>
    </footer>
  </div>
</body>
</html>

