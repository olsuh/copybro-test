<?php

class Notification {

    // TEST
/*
CREATE TABLE `user_notifications` (
  `notification_id` bigint(19) UNSIGNED NOT NULL,
  `user_id` bigint(19) UNSIGNED NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `viewed` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `created` int(11) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
*/

  public static function add($data) {
    // vars
    $non_empty_fields = ['user_id', 'title'];
    $can_empty_fields = ['description', 'viewed', 'created'];
    $data += ['created' => time(), 'viewed' => 0];
    //check and prepare
    $error_data = [];
    $values_string = fields_setting($data, $non_empty_fields, $can_empty_fields, $error_data, "VALUES");
    
    if ( $error = fields_error(__METHOD__, $error_data, $values_string) ) return $error;

    DB::query("INSERT INTO user_notifications $values_string;") or die (DB::error());
    return DB::insert_id();
    //users.count_notifications++ ?
  }

  /**
  * Название `GET /notifications.get`
  * Возвращает массив уведомлений
  * У каждого уведомления должен быть заголовок, описание, дата создания и флаг о статусе прочтения
  * При вызове можем отправить опциональный параметр, чтобы получить список только непрочитанных уведомлений
  */
  public static function notifications_get($data) {
    // vars
    $non_empty_fields = ['user_id'];
    $can_empty_fields = ['viewed'];
    //check and prepare
    $error_data = [];
    $where_string = fields_setting($data, $non_empty_fields, $can_empty_fields, $error_data, " AND ");

    if ( $error = fields_error(__METHOD__, $error_data, $where_string) ) return $error;

    // info
    $q = DB::query("SELECT title, description, created, viewed FROM user_notifications WHERE $where_string ;") or die (DB::error());
    $rows = DB::fetch_all($q);
    return $rows;
  }

  public static function notifications_read($data) {
    foreach ($data as $key => $row) {
      foreach ($row as $field => $value) {
        // read :)
        //echo "$field = $value";
      }
    }
    return [
      'message' => 'notifications read.'
    ];
  }

}
