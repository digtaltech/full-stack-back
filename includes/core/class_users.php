<?php

class Users
{

    // GENERAL


    public static function user_info($user_id)
    {
        $q = DB::query("SELECT user_id, village_id, plot_id, access, first_name, last_name, email, phone, updated, last_login
        FROM users WHERE user_id='" . $user_id . "' LIMIT 1;") or die(DB::error());
        if ($row = DB::fetch_row($q)) {
            return [
                'id' => (int) $row['user_id'],
                'village_id' => $row['village_id'],
                'plot_id' => $row['plot_id'],
                'access' => $row['access'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'updated' => date('Y/m/d', $row['updated']),
                'last_login' => date('Y/m/d', $row['last_login'])
            ];
        } else {
            return [
                'id' => 0,
                'village_id' => 0,
                'plot_id' => '',
                'access' => 0,
                'first_name' => '',
                'last_name' => '',
                'email' => '',
                'phone' => '',
                'updated' => '',
                'last_login' => ''
            ];
        }
    }


    public static function users_list($d = [])
    {
        // переменные
        $search = isset($d['search']) && trim($d['search']) ? $d['search'] : '';
        $offset = isset($d['offset']) && is_numeric($d['offset']) ? $d['offset'] : 0;
        $limit = 20;
        $items = [];

        // условие
        $where = [];
        if ($search) {
            $search_escaped = "%" . $search . "%"; // Экранирование строки поиска для безопасности
            $where[] = "email LIKE '" . $search_escaped . "'";
            $where[] = "phone LIKE '" . $search_escaped . "'";
            $where[] = "CONCAT(first_name, ' ', last_name) LIKE '" . $search_escaped . "'";
        }
        $where = $where ? "WHERE " . implode(" OR ", $where) : "";


        // получение информации
        $q = DB::query("SELECT user_id, village_id, plot_id, access, first_name, last_name, email, phone, updated, last_login
            FROM users " . $where . " ORDER BY last_name LIMIT " . $offset . ", " . $limit . ";") or die(DB::error());
        while ($row = DB::fetch_row($q)) {
            $items[] = [
                'id' => (int) $row['user_id'],
                'village_id' => $row['village_id'],
                'plot_id' => $row['plot_id'],
                'access' => $row['access'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'updated' => date('Y/m/d', $row['updated']),
                'last_login' => date('Y/m/d', $row['last_login'])
            ];
        }

        // пагинация
        $q = DB::query("SELECT count(*) FROM users " . $where . ";");
        $count = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
        $url = 'users?';
        if ($search) $url .= '&search=' . $search;
        paginator($count, $offset, $limit, $url, $paginator);

        // вывод
        return ['items' => $items, 'paginator' => $paginator];
    }

    public static function users_fetch($d = [])
    {
        $info = Users::users_list($d);
        HTML::assign('users', $info['items']);
        return ['html' => HTML::fetch('./partials/users_table.html'), 'paginator' => $info['paginator']];
    }

    // ACTIONS

    public static function user_edit_window($d = [])
    {
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        HTML::assign('user', Users::user_info($user_id));
        return ['html' => HTML::fetch('./partials/user_edit.html')];
    }


    public static function user_edit_update($d = [])
    {

        // vars
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        $first_name = isset($d['first_name']) ? trim($d['first_name']) : '';
        $last_name = isset($d['last_name']) ? trim($d['last_name']) : '';

        // Convert email to lowercase
        $email = isset($d['email']) ? strtolower(trim($d['email'])) : '';

        // Remove all non-numeric characters from phone
        $phone = isset($d['phone']) ? preg_replace('/\D+/', '', trim($d['phone'])) : '';

        $plot_id = isset($d['plot_id']) && preg_match('/^[\d,\s]+$/', $d['plot_id']) ? $d['plot_id'] : '';


        $offset = isset($d['offset']) ? preg_replace('~\D+~', '', $d['offset']) : 0;

        if (empty($first_name) || empty($last_name) || empty($email) || empty($phone)) {
            die("Error: Required fields are missing.");
        }
        // update
        if ($user_id) {
            $set = [];
            $set[] = "first_name='" . $first_name . "'";
            $set[] = "last_name='" . $last_name . "'";
            $set[] = "email='" . $email . "'";
            $set[] = "phone='" . $phone . "'";
            $set[] = "plot_id='" . $plot_id . "'";
            $set[] = "updated='" . Session::$ts . "'";
            $set = implode(", ", $set);
            DB::query("UPDATE users SET " . $set . " WHERE user_id='" . $user_id . "' LIMIT 1;") or die(DB::error());
        } else {
            DB::query("INSERT INTO users (
                first_name,
                last_name,
                email,
                phone,
                plot_id,
                updated,
                last_login
            ) VALUES (
                '" . $first_name . "',
                '" . $last_name . "',
                '" . $email . "',
                '" . $phone . "',
                '" . $plot_id . "',
                '" . Session::$ts . "',
                '" . Session::$ts . "'
            );") or die(DB::error());
        }

        // output
        return Users::users_fetch(['offset' => $offset]);
    }



    public static function user_delete_window($d = [])
    {
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        HTML::assign('user', Users::user_info($user_id));
        return ['html' => HTML::fetch('./partials/user_delete_confirm.html')];
    }

    public static function user_delete_update($d = [])
    {
        // Получаем user_id из переданных данных
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        $offset = isset($d['offset']) ? preg_replace('~\D+~', '', $d['offset']) : 0;
        // Если user_id не предоставлен или недопустим, возвращаем ошибку
        if (!$user_id) {
            die("Error: Invalid user ID.");
        }

        // Удаляем пользователя из базы данных
        DB::query("DELETE FROM users WHERE user_id='" . $user_id . "' LIMIT 1;") or die(DB::error());


        // output
        return Users::users_fetch(['offset' => $offset]);
    }
}
