<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>🎅 Secret Santa Christmas Edition 🎄</title>

  <!-- Bootstrap -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

  <!-- Google Fonts -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap">

  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/christmas.css?v=7">

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <!-- App Logic -->
  <script src="assets/app.js?v=11"></script>
</head>

<body>
  <!-- ✨ Twinkling Lights -->
  <div class="lights">
    <?php for ($i = 0; $i < 25; $i++): ?>
      <div class="bulb"></div>
    <?php endfor; ?>
  </div>

  <!-- ❄️ Falling Snowflakes -->
  <?php for ($i = 0; $i < 40; $i++): ?>
    <div class="snowflake" style="
      left:<?= rand(0, 100) ?>%;
      animation-duration: <?= rand(10, 25) ?>s;
      animation-delay: <?= rand(0, 10) ?>s;
    ">❆</div>
  <?php endfor; ?>

  <!-- 🎁 Main Content -->
  <div class="container py-5 text-center">
    <h2 class="mb-4 glow-green">Welcome to the Secret Santa Christmas Edition!</h2>
    <div id="memberSection" class="mb-5"></div>
    <div id="adminSection"></div>
  </div>

  <footer class="text-center py-4">
    <p>Made with ❤️ & Christmas cheer 🎁</p>
  </footer>
</body>
</html>
