<?php
  session_start();

  if (!isset($_SESSION['zalogowany'])) {
    header('Location: ../wszyscy/index.php');
    exit();
  }

  require_once "../../polacz.php";
  mysqli_report(MYSQLI_REPORT_STRICT);

///---------------------------------------------______-----------------------------------_________________________________--------------//

  //USUWANIE OSÓB

  if (isset($_POST['wyb_osoba'])) {
    $wyb_osoba = $_POST['wyb_osoba'];
    $wszystko_ok = true;

    for ($i = 0; $i < $_SESSION['ilosc_osob']; $i++)
      if ($_SESSION['osoba'.$i]['id'] == $wyb_osoba) {
        $numer_osoby = $i;
        break;
      }

    //Test czy usuwasz samego siebie
    if ($_SESSION['id'] == $wyb_osoba) {
      $wszystko_ok = false;
      $_SESSION['usuwanie_osob'] = "Usuwasz sam siebie, wybierz kogoś innego!";
    }

    //Sprawdzam czy usuwasz jedynego administratora
    if ($_SESSION['osoba'.$numer_osoby]['uprawnienia'] == "a") {
      $ilosc_admin = 0;
      for ($i = 0; $i < $_SESSION['ilosc_osob']; $i++)
        if ($_SESSION['osoba'.$i]['uprawnienia'] == "a")
          if ($ilosc_admin++ > 2)
            break;

      if ($ilosc_admin < 2) {
        $wszystko_ok = false;
        $_SESSION['usuwanie_osob'] = "Usuwasz jedynego administratora, wybierz kogoś innego!";
      }
    }

    //testy czy usuwasz nauczyciela przypisanego do przydziału
    if ($_SESSION['osoba'.$numer_osoby]['uprawnienia'] == "n") {
      try {
        $polaczenie = new mysqli($host, $bd_uzytk, $bd_haslo, $bd_nazwa);
        $polaczenie->query("SET NAMES utf8");

        if ($polaczenie->connect_errno == 0) {
          $sql = sprintf("SELECT * FROM przydzial WHERE id_nauczyciel='%s'",
                          mysqli_real_escape_string($polaczenie, $wyb_osoba));

          if ($rezultat = $polaczenie->query($sql)) {
            if ($rezultat->num_rows > 0) {
              $_SESSION['usuwanie_osob'] = "Ten nauczyciel jest przypisany do przydziałów, nie można go usunąć!";
              $wszystko_ok = false;
            }
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


    //Sprawdzam czy jeśli jesteś uczniem to masz jakieś oceny
    if ($_SESSION['osoba'.$numer_osoby]['uprawnienia'] == "u") {
      try {
        $polaczenie = new mysqli($host, $bd_uzytk, $bd_haslo, $bd_nazwa);
        $polaczenie->query("SET NAMES utf8");

        if ($polaczenie->connect_errno == 0) {
          $sql = sprintf("SELECT * FROM ocena WHERE id_uczen='%s'",
                          mysqli_real_escape_string($polaczenie, $wyb_osoba));

          if ($rezultat = $polaczenie->query($sql)) {
            if ($rezultat->num_rows > 0) {
              $_SESSION['usuwanie_osob'] = "Uczeń posiada oceny, nie można go usunąć!";
              $wszystko_ok = false;
            }
            $rezultat->freoe_result();
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


    //Usuwanie po prostu osoby
    if ($wszystko_ok) {
      try {
        $polaczenie = new mysqli($host, $bd_uzytk, $bd_haslo, $bd_nazwa);
        $polaczenie->query("SET NAMES utf8");

        //Biorę i ogarniam numer osoby, nie id
        if($polaczenie->connect_errno == 0) {
          $numer_osoby = null;
          for ($i = 0; $i < $_SESSION['ilosc_osob']; $i++){
            if ($wyb_osoba == $_SESSION['osoba'.$i]['id'])
              $numer_osoby = $i;
          }



          //Usuwanie odpowiedniego zadania danej osoby
          $zadanie = "";

          switch ($_SESSION['osoba'.$numer_osoby]['uprawnienia']) {
            case 'a': $zadanie = "administrator"; break;
            case 'n': $zadanie = "nauczyciel"; break;
            case 'u': $zadanie = "uczen"; break;
          }

          $sql = sprintf("DELETE FROM `%s` WHERE id_osoba='%s'",
                          mysqli_real_escape_string($polaczenie, $zadanie),
                          mysqli_real_escape_string($polaczenie, $wyb_osoba));

          if ($polaczenie->query($sql))
            $_SESSION['usuwanie_osob'] = "Zadanie osoby zostało usunięte!";
          else
            throw new Exception();



          //Osoba
          $sql = sprintf("DELETE FROM osoba WHERE id='%s'",
                          mysqli_real_escape_string($polaczenie, $wyb_osoba));

          if($polaczenie->query($sql))
            $_SESSION['usuwanie_osob'] = "Osoba została usunięta";
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




  ///---------------------------------------------______-----------------------------------_________________________________--------------//




  //------------------------------------------------WYŚWIETLNIE OSOB-----------------------------------------------//
  try {
    $polaczenie = new mysqli($host, $bd_uzytk, $bd_haslo, $bd_nazwa);
    $polaczenie->query("SET NAMES utf8");

    if ($polaczenie->connect_errno == 0) {
      $sql = "SELECT * FROM osoba";

      if ($rezultat = $polaczenie->query($sql)) {
        $_SESSION['ilosc_osob'] = $rezultat->num_rows;

        for ($i = 0; $i < $_SESSION['ilosc_osob']; $i++) {
          $_SESSION['osoba'.$i] = $rezultat->fetch_assoc();
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

  //WYCIĄGANIE DODATKOWYCH INFORMACJI
  for ($i = 0; $i < $_SESSION['ilosc_osob']; $i++) {

    //ADMINISTATOR
    if ($_SESSION['osoba'.$i]['uprawnienia'] == "a") {
      //NIC
    }

    //NAUCZYCIEL
    if ($_SESSION['osoba'.$i]['uprawnienia'] == "n") {
      try {
        $polaczenie = new mysqli($host, $bd_uzytk, $bd_haslo, $bd_nazwa);
        $polaczenie->query("SET NAMES utf8");

        if ($polaczenie->connect_errno == 0) {
          $sql = sprintf("SELECT * FROM nauczyciel WHERE id_osoba='%s'",
                  mysqli_real_escape_string($polaczenie, $_SESSION['osoba'.$i]['id']));

          if ($rezultat = $polaczenie->query($sql)) {
            if ($rezultat->num_rows == 1) {
              $wiersz = $rezultat->fetch_assoc();
              $_SESSION['osoba'.$i]['id_sala'] = $wiersz['id_sala'];
            }
            $rezultat->free_result();
          } else {
              throw new Exception();
          }

          //Wyciągam informaje o sali
          $sql = sprintf("SELECT * FROM sala WHERE id='%s'",
                  mysqli_real_escape_string($polaczenie, $_SESSION['osoba'.$i]['id_sala']));

          if ($rezultat = $polaczenie->query($sql)) {
            if ($rezultat->num_rows == 1) {
              $wiersz = $rezultat->fetch_assoc();
              $_SESSION['osoba'.$i]['sala_nazwa'] = $wiersz['nazwa'];
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
    }

    //UCZEN
    if ($_SESSION['osoba'.$i]['uprawnienia'] == "u") {
      try {
        $polaczenie = new mysqli($host, $bd_uzytk, $bd_haslo, $bd_nazwa);
        $polaczenie->query("SET NAMES utf8");

        if ($polaczenie->connect_errno == 0) {
          $sql = sprintf("SELECT * FROM uczen WHERE id_osoba='%s'",
                  mysqli_real_escape_string($polaczenie, $_SESSION['osoba'.$i]['id']));

          if ($rezultat = $polaczenie->query($sql)) {
            if ($rezultat->num_rows == 1) {
              $wiersz = $rezultat->fetch_assoc();
              $_SESSION['osoba'.$i]['id_klasa'] = $wiersz['id_klasa'];
              $_SESSION['osoba'.$i]['data_urodzenia'] = $wiersz['data_urodzenia'];
            }
            $rezultat->free_result();
          } else {
            throw new Exception();
          }

          //Wyciągam informaje o klase
          $sql = sprintf("SELECT * FROM klasa WHERE id='%s'",
                  mysqli_real_escape_string($polaczenie, $_SESSION['osoba'.$i]['id_klasa']));

          if ($rezultat = $polaczenie->query($sql)) {
            if ($rezultat->num_rows == 1) {
              $wiersz = $rezultat->fetch_assoc();
              $_SESSION['osoba'.$i]['klasa_nazwa'] = $wiersz['nazwa'];
              $_SESSION['osoba'.$i]['klasa_opis'] = $wiersz['opis'];
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
    }
  }













///---------------------------------------------______-----------------------------------_________________________________--------------//


  //------------------------------------------------WYCIĄGANIE KLAS-----------------------------------------------//

  try {
    $polaczenie = new mysqli($host, $bd_uzytk, $bd_haslo, $bd_nazwa);
    $polaczenie->query("SET NAMES utf8");

    if ($polaczenie->connect_errno == 0) {
      $sql = "SELECT * FROM klasa";

      if ($rezultat = $polaczenie->query($sql)) {
        $_SESSION['ilosc_klas'] = $rezultat->num_rows;

        for ($i = 0; $i < $_SESSION['ilosc_klas']; $i++)
          $_SESSION['klasa'.$i] = $rezultat->fetch_assoc();

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

  //------------------------------------------------WYCIĄGANIE SAL-----------------------------------------------//

  try {
    $polaczenie = new mysqli($host, $bd_uzytk, $bd_haslo, $bd_nazwa);
    $polaczenie->query("SET NAMES utf8");

    if ($polaczenie->connect_errno == 0) {
      $sql = "SELECT * FROM sala";

      if ($rezultat = $polaczenie->query($sql)) {
        $_SESSION['ilosc_sal'] = $rezultat->num_rows;
        for ($i = 0; $i < $_SESSION['ilosc_sal']; $i++) {
          $_SESSION['sala'.$i] = $rezultat->fetch_assoc();
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
    $wszystkoOk = true;

    if(strlen($imie) <= 0 || strlen($imie) > 20) {
      $_SESSION['dodawanie_osob'] = "Imie osoby nie może być puste oraz musi być krótsze od 20 znaków!";
      $wszystkoOk = false;
    }

    if(strlen($nazwisko) <= 0 || strlen($nazwisko) > 30) {
      $_SESSION['dodawanie_osob'] = "Nazwisko osoby nie może być puste oraz musi być krótsze od 30 znaków!";
      $wszystkoOk = false;
    }

    if(strlen($email) <= 0 || strlen($email) > 255) {
      $_SESSION['dodawanie_osob'] = "Email osoby nie może być pusty oraz musi być krótszy od 255 znaków!";
      $wszystkoOk = false;
    }

    //Sprawdzanie poprawności adresu email
    $emailB = filter_var($email, FILTER_SANITIZE_EMAIL);
    if((filter_var($emailB, FILTER_VALIDATE_EMAIL) == false) || ($emailB != $email)) {
      $wszystkoOk = false;
      $_SESSION['dodawanie_osob'] = "Podaj poprawny nowy adres email!";
    }

    //Sprawdzanie czy nowy email nie jest już w bazie danych
    try {
      $polaczenie = new mysqli($host, $bd_uzytk, $bd_haslo, $bd_nazwa);
      $polaczenie->query("SET NAMES utf8");

      if ($polaczenie->connect_errno == 0) {
        $sql = sprintf("SELECT email FROM osoba WHERE email='%s'",
                        mysqli_real_escape_string($polaczenie, $emailB));

        if ($rezultat = $polaczenie->query($sql))
        {
          $ile_emaili = $rezultat->num_rows;
          if ($ile_emaili > 0) {
            $wszystkoOk = false;
            $_SESSION['dodawanie_osob'] = "Taki adres email istnieje już w bazie!";
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

    if(strlen($haslo) < 8 || strlen($haslo) > 32) {
      $_SESSION['dodawanie_osob'] = "Hasło osoby musi posiadać pomiędzy 8 a 32 znakami!";
      $wszystkoOk = false;
    }

    //Prosty test tak o
    if ($uprawnienia != "a" && $uprawnienia != "n" && $uprawnienia != "u") {
      $_SESSION['dodawanie_osob'] = "Uprawnenia zostały błędnie podane. Wybierz odpowienie uprawnienia!";
      $wszystkoOk = false;
    }

    //TESTY DLA UCZNIA I NAUCZYCIELA DODATKOWO
    if ($uprawnienia == "n") {
      //--CZY wyb_sala JEST W BAZIE
      $wyb_salaBaz = false;
      for ($i = 0; $i < $_SESSION['ilosc_sal']; $i++) {
        if ($wyb_sala == $_SESSION['sala'.$i]['nazwa']) {
          $wyb_salaBaz = true;
          break;
        }
      }

      if ($wyb_salaBaz == false) {
        $_SESSION['dodawanie_osob'] = "Wybrana sala nie istenieje w bazie. Wybierz odpowiednią klasę!";
        $wszystkoOk = false;
      }
    } else if ($uprawnienia == "u") {
      //--CZY wyb_klasa JEST W BAZIE
      $wyb_klasaBaz = false;
      for ($i = 0; $i < $_SESSION['ilosc_klas']; $i++) {
        if ($wyb_klasa == $_SESSION['klasa'.$i]['nazwa']) {
          $wyb_klasaBaz = true;
          break;
        }
      }

      if ($wyb_klasaBaz == false) {
        $_SESSION['dodawanie_osob'] = "Wybrana klasa nie istenieje w bazie. Wybierz odpowiednią salę!";
        $wszystkoOk = false;
      }
    }




    //WKŁADANIE DO BAZY
    if ($wszystkoOk) {
      $haslo_hash = password_hash($haslo, PASSWORD_DEFAULT);
      $wyb_klasaId = NULL;
      $wyb_salaId = NULL;

      //FAKTYCZNIE POBIERANIE ID WYBRANEJ KLASY ORAZ SALI
      //**
      //KLASA
      for ($i = 0; $i < $_SESSION['ilosc_klas']; $i++) {
        if ($wyb_klasa == $_SESSION['klasa'.$i]['nazwa']) {
          $wyb_klasaId = $_SESSION['klasa'.$i]['id'];
          break;
        }
      }
      //**
      //Sala
      for ($i = 0; $i < $_SESSION['ilosc_sal']; $i++) {
        if ($wyb_sala == $_SESSION['sala'.$i]['nazwa']) {
          $wyb_salaId = $_SESSION['sala'.$i]['id'];
          break;
        }
      }


      try {
        $polaczenie = new mysqli($host, $bd_uzytk, $bd_haslo, $bd_nazwa);
        $polaczenie->query("SET NAMES utf8");

        if ($polaczenie->connect_errno == 0) {
			       //uprawenienia to cale slowa!!!!!

          $sql = sprintf("INSERT INTO osoba VALUES (NULL, '%s', '%s', '%s', '%s', '%s')",
                  mysqli_real_escape_string($polaczenie, $imie),
                  mysqli_real_escape_string($polaczenie, $nazwisko),
                  mysqli_real_escape_string($polaczenie, $email),
                  mysqli_real_escape_string($polaczenie, $haslo_hash),
                  mysqli_real_escape_string($polaczenie, $uprawnienia));

          if ($rezultat = $polaczenie->query($sql)) {
            $_SESSION['dodawanie_osob'] = "Nowa osoba została dodana!";
          } else {
            throw new Exception();
          }

          //--------TUTAJ ZNOWUSZ MUSIAŁEM UŻYĆ SPRINTF PONIEWAŻ $_SESSION['nId']------------//

          //Wyciągam ID nowododanej osoby
          $sql = sprintf("SELECT id, haslo FROM osoba WHERE email='%s'",
                          mysqli_real_escape_string($polaczenie, $email));

          if ($rezultat = $polaczenie->query($sql))
          {
            $ilu_userow = $rezultat->num_rows;
            if($ilu_userow == 1) {
              $wiersz = $rezultat->fetch_assoc();

              //Wyciagam id nowododanej osoby
              if (password_verify($haslo, $wiersz['haslo'])) {
                $_SESSION['nId'] = $wiersz['id'];
                $rezultat->free_result();
              } else
                throw new Exception();
            }
          }

          //Wkładam ADMINISTRATORA
          if ($uprawnienia == "a") {
            $sql = sprintf("INSERT INTO administrator VALUES ('%s')",
                            mysqli_real_escape_string($polaczenie, $_SESSION['nId']));

            if($rezultat = $polaczenie->query($sql))
              $_SESSION['dodawanie_osob'] = "Nowy administrator został dodany!";
            else
              throw new Exception();
          }

          //Wkładam NAUCZYCIELA
          if ($uprawnienia == "n") {
            $sql = sprintf("INSERT INTO nauczyciel VALUES ('%s', '%s')",
                            mysqli_real_escape_string($polaczenie, $_SESSION['nId']),
                            mysqli_real_escape_string($polaczenie, $wyb_salaId));

            if($rezultat = $polaczenie->query($sql))
              $_SESSION['dodawanie_osob'] = "Nowy nauczyciel został dodany!";
            else
              throw new Exception();
          }

          //Wkładam UCZNIA
          if ($uprawnienia == "u") {
            $sql = sprintf("INSERT INTO uczen VALUES ('%s', '%s', '%s')",
                            mysqli_real_escape_string($polaczenie, $_SESSION['nId']),
                            mysqli_real_escape_string($polaczenie, $wyb_klasaId),
                            mysqli_real_escape_string($polaczenie, $dataUrodzenia));

            if($rezultat = $polaczenie->query($sql))
              $_SESSION['dodawanie_osob'] = "Nowy uczeń został dodany!";
            else
              throw new Exception();
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
  <meta name="author" content="Redzik">

  <link href="https://fonts.googleapis.com/css?family=Open+Sans+Condensed:300" rel="stylesheet">
  <link rel="stylesheet" href="../../../css/style.css">

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.js" type="text/javascript"></script>
  <script src="../../../js/script.js" type="text/javascript"></script>
</head>
<body>
  <header>
    <h1>ZOBACZ, DODAJ, USUŃ, EDYTUJ OSOBY</h1>
  </header>

  <main>
    <section>
      <h2>ZOBACZ OSOBY</h2>

      <?php
        //HEHE to się nigdy nie wydarzy
        if ($_SESSION['ilosc_osob'] == 0) {
          echo '<div class="wiersz-osoba">Nie ma żadnych osób w bazie</div>';
        }

        //WYŚWIETLAM ADMINISTATORÓW
        for ($i = 0; $i < $_SESSION['ilosc_osob']; $i++) {
          if ($i == 0)
            echo '<div class="wiersz-osoba"><h3>ADMINISTRATORZY</h3></div>';

          if ($_SESSION['osoba'.$i]['uprawnienia'] == "a") {
            echo '<div class="wiersz-osoba">';
            echo '<div>'.$_SESSION['osoba'.$i]['id'].'</div>';
            echo '<div>'.$_SESSION['osoba'.$i]['imie'].'</div>';
            echo '<div>'.$_SESSION['osoba'.$i]['nazwisko'].'</div>';
            echo '<div>'.$_SESSION['osoba'.$i]['email'].'</div>';
            echo '<div>'.substr($_SESSION['osoba'.$i]['haslo'], 0, 4).'...'.'</div>';
            echo '<div>'.$_SESSION['osoba'.$i]['uprawnienia'].'</div>';
            echo '</div>';
          }
        }

        //WYŚWIETLAM NAUCZYCIELi
        for ($i = 0; $i < $_SESSION['ilosc_osob']; $i++) {
          if ($i == 0)
            echo '<div class="wiersz-osoba"><h3>NAUCZYCIELE</h3></div>';

          if ($_SESSION['osoba'.$i]['uprawnienia'] == "n") {
            echo '<div class="wiersz-osoba">';
            echo '<div>'.$_SESSION['osoba'.$i]['id'].'</div>';
            echo '<div>'.$_SESSION['osoba'.$i]['imie'].'</div>';
            echo '<div>'.$_SESSION['osoba'.$i]['nazwisko'].'</div>';
            echo '<div>'.$_SESSION['osoba'.$i]['email'].'</div>';
            echo '<div>'.substr($_SESSION['osoba'.$i]['haslo'], 0, 4).'...'.'</div>';
            echo '<div>'.$_SESSION['osoba'.$i]['uprawnienia'].'</div>';
            echo '<div>'.$_SESSION['osoba'.$i]['id_sala'].'</div>';
            echo '<div>'.$_SESSION['osoba'.$i]['sala_nazwa'].'</div>';
            echo '</div>';
          }
        }

        //UCZNIOWIE
        for ($i = 0; $i < $_SESSION['ilosc_osob']; $i++) {
          $ktory = 0;

          if ($i == 0)
            echo '<div class="wiersz-osoba"><h3>UCZNIOWIE</h3></div>';

          if ($_SESSION['osoba'.$i]['uprawnienia'] == "u") {
            echo '<div class="wiersz-osoba">';
            echo '<div>'.$_SESSION['osoba'.$i]['id'].'</div>';
            echo '<div>'.$_SESSION['osoba'.$i]['imie'].'</div>';
            echo '<div>'.$_SESSION['osoba'.$i]['nazwisko'].'</div>';
            echo '<div>'.$_SESSION['osoba'.$i]['email'].'</div>';
            echo '<div>'.substr($_SESSION['osoba'.$i]['haslo'], 0, 4).'...'.'</div>';
            echo '<div>'.$_SESSION['osoba'.$i]['uprawnienia'].'</div>';
            echo '<div>'.$_SESSION['osoba'.$i]['data_urodzenia'].'</div>';
            echo '<div>'.$_SESSION['osoba'.$i]['id_klasa'].'</div>';
            echo '<div>'.$_SESSION['osoba'.$i]['klasa_nazwa'].'</div>';
            echo '<div>'.$_SESSION['osoba'.$i]['klasa_opis'].'</div>';
            echo '</div>';
          }
        }
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
                echo '<option value="'.$_SESSION['sala'.$i]['nazwa'].'">'.$_SESSION['sala'.$i]['nazwa'].'</option>';

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
                echo '<option value="'.$_SESSION['klasa'.$i]['nazwa'].'">'.$_SESSION['klasa'.$i]['nazwa'].'</option>';

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
    <section>
      <form method="post" action="edytowanieosob.php">
        <h2>Edytowanie osób</h2>
        <?php
          //Jeśli nikogo nie ma, ahem nigdy się nie wydarzy
          if ($_SESSION['ilosc_osob'] == 0) {
            echo '<p><span>Nie ma żadnych osób do edytowania</span></p>';
          } else {
            echo '<select name="wyb_osoba">';

            for ($i = 0; $i < $_SESSION['ilosc_osob']; $i++)
              echo '<option value="'.$_SESSION['osoba'.$i]['id'].'">'.$_SESSION['osoba'.$i]['imie'].' | '.$_SESSION['osoba'.$i]['nazwisko'].
                      ' | '.$_SESSION['osoba'.$i]['email'].' | '.$_SESSION['osoba'.$i]['uprawnienia'].'</option>';

            echo '</select>';

            echo '<button type="submit">Edytuj</button>';
          }
        ?>
      </form>
    </section>
    <section>
      <form method="post">
        <h2>Usuwanie osób</h2>
        <?php
          //Jeśli nikogo nie ma, ahem nigdy się nie wydarzy
          if ($_SESSION['ilosc_osob'] == 0) {
            echo '<p><span>Nie ma żadnych osób do usunięcia</span></p>';
          } else if ($_SESSION['ilosc_osob'] == 0 && $_SESSION['osoba0']['uprawnienia'] == "a") { //Jeśli jest tylko administrator
            echo '<p><span>Nie ma żadnych osób do usunięcia, ponieważ jest tylko administrator</span></p>';
          } else {
            //Jak sprawić aby nie można było usunąć administratora
            //Zrobię to w trakcie usuwania - jeśli osoba która usuwasz to administrator i nie ma żadnego innego to nie dajesz usuwać

            echo '<select name="wyb_osoba">';

            for ($i = 0; $i < $_SESSION['ilosc_osob']; $i++)
              echo '<option value="'.$_SESSION['osoba'.$i]['id'].'">'.$_SESSION['osoba'.$i]['imie'].' | '.$_SESSION['osoba'.$i]['nazwisko'].
                      ' | '.$_SESSION['osoba'.$i]['email'].' | '.$_SESSION['osoba'.$i]['uprawnienia'].'</option>';

            echo '</select>';

            echo '<button type="submit">Usuń</button>';
          }
        ?>
        <div class="info">
          <?php
            if (isset($_SESSION['usuwanie_osob'])) {
              echo '<p>'.$_SESSION['usuwanie_osob'].'</p>';
              unset($_SESSION['usuwanie_osob']);
            }
          ?>
        </div>
      </form>
    </section>
  </main>

  <a href="../wszyscy/dziennik.php"><button class="cofnij-btn">Wyjdź</button></a>

  <footer>
    <h6>Autor: Szymon Polaczy</h6>
  </footer>
</body>
</html>