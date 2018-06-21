<?php


function cli_start($argc, $argv)
{


    if ($argv[1] === 'migrate') {
        echo 'Start migrate...' . PHP_EOL;
        $orders       = file_get_contents(__DIR__ . '/../data/orders.sql');
        $transactions = file_get_contents(__DIR__ . '/../data/transactions.sql');
        $users        = file_get_contents(__DIR__ . '/../data/users.sql');

        $r = mysqli_query(db_getConnection('order'), $orders);
        $r = $r && mysqli_query(db_getConnection('transaction'), $transactions);
        $r = $r && mysqli_query(db_getConnection('user'), $users);

        if (!$r) {
            echo 'Migrate failed!' . PHP_EOL;
            exit(1);
        }

        echo 'End migrate...' . PHP_EOL;
        exit(0);
    }

    if ($argv[1] === 'transactions') {
        echo 'Start handle transactions...' . PHP_EOL;
        $ordersConnection = db_getConnection('order');
        $orders = m_Order_get_unhandled();

        var_dump($orders); exit();
        while ($row = mysqli_fetch_array($orders, MYSQLI_ASSOC)) {
            var_dump($row);
        }

        mysqli_free_result($orders);
        mysqli_close($ordersConnection);

        exit(0);
    }




}

;
