<?php
  session_start();

  if(!isset($_SESSION['zalogowany'])) {
    header('Location: ../wszyscy/index.php');
    exit();
  }

  require_once "../../polacz.php";

  mysqli_report(MYSQLI_REPORT_STRICT);



  //WYCIĄGAM PRZYDZIAŁY
  try {
    $polaczenie = new mysqli($host, $bd_uzytk, $bd_haslo, $bd_nazwa);
    $polaczenie->query("SET NAMES utf8");

    if ($polaczenie->connect_errno == 0) {
      $sql = sprintf("SELECT * FROM przydzial WHERE id_nauczyciel='%s'",
                      mysqli_real_escape_string($polaczenie, $_SESSION['id']));

      if ($rezultat = $polaczenie->query($sql)) {
        $_SESSION['ilosc_przydzialow'] = $rezultat->num_rows;

        for ($i = 0; $i < $_SESSION['ilosc_przydzialow']; $i++)
          $_SESSION['przydzial'.$i] = $rezultat->fetch_assoc();

        $rezultat->free_result();
      } else {
          throw new Exception();
      }
      $polaczenie->close();
    } else {
      throw new Exception(mysqli_connect_errno());
    }
  } catch (Exception $blad) {
    echo '<span style="color: #f33">Błąd serwera! Przepraszam za niedogodności i prosimy o powrót w innym terminie!</span>';
    echo '</br><span style="color: #c00">Informacja developerska: '.$blad.'</span>';
  }


  //WYCIĄGAM OSOBY
  try {
    $polaczenie = new mysqli($host, $bd_uzytk, $bd_haslo, $bd_nazwa);
    $polaczenie->query("SET NAMES utf8");

    if ($polaczenie->connect_errno == 0) {
      $sql = "SELECT * FROM osoba WHERE uprawnienia='n'";

      if ($rezultat = $polaczenie->query($sql)) {
        $ilosc_osob = $rezultat->num_rows;

        for ($i = 0; $i < $ilosc_osob; $i++) {
          $_SESSION['osoba'.$i] = $rezultat->fetch_assoc();
        }

        //Dodaję osobę do wyświetlenia
        for ($i = 0; $i < $_SESSION['ilosc_przydzialow']; $i++) {
          for ($j = 0; $j < $ilosc_osob; $j++) {
            if ($_SESSION['przydzial'.$i]['id_nauczyciel'] == $_SESSION['osoba'.$j]['id']) {
              $_SESSION['przydzial'.$i]['nauczyciel-id'] = $_SESSION['osoba'.$j]['id'];
              $_SESSION['przydzial'.$i]['nauczyciel-imie'] = $_SESSION['osoba'.$j]['imie'];
              $_SESSION['przydzial'.$i]['nauczyciel-nazwisko'] = $_SESSION['osoba'.$j]['nazwisko'];
            }
          }
        }

        $rezultat->free_result();
      } else {
          throw new Exception();
      }
      $polaczenie->close();
    } else {
      throw new Exception(mysqli_connect_errno());
    }
  } catch (Exception $blad) {
    echo '<span style="color: #f33">Błąd serwera! Przepraszam za niedogodności i prosimy o powrót w innym terminie!</span>';
    echo '</br><span style="color: #c00">Informacja developerska: '.$blad.'</span>';
  }

  //WYCIĄGAM NAUCZYCIELI
  try {
    $polaczenie = new mysqli($host, $bd_uzytk, $bd_haslo, $bd_nazwa);
    $polaczenie->query("SET NAMES utf8");

    if ($polaczenie->connect_errno == 0) {
      $sql = "SELECT * FROM nauczyciel";

      if ($rezultat = $polaczenie->query($sql)) {
        $_SESSION['ilosc_nauczycieli'] = $rezultat->num_rows;

        for ($i = 0; $i < $_SESSION['ilosc_nauczycieli']; $i++) {
          $_SESSION['nauczyciel'.$i] = $rezultat->fetch_assoc();
        }

        //Dodaję osobę do wyświetlenia
        for ($i = 0; $i < $_SESSION['ilosc_nauczycieli']; $i++) {
          for ($j = 0; $j < $ilosc_osob; $j++) {
            if ($_SESSION['nauczyciel'.$i]['id_osoba'] == $_SESSION['osoba'.$j]['id']) {
              $_SESSION['nauczyciel'.$i]['imie'] = $_SESSION['osoba'.$j]['imie'];
              $_SESSION['nauczyciel'.$i]['nazwisko'] = $_SESSION['osoba'.$j]['nazwisko'];
            }
          }
        }

        $rezultat->free_result();
      } else {
          throw new Exception();
      }
      $polaczenie->close();
    } else {
      throw new Exception(mysqli_connect_errno());
    }
  } catch (Exception $blad) {
    echo '<span style="color: #f33">Błąd serwera! Przepraszam za niedogodności i prosimy o powrót w innym terminie!</span>';
    echo '</br><span style="color: #c00">Informacja developerska: '.$blad.'</span>';
  }


  //WYCIĄGAM PRZEDMIOTY
  try {
    $polaczenie = new mysqli($host, $bd_uzytk, $bd_haslo, $bd_nazwa);
    $polaczenie->query("SET NAMES utf8");

    if ($polaczenie->connect_errno == 0) {
      $sql = "SELECT * FROM przedmiot";

      if ($rezultat = $polaczenie->query($sql)) {
        $_SESSION['ilosc_przedmiotow'] = $rezultat->num_rows;

        for ($i = 0; $i < $_SESSION['ilosc_nauczycieli']; $i++) {
          $_SESSION['przedmiot'.$i] = $rezultat->fetch_assoc();
        }

        //Dodaję przedmiot do wyświetlenia
        for ($i = 0; $i < $_SESSION['ilosc_przydzialow']; $i++)
          for ($j = 0; $j < $_SESSION['ilosc_przedmiotow']; $j++)
            if ($_SESSION['przydzial'.$i]['id_przedmiot'] == $_SESSION['przedmiot'.$j]['id']) {
              $_SESSION['przydzial'.$i]['przedmiot-id'] = $_SESSION['przedmiot'.$j]['id'];
              $_SESSION['przydzial'.$i]['przedmiot-nazwa'] = $_SESSION['przedmiot'.$j]['nazwa'];
            }


        $rezultat->free_result();
      } else {
          throw new Exception();
      }
      $polaczenie->close();
    } else {
      throw new Exception(mysqli_connect_errno());
    }
  } catch (Exception $blad) {
    echo '<span style="color: #f33">Błąd serwera! Przepraszam za niedogodności i prosimy o powrót w innym terminie!</span>';
    echo '</br><span style="color: #c00">Informacja developerska: '.$blad.'</span>';
  }

  //WYCIĄGAM KLASY
  try {
    $polaczenie = new mysqli($host, $bd_uzytk, $bd_haslo, $bd_nazwa);
    $polaczenie->query("SET NAMES utf8");

    if ($polaczenie->connect_errno == 0) {
      $sql = "SELECT * FROM klasa";

      if ($rezultat = $polaczenie->query($sql)) {
        $_SESSION['ilosc_klas'] = $rezultat->num_rows;

        for ($i = 0; $i < $_SESSION['ilosc_klas']; $i++) {
          $_SESSION['klasa'.$i] = $rezultat->fetch_assoc();
        }

        //Dodaję osobę do wyświetlenia
        for ($i = 0; $i < $_SESSION['ilosc_przydzialow']; $i++)
          for ($j = 0; $j < $_SESSION['ilosc_klas']; $j++)
            if ($_SESSION['przydzial'.$i]['id_klasa'] == $_SESSION['klasa'.$j]['id']) {
              $_SESSION['przydzial'.$i]['klasa-id'] = $_SESSION['klasa'.$j]['id'];
              $_SESSION['przydzial'.$i]['klasa-nazwa'] = $_SESSION['klasa'.$j]['nazwa'];
            }


        $rezultat->free_result();
      } else {
          throw new Exception();
      }
      $polaczenie->close();
    } else {
      throw new Exception(mysqli_connect_errno());
    }
  } catch (Exception $blad) {
    echo '<span style="color: #f33">Błąd serwera! Przepraszam za niedogodności i prosimy o powrót w innym terminie!</span>';
    echo '</br><span style="color: #c00">Informacja developerska: '.$blad.'</span>';
  }

?>

<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">

  <title>BDG DZIENNIK - Wybierz przydział</title>
  <meta name="keywords" content="">
  <meta name="description" content="">
  <meta name="author" content="Szymon Polaczy">

  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans+Condensed:300" rel="stylesheet">
  <link rel="stylesheet" href="../../../css/style.css">
</head>
<body>
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
              echo '<li class="nav-item"><a href="../admin/admin_klasy.php" class="nav-link">KLASY</a></li>';
              echo '<li class="nav-item"><a href="../admin/admin_sale.php" class="nav-link">SALE</a></li>';
              echo '<li class="nav-item"><a href="../admin/admin_przedmioty.php" class="nav-link">PRZEDMIOTY</a></li>';
              echo '<li class="nav-item"><a href="../admin/admin_osoby.php" class="nav-link">OSOBY</a></li>';
              echo '<li class="nav-item"><a href="../admin/admin_przydzialy.php" class="nav-link">PRZYDZIAŁY</a></li>';
            } else if ( $_SESSION['uprawnienia'] == "n") {
              echo '<li class="nav-item"><a href="wybierz_przydzial.php" class="nav-link">OCENY</a></li>';
              echo '<li class="nav-item"><a href="nauczyciel_przydzialy.php" class="nav-link">PRZYDZIAŁY</a></li>';
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
    <h2>WYBIERZ PRZYDZIAŁ</h2>
    <section>
      <form action="nauczyciel_oceny.php" method="post">
        <?php
          if ($_SESSION['ilosc_przydzialow'] == 0) {
            echo 'Nie ma żadnych przydziałów, musisz jakieś utworzyć, aby dodać do nich oceny';
          } else {
            echo '<select name="wyb_przydzial">';

            for ($i = 0; $i < $_SESSION['ilosc_przydzialow']; $i++)
              echo '<option value="'.$_SESSION['przydzial'.$i]['id'].'">'.$_SESSION['przydzial'.$i]['nauczyciel-imie']
              .' '.$_SESSION['przydzial'.$i]['nauczyciel-nazwisko']
              .' | '.$_SESSION['przydzial'.$i]['przedmiot-nazwa']
              .' | '.$_SESSION['przydzial'.$i]['klasa-nazwa'].'</option>';

            echo '</select>';

            echo '<button type="submit">Wybierz</button>';
          }
        ?>
      </form>
    </section>

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
