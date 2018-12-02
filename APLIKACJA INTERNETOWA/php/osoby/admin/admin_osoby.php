<?php
  session_start();
  mysqli_report(MYSQLI_REPORT_STRICT);

  if (!isset($_SESSION['zalogowany'])) {
    header('Location: ../wszyscy/index.php');
    exit();
  }

  require_once "../../polacz.php";
  require_once "../../wg_pdo_mysql.php";

  $pdo = new WG_PDO_Mysql($bd_uzytk, $bd_haslo, $bd_nazwa, $host);

  //------------------------------------------------WYŚWIETLNIE OSOB-----------------------------------------------//
  $sql = "SELECT * FROM osoba";

  $rezultat = $pdo->sql_table($sql);

  $_SESSION['ilosc_osob'] = count($rezultat);

  for ($i = 0; $i < $_SESSION['ilosc_osob']; $i++)
    $_SESSION['osoba'.$i] = $rezultat[$i];

  //WYCIĄGANIE DODATKOWYCH INFORMACJI
  for ($i = 0; $i < $_SESSION['ilosc_osob']; $i++) {
    //NAUCZYCIEL
    if ($_SESSION['osoba'.$i]['uprawnienia'] == "n") {
      $id_osoba = $_SESSION['osoba'.$i]['id'];
      $sql = "SELECT nazwa FROM osoba, nauczyciel, sala WHERE osoba.id='$id_osoba' AND nauczyciel.id_osoba=osoba.id AND nauczyciel.id_sala=sala.id";

      $rezultat = $pdo->sql_value($sql);

      $_SESSION['osoba'.$i]['sala_nazwa'] = $rezultat;
    }

    //UCZEN
    if ($_SESSION['osoba'.$i]['uprawnienia'] == "u") {
      $id_osoba = $_SESSION['osoba'.$i]['id'];
      $sql = "SELECT data_urodzenia, nazwa, opis FROM osoba, uczen, klasa WHERE osoba.id='$id_osoba' AND uczen.id_osoba=osoba.id AND klasa.id=uczen.id_klasa";

      $rezultat = $pdo->sql_record($sql);

      $_SESSION['osoba'.$i]['data_urodzenia'] = $rezultat['data_urodzenia'];
      $_SESSION['osoba'.$i]['klasa_nazwa'] = $rezultat['nazwa'];
      $_SESSION['osoba'.$i]['klasa_opis'] = $rezultat['opis'];
    }
  }

///---------------------------------------------______-----------------------------------_________________________________--------------//


  //------------------------------------------------WYCIĄGANIE KLAS-----------------------------------------------//
  $sql = "SELECT * FROM klasa";

  $rezultat = $pdo->sql_table($sql);

  $_SESSION['ilosc_klas'] = count($rezultat);

  for ($i = 0; $i < $_SESSION['ilosc_klas']; $i++)
    $_SESSION['klasa'.$i] = $rezultat[$i];

  //------------------------------------------------WYCIĄGANIE SAL-----------------------------------------------//
  $sql = "SELECT * FROM sala";

  $rezultat = $pdo->sql_table($sql);

  $_SESSION['ilosc_sal'] = count($rezultat);

  for ($i = 0; $i < $_SESSION['ilosc_klas']; $i++)
    $_SESSION['sala'.$i] = $rezultat[$i];

  //------------------------------------------------DODAWANIE OSÓB-----------------------------------------------//
  if (isset($_POST['imie']) || isset($_POST['nazwisko'])) {
    $imie = $_POST['imie'];
    $nazwisko = $_POST['nazwisko'];
    $email = $_POST['email'];
    $haslo = $_POST['haslo'];
    $uprawnienia = $_POST['uprawnienia'];
    //NAUCZYCIEL
    $wyb_sala = $_POST['wyb_sala'];
    //UCZEŃ
    $dataUrodzenia = $_POST['data_urodzenia'];
    $wyb_klasa = $_POST['wyb_klasa'];

    //TESTY
    $wszystko_ok = true;

    if(strlen($imie) <= 0 || strlen($imie) > 20) {
      $_SESSION['dodawanie_osob'] = "Imie osoby nie może być puste oraz musi być krótsze od 20 znaków!";
      $wszystko_ok = false;
    }

    if(strlen($nazwisko) <= 0 || strlen($nazwisko) > 30) {
      $_SESSION['dodawanie_osob'] = "Nazwisko osoby nie może być puste oraz musi być krótsze od 30 znaków!";
      $wszystko_ok = false;
    }

    if(strlen($email) <= 0 || strlen($email) > 255) {
      $_SESSION['dodawanie_osob'] = "Email osoby nie może być pusty oraz musi być krótszy od 255 znaków!";
      $wszystko_ok = false;
    }

    //Sprawdzanie poprawności adresu email
    $emailB = filter_var($email, FILTER_SANITIZE_EMAIL);
    if((filter_var($emailB, FILTER_VALIDATE_EMAIL) == false) || ($emailB != $email)) {
      $wszystko_ok = false;
      $_SESSION['dodawanie_osob'] = "Podaj poprawny nowy adres email!";
    }

    //Sprawdzanie czy nowy email nie jest już w bazie danych
    $sql = "SELECT id FROM osoba WHERE email='$emailB'";

    $rezultat = $pdo->sql_table($sql);

    if (count($rezultat) > 0) {
      $wszystko_ok = false;
      $_SESSION['dodawanie_osob'] = "Taki adres email istnieje już w bazie!";
    }

    if(strlen($haslo) < 8 || strlen($haslo) > 32) {
      $_SESSION['dodawanie_osob'] = "Hasło osoby musi posiadać pomiędzy 8 a 32 znakami!";
      $wszystko_ok = false;
    }

    //Prosty test tak o
    if ($uprawnienia != "a" && $uprawnienia != "n" && $uprawnienia != "u") {
      $_SESSION['dodawanie_osob'] = "Uprawnenia zostały błędnie podane. Wybierz odpowienie uprawnienia!";
      $wszystko_ok = false;
    }

    //TESTY DLA UCZNIA I NAUCZYCIELA DODATKOWO
    if ($uprawnienia == "n") {
      //--CZY wyb_sala JEST W BAZIE
      $wyb_salaBaz = false;
      for ($i = 0; $i < $_SESSION['ilosc_sal']; $i++) {
        if ($wyb_sala == $_SESSION['sala'.$i]['id']) {
          $wyb_salaBaz = true;
          break;
        }
      }

      if ($wyb_salaBaz == false) {
        $_SESSION['dodawanie_osob'] = "Wybrana sala nie istenieje w bazie. Wybierz odpowiednią klasę!";
        $wszystko_ok = false;
      }
    } else if ($uprawnienia == "u") {
      //--CZY wyb_klasa JEST W BAZIE
      $wyb_klasaBaz = false;
      for ($i = 0; $i < $_SESSION['ilosc_klas']; $i++) {
        if ($wyb_klasa == $_SESSION['klasa'.$i]['id']) {
          $wyb_klasaBaz = true;
          break;
        }
      }

      if ($wyb_klasaBaz == false) {
        $_SESSION['dodawanie_osob'] = "Wybrana klasa nie istenieje w bazie. Wybierz odpowiednią salę!";
        $wszystko_ok = false;
      }
    }




    //WKŁADANIE DO BAZY
    if ($wszystko_ok) {
      $haslo_hash = password_hash($haslo, PASSWORD_DEFAULT);

      //DODAWANIE OSOBY
      $sql = "INSERT INTO osoba VALUES (NULL, '$imie', '$nazwisko', '$email', '$haslo_hash', '$uprawnienia')";

      if ($rezultat = $pdo->sql_query($sql) > 0)
        $_SESSION['dodawanie_osob'] = "Nowa osoba została dodana!";
      else
        $_SESSION['dodawanie_osob'] = "Nowa osoba nie została dodana!";


      //WYCIĄGANIE ID NOWEJ OSOBY
      $sql = "SELECT id, haslo FROM osoba WHERE email='$email'";

      $rezultat = $pdo->sql_table($sql);

      if (count($rezultat) == 1 && password_verify($haslo, $rezultat[0]['haslo']))
        $nosoba_id = $rezultat[0]['id']; //sprawdz


      if ($uprawnienia == "a") {
        //DOŁOŻENIE ADMINISTRATORA
        $sql = "INSERT INTO administrator VALUES ('$nosoba_id')";

        if ($rezultat = $pdo->sql_query($sql) > 0)
          $_SESSION['dodawanie_osob'] = "Nowy administrator został dodany!";
        else
          $_SESSION['dodawanie_osob'] = "Nowy administrator nie został dodany!";
      } else if ($uprawnienia == "n") {
        //DOŁOŻENIE NAUCZYCIELA
        $sql = "INSERT INTO nauczyciel VALUES ('$nosoba_id', '$wyb_sala')";

        if ($rezultat = $pdo->sql_query($sql) > 0)
          $_SESSION['dodawanie_osob'] = "Nowy nauczyciel został dodany!";
        else
          $_SESSION['dodawanie_osob'] = "Nowy nauczyciel nie został dodany!";
      } else if ($uprawnienia == "u") {
        //DOŁOŻENIE UCZNIA
        $sql = "INSERT INTO uczen VALUES ('$nosoba_id', '$wyb_klasa', '$dataUrodzenia')";

        if ($rezultat = $pdo->sql_query($sql) > 0)
          $_SESSION['dodawanie_osob'] = "Nowy uczeń został dodany!";
        else
          $_SESSION['dodawanie_osob'] = "Nowy uczeń nie został dodany!";
      }
    }
  }
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">

  <title>BDG DZIENNIK - ZOBACZ, DODAJ, USUŃ, EDYTUJ OSOBY</title>
  <meta name="keywords" content="">
  <meta name="description" content="">
  <meta name="author" content="Szymon Polaczy">

  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans+Condensed:300" rel="stylesheet">
  <link rel="stylesheet" href="../../../css/style.css">

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.js" type="text/javascript"></script>
  <script src="../../../js/script.js" type="text/javascript"></script>
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
    <section>
      <h2>ZOBACZ OSOBY</h2>
      <?php
        //HEHE to się nigdy nie wydarzy
        if ($_SESSION['ilosc_osob'] == 0) {
          echo '<p>Nie ma żadnych osób w bazie</p>';
        }

        //ZADANIA PHP
        if (isset($_SESSION['usuwanie_osob'])) {
          echo '<small id="logowaniePomoc" class="form-text uzytk-blad">'.$_SESSION['usuwanie_osob'].'</small>';
          unset($_SESSION['usuwaniusuwanie_osobe_klas']);
        }

        if (isset($_SESSION['edytowanie_osob'])) {
          echo '<small id="logowaniePomoc" class="form-text uzytk-blad">'.$_SESSION['edytowanie_osob'].'</small>';
          unset($_SESSION['edytowanie_osob']);
        }

        //WYŚWIETLAM ADMINISTATORÓW
        echo '<table class="table">';
        echo '<thead class="thead-dark">';
          echo '<tr>';
            echo '<th class="tabela-liczby">#</th>';
            echo '<th class="tabela-tekst">IMIE</th>';
            echo '<th class="tabela-tekst">NAZWISKO</th>';
            echo '<th class="tabela-tekst">EMAIL</th>';
            echo '<th class="tabela-tekst">HASŁO</th>';
            echo '<th class="tabela-tekst">UPRAWNIENIA</th>';
            echo '<th class="tabela-zadania">EDYTOWANIE</th>';
            echo '<th class="tabela-zadania">USUWANIE</th>';
          echo '</tr>';
        echo '</thead>';

        echo '<tbody>';

        for ($i = 0; $i < $_SESSION['ilosc_osob']; $i++) {
          if ($_SESSION['osoba'.$i]['uprawnienia'] == "a") {
            echo '<tr>';
            echo '<td class="tabela-liczby">'.$i.'</td>';
            echo '<td class="tabela-tekst">'.$_SESSION['osoba'.$i]['imie'].'</td>';
            echo '<td class="tabela-tekst">'.$_SESSION['osoba'.$i]['nazwisko'].'</td>';
            echo '<td class="tabela-tekst">'.$_SESSION['osoba'.$i]['email'].'</td>';
            echo '<td class="tabela-tekst">'.substr($_SESSION['osoba'.$i]['haslo'], 0, 4).'...'.'</td>';
            echo '<td class="tabela-tekst">'.$_SESSION['osoba'.$i]['uprawnienia'].'</td>';
            echo '<td class="tabela-zadania"><a href="edytowanie_osob.php?wyb_osoba='.$_SESSION['osoba'.$i]['id'].'">Edytuj</a></td>';
            echo '<td class="tabela-zadania"><a href="zadania/usuwanie_osob.php?wyb_osoba='.$_SESSION['osoba'.$i]['id'].'">Usuń</a></td>';
            echo '</tr>';
          }
        }

        echo '</tbody>';
        echo '</table>';



        //WYŚWIETLAM NAUCZYCIELi
        echo '<table class="table">';
        echo '<thead class="thead-dark">';
          echo '<tr>';
            echo '<th class="tabela-liczby">#</th>';
            echo '<th class="tabela-tekst">IMIE</th>';
            echo '<th class="tabela-tekst">NAZWISKO</th>';
            echo '<th class="tabela-tekst">EMAIL</th>';
            echo '<th class="tabela-tekst">HASŁO</th>';
            echo '<th class="tabela-tekst">UPRAWNIENIA</th>';
            echo '<th class="tabela-tekst">NAZWA SALI</th>';
            echo '<th class="tabela-zadania">EDYTOWANIE</th>';
            echo '<th class="tabela-zadania">USUWANIE</th>';
          echo '</tr>';
        echo '</thead>';

        echo '<tbody>';

        for ($i = 0; $i < $_SESSION['ilosc_osob']; $i++) {
          if ($_SESSION['osoba'.$i]['uprawnienia'] == "n") {
            echo '<tr>';
            echo '<td class="tabela-liczby">'.$i.'</td>';
            echo '<td class="tabela-tekst">'.$_SESSION['osoba'.$i]['imie'].'</td>';
            echo '<td class="tabela-tekst">'.$_SESSION['osoba'.$i]['nazwisko'].'</td>';
            echo '<td class="tabela-tekst">'.$_SESSION['osoba'.$i]['email'].'</td>';
            echo '<td class="tabela-tekst">'.substr($_SESSION['osoba'.$i]['haslo'], 0, 4).'...'.'</td>';
            echo '<td class="tabela-tekst">'.$_SESSION['osoba'.$i]['uprawnienia'].'</td>';
            echo '<td class="tabela-tekst">'.$_SESSION['osoba'.$i]['sala_nazwa'].'</td>';
            echo '<td class="tabela-zadania"><a href="edytowanie_osob.php?wyb_osoba='.$_SESSION['osoba'.$i]['id'].'">Edytuj</a></td>';
            echo '<td class="tabela-zadania"><a href="zadania/usuwanie_osob.php?wyb_osoba='.$_SESSION['osoba'.$i]['id'].'">Usuń</a></td>';
            echo '</tr>';
          }
        }

        echo '</tbody>';
        echo '</table>';



        //UCZNIOWIE
        echo '<table class="table">';
        echo '<thead class="thead-dark">';
          echo '<tr>';
            echo '<th class="tabela-liczby">#</th>';
            echo '<th class="tabela-tekst">IMIE</th>';
            echo '<th class="tabela-tekst">NAZWISKO</th>';
            echo '<th class="tabela-tekst">EMAIL</th>';
            echo '<th class="tabela-tekst">HASŁO</th>';
            echo '<th class="tabela-tekst">UPRAWNIENIA</th>';
            echo '<th class="tabela-liczby">DATA URODZENIA</th>';
            echo '<th class="tabela-tekst">NAZWA KLASY</th>';
            echo '<th class="tabela-tekst">OPIS KLASY</th>';
            echo '<th class="tabela-zadania">EDYTOWANIE</th>';
            echo '<th class="tabela-zadania">USUWANIE</th>';
          echo '</tr>';
        echo '</thead>';

        echo '<tbody>';

        for ($i = 0; $i < $_SESSION['ilosc_osob']; $i++) {
          if ($_SESSION['osoba'.$i]['uprawnienia'] == "u") {
            echo '<tr>';
            echo '<td class="tabela-liczby">'.$i.'</td>';
            echo '<td class="tabela-tekst">'.$_SESSION['osoba'.$i]['imie'].'</td>';
            echo '<td class="tabela-tekst">'.$_SESSION['osoba'.$i]['nazwisko'].'</td>';
            echo '<td class="tabela-tekst">'.$_SESSION['osoba'.$i]['email'].'</td>';
            echo '<td class="tabela-tekst">'.substr($_SESSION['osoba'.$i]['haslo'], 0, 4).'...'.'</td>';
            echo '<td class="tabela-tekst">'.$_SESSION['osoba'.$i]['uprawnienia'].'</td>';
            echo '<td class="tabela-liczby">'.$_SESSION['osoba'.$i]['data_urodzenia'].'</td>';
            echo '<td class="tabela-tekst">'.$_SESSION['osoba'.$i]['klasa_nazwa'].'</td>';
            echo '<td class="tabela-tekst">'.$_SESSION['osoba'.$i]['klasa_opis'].'</td>';
            echo '<td class="tabela-zadania"><a href="edytowanie_osob.php?wyb_osoba='.$_SESSION['osoba'.$i]['id'].'">Edytuj</a></td>';
            echo '<td class="tabela-zadania"><a href="zadania/usuwanie_osob.php?wyb_osoba='.$_SESSION['osoba'.$i]['id'].'">Usuń</a></td>';
            echo '</tr>';
          }
        }

        echo '</tbody>';
        echo '</table>';
      ?>
    </section>
    <section>
      <form method="post">
        <h2>Dodawanie Osób</h2>
        <input type="text" placeholder="Imię" name="imie"/>
        <input type="text" placeholder="Nazwisko" name="nazwisko"/>
        <input type="email" placeholder="Email" name="email"/>
        <input type="password" placeholder="Hasło" name="haslo"/>
        <select id="dodawanie-osob-select" name="uprawnienia" onchange="pokazUzupelnienie()">
          <option value="a">Administrator</option>
          <option value="n">Nauczyciel</option>
          <option value="u">Uczeń</option>
        </select>

        <!--ADMINISTRATOR: nic nie potrzebuje-->

        <!--NAUCZYCIEL: połączenie z salą-->
        <div class="niewidoczne" id="nauczyciel-uzu">
          <?php
            if ($_SESSION['ilosc_sal'] == 0) {
              echo '<span style="color: red;">Nie ma żadnej sali z którą można połączyć nauczyciela. Dodaj pierw klasy!</span>';
            } else {
              echo '<select name="wyb_sala">';

              for ($i = 0; $i < $_SESSION['ilosc_sal']; $i++)
                echo '<option value="'.$_SESSION['sala'.$i]['id'].'">'.$_SESSION['sala'.$i]['nazwa'].'</option>';

              echo '</select>';
            }
          ?>
        </div>

        <!--UCZEŃ: datę urodzenia plus połączenie z klasą-->
        <div class="niewidoczne" id="uczen-uzu">
          <?php
            if ($_SESSION['ilosc_klas'] == 0) {
              echo '<span style="color: red;">Nie ma żadnej klasy z którą można połączyć nauczyciela. Dodaj pierw klasy!</span>';
            } else {
              echo '<input type="date" name="data_urodzenia"/>';
              echo '<select name="wyb_klasa">';

              for ($i = 0; $i < $_SESSION['ilosc_klas']; $i++)
                echo '<option value="'.$_SESSION['klasa'.$i]['id'].'">'.$_SESSION['klasa'.$i]['nazwa'].'</option>';

              echo '</select>';
            }
          ?>
        </div>

        <button type="submit">Dodaj</button>

        <div class="info">
          <?php
            if (isset($_SESSION['dodawanie_osob'])) {
              echo '<p>'.$_SESSION['dodawanie_osob'].'</p>';
              unset($_SESSION['dodawanie_osob']);
            }
          ?>
        </div>
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