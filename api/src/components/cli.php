<?php

/**
 * @param string $string
 */
function cli_log(string $string)
{
    echo date('d.m.Y H:i:s') . ' ' . $string . PHP_EOL;
}

/**
 * @param $argc
 * @param $argv
 */
function cli_start(int $argc, array $argv)
{

    if ($argv[1] === 'migrate') {
        cli_log('Start migrate...');
        $orders       = file_get_contents(__DIR__ . '/../data/orders.sql');
        $transactions = file_get_contents(__DIR__ . '/../data/transactions.sql');
        $users        = file_get_contents(__DIR__ . '/../data/users.sql');

        $r = mysqli_query(db_getConnection('order'), $orders);
        $r = $r && mysqli_query(db_getConnection('transaction'), $transactions);
        $r = $r && mysqli_query(db_getConnection('user'), $users);

        if (!$r) {
            cli_log('Migrate failed!');
            exit(1);
        }

        cli_log('End migrate...');
        exit(0);
    }

    if ($argv[1] === 'transactions') {

        $begin = time();
        $end   = $begin + 55;

        while (1) {
            cli_log('Start handle orders...');
            $orders = m_Order_get_unhandled();

            while ($row = mysqli_fetch_array($orders, MYSQLI_ASSOC)) {
                cli_log('Handle order ' . $row['id'] . (string) m_Order_handle($row['id']));
            }

            mysqli_free_result($orders);

            if (time() > $end) {
                exit(0);
            }

            sleep(5);
        }

    }
}
