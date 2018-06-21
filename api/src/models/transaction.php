<?php


function m_Transaction_init()
{
    global $_db;

    $_db['_connections']['transaction'] = db_getConnection('transaction');
    if (empty($_db['_connections'])) {
        response_error('Unable to connect database');
    }
}

m_Transaction_init();