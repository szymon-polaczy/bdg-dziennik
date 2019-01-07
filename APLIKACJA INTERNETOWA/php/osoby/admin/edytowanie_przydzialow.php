<?php
  session_start();
  mysqli_report(MYSQLI_REPORT_STRICT);

  if(!isset($_POST['wyb_przydzial']) && !isset($_POST['wyb_nauczyciel'])) {
    header('Location: admin_przydzialy.php');
    exit();
  }

  require_once "../../polacz.php";
  require_once "../../wg_pdo_mysql.php";

  $pdo = new WG_PDO_Mysql($bd_uzytk, $bd_haslo, $bd_nazwa, $host);

  //Wyciągam osoby
  $sql = "SELECT * FROM osoba, nauczyciel WHERE uprawnienia='n' AND osoba.id=nauczyciel.id_osoba";

  $rezultat = $pdo->sql_table($sql);

  $ilosc_osob = count($rezultat);

  for ($i = 0; $i < $ilosc_osob; $i++)
    $_SESSION['osoba'.$i] = $rezultat[$i];

  //Wyciągam przedmioty
  $sql = "SELECT * FROM przedmiot";

  $rezultat = $pdo->sql_table($sql);

  $ilosc_przedmiotow = count($rezultat);

  for ($i = 0; $i < $ilosc_przedmiotow; $i++)
    $_SESSION['przedmiot'.$i] = $rezultat[$i];

  //Wyciągam klasy
  $sql = "SELECT * FROM klasa";

  $rezultat = $pdo->sql_table($sql);

  $ilosc_klas = count($rezultat);

  for ($i = 0; $i < $ilosc_klas; $i++)
    $_SESSION['klasa'.$i] = $rezultat[$i];

  //Wyciąganie edytowanego przydziału
  if (isset($_POST['wyb_przydzial'])) {
    $wyb_przydzial = $_POST['wyb_przydzial'];
    $sql = "SELECT * FROM przydzial WHERE id='$wyb_przydzial'";

    $rezultat = $pdo->sql_table($sql);

    $_SESSION['edytowany_id'] = $rezultat[0]['id'];
    $_SESSION['edytowany_id_nauczyciel'] = $rezultat[0]['id_nauczyciel'];
    $_SESSION['edytowany_id_przedmiot'] = $rezultat[0]['id_przedmiot'];
    $_SESSION['edytowany_id_klasa'] = $rezultat[0]['id_klasa'];
  }
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">

  <title>BDG DZIENNIK - Edytuj Przydział</title>
  <meta name="keywords" content="">
  <meta name="description" content="">
  <meta name="author" content="Szymon Polaczy">

  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans+Condensed:300" rel="stylesheet">
  <link rel="stylesheet" href="../../../css/style.css">
</head>
<body class="index-body">
  <header>
    <nav class="navbar navbar-expand-md navbar-dark bg-dark">
      <a href="../wszyscy/dziennik.php" class="navbar-brand">BDG DZIENNIK</a>
      <button class="navbar-toggler" data-toggle="collapse" data-target="#glowneMenu">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div id="glowneMenu" class="collapse navbar-collapse">
        <ul class="navbar-nav  ml-auto">
          <?php
            if ( $_SESSION['uprawnienia'] == "a") {
              echo '<li class="nav-item"><a href="admin_klasy.php" class="nav-link">KLASY</a></li>';
              echo '<li class="nav-item"><a href="admin_sale.php" class="nav-link">SALE</a></li>';
              echo '<li class="nav-item"><a href="admin_przedmioty.php" class="nav-link">PRZEDMIOTY</a></li>';
              echo '<li class="nav-item"><a href="admin_osoby.php" class="nav-link">OSOBY</a></li>';
              echo '<li class="nav-item"><a href="admin_przydzialy.php" class="nav-link">PRZYDZIAŁY</a></li>';
            } else if ( $_SESSION['uprawnienia'] == "n") {
              echo '<li class="nav-item"><a href="../nauczyciel/wybierz_przydzial.php" class="nav-link">OCENY</a></li>';
              echo '<li class="nav-item"><a href="../nauczyciel/nauczyciel_przydzialy.php" class="nav-link">PRZYDZIAŁY</a></li>';
            } else if ( $_SESSION['uprawnienia'] == "u") {
              echo '<li class="nav-item"><a href="../uczen/uczen_oceny.php" class="nav-link">OCENY</a></li>';
              echo '<li class="nav-item"><a href="../uczen/uczen_przydzialy.php" class="nav-link">PRZYDZIAŁY</a></li>';
            }
          ?>
          <li class="nav-item">
            <div class="dropdown">
              <a href="#" class="nav-item btn btn-dark dropdown-toggle" role="button" id="dropdownProfil"  data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                PROFIL
              </a>

              <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink">
                <a class="dropdown-item disabled" href="#">Imie: <span class="wartosc"><?php echo $_SESSION['imie']; ?></span></a>
                <a class="dropdown-item disabled" href="#">Nazwisko: <span class="wartosc"><?php echo $_SESSION['nazwisko']; ?></span></a>
                <a class="dropdown-item disabled" href="#">Email: <span class="wartosc"><?php echo $_SESSION['email']; ?></span></a>
                <?php
                  if ($_SESSION['uprawnienia'] == "n")
                    echo '<a class="dropdown-item disabled" href="#">Sala: <span class="wartosc">'.$_SESSION['sala_nazwa'].'</span></a>';
                  else if ($_SESSION['uprawnienia'] == "u")
                    echo '<a class="dropdown-item disabled" href="#">Klasa: <span class="wartosc">'.$_SESSION['klasa_nazwa'].'</span></a>';
                ?>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="zmien_dane.php">ZMIEŃ DANE</a>
                <a class="dropdown-item" href="../wszyscy/zadania/wyloguj.php">WYLOGUJ</a>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </nav>
  </header>

  <main>
    <form method="post" action="zadania/edytowanie_przydzialow.php">
      <h2>Edytuj Przydział</h2>
      <div class="form-group">
        <label for="wybor_nauczyciela">Wybierz nauczyciela</label>
        <select name="wyb_nauczyciel" class="form-control" id="wybor_nauczyciela">
          <?php
            for ($i = 0; $i < $ilosc_osob; $i++)
              echo '<option '.($_SESSION['osoba'.$i]['id'] == $_SESSION['edytowany_id_nauczyciel']? 'selected' : '').' value="'.$_SESSION['osoba'.$i]['id_osoba'].'">Nauczyciel '.$_SESSION['osoba'.$i]['imie'].' '.$_SESSION['osoba'.$i]['nazwisko'].'</option>';
           ?>
        </select>
      </div>
      <div class="form-group">
        <label for="wybor_przedmiotu">Wybierz przedmiot</label>
        <select name="wyb_przedmiot" class="form-control" id="wybor_przedmiotu">
          <?php
            for ($i = 0; $i < $ilosc_przedmiotow; $i++)
              echo '<option '.($_SESSION['przedmiot'.$i]['id'] == $_SESSION['edytowany_id_przedmiot']? 'selected' : '').' value="'.$_SESSION['przedmiot'.$i]['id'].'">Przedmiot '.$_SESSION['przedmiot'.$i]['nazwa'].'</option>';
           ?>
        </select>
      </div>
      <div class="form-group">
        <label for="wybor_klasy">Wybierz klasę</label>
        <select name="wyb_klasa" class="form-control" id="wybor_klasy">
          <?php
            for ($i = 0; $i < $ilosc_klas; $i++)
              echo '<option '.($_SESSION['klasa'.$i]['id'] == $_SESSION['edytowany_id_klasa']? 'selected' : '').' value="'.$_SESSION['klasa'.$i]['id'].'">Klasa '.$_SESSION['klasa'.$i]['nazwa'].' | '.$_SESSION['klasa'.$i]['opis'].'</option>';
           ?>
        </select>
      </div>
      <div class="form-group form-inf">
      <button type="submit" class="btn btn-dark">Edytuj</button>
      </div>
    </form>

    <a href="../wszyscy/dziennik.php"><button class="btn btn-dark">Powrót do strony głównej</button></a>
  </main>

  <footer class="fixed-bottom bg-dark glowna-stopka">
    <h6>Autor: Szymon Polaczy</h6>
  </footer>

  <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
</body>
</html>
