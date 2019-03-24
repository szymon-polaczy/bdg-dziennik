<?php
  class User_Adm {
    private $pdo_db;

    function __construct($pdo) {
      $this->pdo_db = $pdo;
    }

    function getUserById($user_id, $get_info = "*") {
      //check if user_id is a number and check if get_info is a string
      if (!is_numeric($user_id) || !is_string($get_info))
        return NULL;

      //write sql
      $sql = "SELECT ".$get_info." FROM osoba WHERE id='$user_id'";

      //retrive data from database
      $res = $this->pdo_db->sql_record($sql);

      //return data - if there is an array return array if one value return one value
      if (count($res) === 1)
        return $res[$get_info];
      else
        return $res;
    }

    function getUserByCategory($cat_name) {
      //check if cat_name is a string
      if (!is_string($cat_name))
        return NULL;

      //sql settings
      $select = ($cat_name[0] === 'u'? ", klasa.nazwa, klasa.opis" : ($cat_name[0] === 'n'? ", sala.nazwa" : ""));
      $from = ($cat_name[0] === 'u'? ", klasa" : ($cat_name[0] === 'n'? ", sala" : ""));
      $where = ($cat_name[0] === 'u'? "AND `$cat_name`.id_klasa=klasa.id" : ($cat_name[0] === 'n'? "AND `$cat_name`.id_sala=sala.id" : ""));

      //write sql
      $sql = "SELECT osoba.*, `$cat_name`.* ".$select." FROM osoba, `$cat_name` ".$from." WHERE uprawnienia='$cat_name[0]' AND osoba.id=`$cat_name`.id_osoba ".$where;

      //retrive data from database
      $res = $this->pdo_db->sql_table($sql);

      //return data
      return $res;
    }
  }