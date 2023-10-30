<?php

function controller_user($act, $d) {
    if ($act == 'edit_window') return Users::user_edit_window($d);
    if ($act == 'edit_update') return Users::user_edit_update($d);

    if ($act == 'delete_window') return Users::user_delete_window($d);
    if ($act == 'delete_update') return Users::user_delete_update($d);
    return '';
}
