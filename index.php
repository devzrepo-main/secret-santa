<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title> Secret Santa Christmas Edition </title>

  <!-- Bootstrap -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

  <!-- Google Fonts -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap">

  <!-- Custom Christmas CSS -->
  <link rel="stylesheet" href="assets/css/christmas.css?v=5">

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <!-- Main App -->
  <script src="assets/app.js?v=10"></script>
</head>

<body>
  <!-- ✨ Twinkling Lights -->
  <div class="lights">
    <?php for ($i = 0; $i < 25; $i++): ?>
      <div class="bulb"></div>
    <?php endfor; ?>
  </div>

  <!-- ❄️ Falling Snow -->
  <?php for ($i = 0; $i < 40; $i++): ?>
    <div class="snowflake" style="
      left:<?= rand(0, 100) ?>%;
      animation-duration: <?= rand(10, 25) ?>s;
      animation-delay: <?= rand(0, 10) ?>s;
    ">❆</div>
  <?php endfor; ?>

  <div class="container text-center py-5">
    <h1 class="display-4 fw-bold text-christmas mt-4"> A Warm Christmas Welcome!</h1>
    <p class="lead text-light">Spread joy, share gifts, and enjoy the holiday magic!</p>

    <div id="memberSection" class="mt-5"></div>
    <div id="adminSection" class="mt-5"></div>
  </div>

  <!--  Festive Footer with Spotify Playlist -->
  <footer class="text-center mt-5 py-5 christmas-footer">
    <p class="footer-text"> Made with Christmas Cheer </p>

    <div class="spotify-frame">
      <iframe style="border-radius:12px" 
        src="https://open.spotify.com/embed/playlist/37i9dQZF1DX0Yxoavh5qJV?utm_source=generator"
        width="80%" height="152" frameBorder="0" 
        allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture">
      </iframe>
    </div>
  </footer>
</body>
</html>


