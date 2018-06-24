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

        $begin = time();
        $end   = $begin + 55;

        while (1) {
            echo 'Start handle orders...' . PHP_EOL;
            $orders = m_Order_get_unhandled();

            while ($row = mysqli_fetch_array($orders, MYSQLI_ASSOC)) {
                echo 'Handle order ' . $row['id'] . (string) m_Order_handle($row['id']) . PHP_EOL;
            }

            mysqli_free_result($orders);

            if (time() > $end) {
                exit(0);
            }

            sleep(5);
        }


    }


}

;
