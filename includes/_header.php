<nav class="navbar navbar-expand-lg bg-body-tertiary" data-bs-theme="dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">iTodo</a>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav">
        
        <?php
          if(isset($_SESSION["user_id"])) { ?>
            <li class="nav-item">
              <a class="nav-link" href="logout.php">Logout</a>
            </li>
        <?php }?>

        <?php
          if(!isset($_SESSION["user_id"])) { ?>
            <li class="nav-item">
              <a class="nav-link active" aria-current="page" href="index.php">Login</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="register.php">Register</a>
            </li>
        <?php }?>
        
      </ul>
    </div>
  </div>
</nav>

