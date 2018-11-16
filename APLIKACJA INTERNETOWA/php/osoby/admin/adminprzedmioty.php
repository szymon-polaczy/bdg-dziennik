<?php
  session_start();

  if(!isset($_SESSION['zalogowany'])) {
    header('Location: ../wszyscy/index.php');
    exit();
  }

  require_once "../../polacz.php";

  mysqli_report(MYSQLI_REPORT_STRICT);

  //---------------------------------------------------USUWANIE przedmiotu--------------------------------------------------------//
  if (isset($_POST['wyb_przedmiot']) && !isset($_POST['nazwa'])) {
    $wyb_przedmiot = $_POST['wyb_przedmiot'];
    $wszystko_ok = true;

    //Sprawdzam czy przedmiot nie jest gdzieś w jakimś przydziale
    try {
      $polaczenie = new mysqli($host, $bd_uzytk, $bd_haslo, $bd_nazwa);
      $polaczenie->query("SET NAMES utf8");

      if($polaczenie->connect_errno == 0) {
        $sql = sprintf("SELECT * FROM przydzial WHERE id_przedmiot='%s'",
                        mysqli_real_escape_string($polaczenie, $wyb_przedmiot));

        if($rezultat = $polaczenie->query($sql)) {
          if ($rezultat->num_rows > 0) {
            $_SESSION['usuwanie_przedmiotow'] = "Przedmiot jest przypisany do przydziałów, nie można go usunąć!";
            $wszystko_ok = false;
          }
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

    if ($wszystko_ok) {
      try {
        $polaczenie = new mysqli($host, $bd_uzytk, $bd_haslo, $bd_nazwa);
        $polaczenie->query("SET NAMES utf8");

        if($polaczenie->connect_errno == 0) {
          $sql = sprintf("DELETE FROM przedmiot WHERE id='%s'",
                          mysqli_real_escape_string($polaczenie, $wyb_przedmiot));

          if($polaczenie->query($sql)) {
            $_SESSION['usuwanie_przedmiotow'] = "Przedmiot został usunięty!";
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



  //------------------------------------------------WYCIĄGANIE PRZEDMIOTÓW DO OBEJRZENIA-----------------------------------------------//

  try {
    $polaczenie = new mysqli($host, $bd_uzytk, $bd_haslo, $bd_nazwa);
    $polaczenie->query("SET NAMES utf8");

    if ($polaczenie->connect_errno == 0) {
      $sql = "SELECT * FROM przedmiot";

      if ($rezultat = $polaczenie->query($sql)) {
        $_SESSION['ilosc_przedmiotow'] = $rezultat->num_rows;

        for ($i = 0; $i < $_SESSION['ilosc_przedmiotow']; $i++)
          $_SESSION['przedmiot'.$i] = $rezultat->fetch_assoc();

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

  //-----------------------------------------------------DODAWANIE KLAS------------------------------------------------------//
  if(isset($_POST['nazwa']) && !isset($_POST['wyb_przedmiot'])) {
    $nazwa = $_POST['nazwa'];
    $wszystko_ok = true;

    //Sprawdzanie długości nazwy
    if (strlen($nazwa) < 2 || strlen($nazwa) > 50) {
      $wszystko_ok = false;
      $_SESSION['dodawanie_przedmiotow'] = "Nazwa przedmiotu musi mieć pomiędzy 2 a 50 znaków!";
    }

    //Sprawdzanie czy istnieje taka nazwa w bazie
    for ($i = 0; $i < $_SESSION['ilosc_przedmiotow']; $i++) {
      if ($nazwa == $_SESSION['przedmiot'.$i]['nazwa']) {
        $wszystko_ok = false;
        $_SESSION['dodawanie_przedmiotow'] = "Przedmiot o takiej nazwie już istnieje!";
        break;
      }
    }

    //Po pozytywnym przejściu testów dodaję przedmiot
    if($wszystko_ok) {
      try {
        $polaczenie = new mysqli($host, $bd_uzytk, $bd_haslo, $bd_nazwa);
        $polaczenie->query("SET NAMES utf8");

        if($polaczenie->connect_errno == 0) {
          $sql = sprintf("INSERT INTO przedmiot VALUES(NULL, '%s')",
                          mysqli_real_escape_string($polaczenie, $nazwa));

          if($polaczenie->query($sql))
            $_SESSION['dodawanie_przedmiotow'] = "Nowy przedmiot został dodany!";
          else
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

  //-----------------------------------------------------DODAWANIE KLAS------------------------------------------------------//
  if (isset($_POST['nazwa']) && isset($_POST['wyb_przedmiot'])) {
    $wyb_przedmiot = $_POST['wyb_przedmiot'];
    $nazwa = $_POST['nazwa'];
    $wszystko_ok = true;

    if(strlen($nazwa) < 2 || strlen($nazwa) > 50) {
      $wszystko_ok = false;
      $_SESSION['edytowanie_przedmiotow'] = "Nazwa musi mieć pomiędzy 2 a 50 znaków!";
    }

    for ($i = 0; $i < $_SESSION['ilosc_przedmiotow']; $i++) {
      if ($nazwa == $_SESSION['przedmiot'.$i]['nazwa']) {
        $wszystko_ok = false;
        $_SESSION['edytowanie_przedmiotow'] = "Przedmiot o takiej nazwie już istnieje!";
        break;
      }
    }

    if ($wszystko_ok) {
      try {
        $polaczenie = new mysqli($host, $bd_uzytk, $bd_haslo, $bd_nazwa);
        $polaczenie->query("SET NAMES utf8");

        if($polaczenie->connect_errno == 0) {
          $sql = sprintf("UPDATE przedmiot SET nazwa='%s' WHERE nazwa='%s'",
                          mysqli_real_escape_string($polaczenie, $nazwa),
                          mysqli_real_escape_string($polaczenie, $wyb_przedmiot));

          if($polaczenie->query($sql))
            $_SESSION['edytowanie_przedmiotow'] = "Nazwa przedmiotu została zedytowana";
          else
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

  <title>BDG DZIENNIK - Zobacz, Dodaj, Usuń, Edytuj PRZEDMIOT</title>
  <meta name="keywords" content="">
  <meta name="description" content="">
  <meta name="author" content="Redzik">

  <link href="https://fonts.googleapis.com/css?family=Open+Sans+Condensed:300" rel="stylesheet">
  <link rel="stylesheet" href="../../../css/style.css">
</head>
<body>
  <header>
    <h1>ZOBACZ, DODAJ, USUŃ, EDYTUJ przedmiotu</h1>
  </header>

  <main>
    <section>
      <h2>ZOBACZ PRZEDMIOT</h2>
      <div class="wiersz-przedmiot">
        <div class="kolumna-numer">NUMER</div>
        <div class="kolumna-id">ID</div>
        <div class="kolumna-nazwa">NAZWA</div>
      </div>
      <?php
        for ($i = 0; $i < $_SESSION['ilosc_przedmiotow']; $i++) {
          echo '<div class="wiersz-przedmiot">';
            echo '<div class="kolumna kolumna-numer">'.$i.'</div>';
            echo '<div class="kolumna kolumna-id">'.$_SESSION['przedmiot'.$i]['id'].'</div>';
            echo '<div class="kolumna kolumna-nazwa">'.$_SESSION['przedmiot'.$i]['nazwa'].'</div>';
          echo '</div>';
        }
        if ($_SESSION['ilosc_przedmiotow'] == 0) {
          echo '<div class="wiersz-przedmiot">ŻADEN PRZEDMIOT NIE ISTNIEJE W BAZIE</div>';
        }
      ?>
    </section>
    <section>
      <form method="post">
        <h3>DODAJ PRZEDMIOT</h3>
        <input type="text" placeholder="Nazwa" name="nazwa"/>
        <button type="submit">Dodaj</button>
        <div class="info">
          <?php
            if (isset($_SESSION['dodawanie_przedmiotow'])) {
              echo '<p>'.$_SESSION['dodawanie_przedmiotow'].'</p>';
              unset($_SESSION['dodawanie_przedmiotow']);
            }
          ?>
        </div>
      </form>
    </section>
    <section>
      <form method="post">
        <h3>EDYTUJ PRZEDMIOT</h3>
        <select name="wyb_przedmiot">
          <?php
            for ($i = 0; $i < $_SESSION['ilosc_przedmiotow']; $i++)
              echo '<option value="'.$_SESSION['przedmiot'.$i]['nazwa'].'">'.$_SESSION['przedmiot'.$i]['nazwa'].'</option>';
          ?>
        </select>
        <input type="text" placeholder="Nazwa" name="nazwa"/>
        <button type="submit">Edytuj</button>
        <div class="info">
          <?php
            if (isset($_SESSION['edytowanie_przedmiotow'])) {
              echo '<p>'.$_SESSION['edytowanie_przedmiotow'].'</p>';
              unset($_SESSION['edytowanie_przedmiotow']);
            }
          ?>
        </div>
      </form>
    </section>
    <section>
      <form method="post">
        <h3>USUŃ PRZEDMIOT</h3>
        <select name="wyb_przedmiot">
          <?php
            for ($i = 0; $i < $_SESSION['ilosc_przedmiotow']; $i++)
              echo '<option value="'.$_SESSION['przedmiot'.$i]['id'].'">'.$_SESSION['przedmiot'.$i]['nazwa'].'</option>';
          ?>
        </select>
        <button type="submit">Usuń</button>
        <div class="info">
          <?php
            if (isset($_SESSION['usuwanie_przedmiotow'])) {
              echo '<p>'.$_SESSION['usuwanie_przedmiotow'].'</p>';
              unset($_SESSION['usuwanie_przedmiotow']);
            }
          ?>
        </div>
      </form>
    </section>
  </main>

  <footer>
    <h6>Autor: Szymon Polaczy</h6>
  </footer>

  <a href="../wszyscy/dziennik.php"><button class="cofnij-btn">Wyjdź</button></a>
</body>
</html>