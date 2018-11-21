<?php
  session_start();
  require_once "../../polacz.php";
  mysqli_report(MYSQLI_REPORT_STRICT);


  if(!isset($_POST['wyb_osoba']) && !isset($_POST['imie'])) {
    header('Location: adminosoby.php');
    exit();
  } else if (isset($_POST['wyb_osoba'])) {
    $id_osoba = $_POST['wyb_osoba'];

    //Wyciągam wszystkie wartości użytkownika
    try {
      $polaczenie = new mysqli($host, $bd_uzytk, $bd_haslo, $bd_nazwa);
      $polaczenie->query("SET NAMES utf8");

      if ($polaczenie->connect_errno == 0) {
        $sql= sprintf("SELECT * FROM osoba WHERE id='%s'",
                      mysqli_real_escape_string($polaczenie, $id_osoba));

        if ($rezultat = $polaczenie->query($sql)) {
          $wiersz = $rezultat->fetch_assoc();

          $_SESSION['edytowana_id'] = $wiersz['id'];
          $_SESSION['edytowana_imie'] = $wiersz['imie'];
          $_SESSION['edytowana_nazwisko'] = $wiersz['nazwisko'];
          $_SESSION['edytowana_email'] = $wiersz['email'];
          $_SESSION['edytowana_haslo'] = $wiersz['haslo'];
          $_SESSION['edytowana_uprawnienia'] = $wiersz['uprawnienia'];

          $rezultat->free_result();
        } else
          throw new Exception();

        $polaczenie->close();
      } else {
        throw new Exception(mysqli_connect_errno());
      }
    } catch (Exception $blad) {
      echo '<span style="color: #f33">Błąd serwera! Przepraszam za niedogodności i prosimy o powrót w innym terminie!</span>';
      echo '</br><span style="color: #c00">Informacja developerska: '.$blad.'</span>';
    }
  }

  if (isset($_POST['imie'])) {
    $imie = $_POST['imie'];
    $nazwisko = $_POST['nazwisko'];
    $email = $_POST['email'];
    $haslo = $_POST['haslo'];

    $wszystko_ok = true;

    //TESTY IMIENIA - jeśli zmiana
    if ($imie != $_SESSION['edytowana_imie']) {
      if(strlen($imie) <= 0 || strlen($imie) > 20) {
        $_SESSION['edytowanie_osob'] = "Imie osoby nie może być puste oraz musi być krótsze od 20 znaków!";
        $wszystko_ok = false;
      }
    }

    //TESTY NAZWISKA - jeśli zmiana
    if ($nazwisko != $_SESSION['edytowana_nazwisko']) {
      if(strlen($nazwisko) <= 0 || strlen($nazwisko) > 30) {
        $_SESSION['edytowanie_osob'] = "Nazwisko osoby nie może być puste oraz musi być krótsze od 30 znaków!";
        $wszystko_ok = false;
      }
    }

    //TESTY EMAIL - jeśli zmiana
    if ($email != $_SESSION['edytowana_email']) {
      if(strlen($email) <= 0 || strlen($email) > 255) {
        $_SESSION['edytowanie_osob'] = "Email osoby nie może być pusty oraz musi być krótszy od 255 znaków!";
        $wszystko_ok = false;
      }

      //Sprawdzanie poprawności adresu email
      $emailB = filter_var($email, FILTER_SANITIZE_EMAIL);
      if((filter_var($emailB, FILTER_VALIDATE_EMAIL) == false) || ($emailB != $email)) {
        $wszystko_ok = false;
        $_SESSION['edytowanie_osob'] = "Podaj poprawny nowy adres email!";
      }

      //Sprawdzanie czy nowy email nie jest już w bazie danych
      try {
        $polaczenie = new mysqli($host, $bd_uzytk, $bd_haslo, $bd_nazwa);
        $polaczenie->query("SET NAMES utf8");

        $sql = sprintf("SELECT email FROM osoba WHERE email='%s'",
                        mysqli_real_escape_string($polaczenie, $emailB));

        if ($polaczenie->connect_errno == 0) {
          if ($rezultat = $polaczenie->query($sql))
          {
            $ile_emaili = $rezultat->num_rows;
            if ($ile_emaili > 0) {
              $wszystko_ok = false;
              $_SESSION['edytowanie_osob'] = "Taki adres email istnieje już w bazie!";
            }
            $rezultat->free_result();
          }
          $polaczenie->close();
        } else {
            throw new Exception(mysqli_connect_errno());
        }
      } catch (Exception $blad) {
        echo '<span style="color: #f33">Błąd serwera! Przepraszam za niedogodności i prosimy o próbę w innym terminie!</span>';
        echo '</br><span style="color: #c00">Informacja developerska: '.$blad.'</span>';
      }
    }

    //TESTY HASLO - jeśli zmiana
    if ($haslo != $_SESSION['edytowana_haslo']) {
      if(strlen($haslo) < 8 || strlen($haslo) > 32) {
        $_SESSION['edytowanie_osob'] = "Hasło osoby musi posiadać pomiędzy 8 a 32 znakami!";
        $wszystko_ok = false;
      } else {
        //Z hasłem wszystko ok, wstawiam hash do hasła
        $hash = password_hash($haslo, PASSWORD_DEFAULT);
        $haslo = $hash;
      }
    }



    //FAKTYCZNE EDYTOWANIE OSOBY
    if ($wszystko_ok) {
      try {
        $polaczenie = new mysqli($host, $bd_uzytk, $bd_haslo, $bd_nazwa);
        $polaczenie->query("SET NAMES utf8");

        if ($polaczenie->connect_errno == 0) {
          $sql = sprintf("UPDATE osoba SET imie='%s', nazwisko='%s',
                          email='%s', haslo='%s', WHERE ='%s'"
                          mysqli_real_escape_string($polaczenie, $imie),
                          mysqli_real_escape_string($polaczenie, $nazwisko),
                          mysqli_real_escape_string($polaczenie, $email),
                          mysqli_real_escape_string($polaczenie, $haslo),
                          mysqli_real_escape_string($polaczenie, $_SESSION['edytowana_id']));

          if ($polaczenie->query($sql)) {
            $_SESSION['edytowanie_osob'] = "Osoba została zedytowana";
          } else
            throw new Exception();

          $polaczenie->close();
        } else {
          throw new Exception(mysqli_connect_errno());
        }
      } catch (Exception $blad) {
        echo '<span style="color: #f33">Błąd serwera! Przepraszam za niedogodności i prosimy o powrót w innym terminie!</span>';
        echo '</br><span style="color: #c00">Informacja developerska: '.$blad.'</span>';
      }
    }
  }

?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">

  <title>BDG DZIENNIK - Edytuj Osobę</title>
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
                <a class="dropdown-item" href="../wszyscy/zmien_dane.php">ZMIEŃ DANE</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="../wszyscy/zadania/wyloguj.php">WYLOGÓJ</a>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </nav>
  </header>

  <main>
    <form method="post">
      <h3>Edytuj Osoby</h3>
      <?php
        echo '<input type="text" value="'.$_SESSION['edytowana_imie'].'" name="imie"/>';
        echo '<input type="text" value="'.$_SESSION['edytowana_nazwisko'].'" name="nazwisko"/>';
        echo '<input type="email" value="'.$_SESSION['edytowana_email'].'" name="email"/>';
        echo '<input type="password" value="'.$_SESSION['edytowana_haslo'].'" name="haslo"/>';
      ?>
      <button type="submit">Edytuj</button>
      <div class="info">
        <?php
          if (isset($_SESSION['edytowanie_osob'])) {
            echo '<p>'.$_SESSION['edytowanie_osob'].'</p>';
            unset($_SESSION['edytowanie_osob']);
          }
        ?>
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
