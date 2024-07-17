<?php
$token = 'YOUR_TELEGRAM_BOT_API_TOKEN';
$bluewallet_address = 'YOUR_BLUEWALLET_BTC_ADDRESS';
$expected_amount = 0.001; // Set your expected amount in BTC
$admin_chat_id = 'YOUR_TELEGRAM_CHAT_ID'; // Admin's chat ID for notifications

$users_file = 'users.json';
$escrows_file = 'escrows.json';

function sendMessage($chat_id, $message) {
    global $token;
    $url = "https://api.telegram.org/bot$token/sendMessage";
    $post_fields = [
        'chat_id' => $chat_id,
        'text' => $message
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_exec($ch);
    curl_close($ch);
}

function logMessage($message) {
    $logfile = 'bot.log';
    file_put_contents($logfile, date('Y-m-d H:i:s') . " - " . $message . PHP_EOL, FILE_APPEND);
}

function checkPayment() {
    global $bluewallet_address, $expected_amount;
    $url = "https://blockchain.info/rawaddr/$bluewallet_address";
    $response = file_get_contents($url);
    $data = json_decode($response, true);

    if (isset($data['total_received'])) {
        $total_received = $data['total_received'] / 100000000; // Convert satoshi to BTC
        return $total_received >= $expected_amount;
    } else {
        return false;
    }
}

function getBalance() {
    global $bluewallet_address;
    $url = "https://blockchain.info/rawaddr/$bluewallet_address";
    $response = file_get_contents($url);
    $data = json_decode($response, true);

    if (isset($data['final_balance'])) {
        $balance = $data['final_balance'] / 100000000; // Convert satoshi to BTC
        return $balance;
    } else {
        return 0;
    }
}

function listTransactions() {
    global $bluewallet_address;
    $url = "https://blockchain.info/rawaddr/$bluewallet_address";
    $response = file_get_contents($url);
    $data = json_decode($response, true);

    if (isset($data['txs'])) {
        $transactions = [];
        foreach ($data['txs'] as $tx) {
            $tx_hash = $tx['hash'];
            $tx_time = date('Y-m-d H:i:s', $tx['time']);
            $tx_amount = $tx['result'] / 100000000; // Convert satoshi to BTC
            $transactions[] = "Hash: $tx_hash, Time: $tx_time, Amount: $tx_amount BTC";
        }
        return $transactions;
    } else {
        return [];
    }
}

function loadUsers() {
    global $users_file;
    if (file_exists($users_file)) {
        return json_decode(file_get_contents($users_file), true) ?: [];
    }
    return [];
}

function saveUsers($users) {
    global $users_file;
    file_put_contents($users_file, json_encode($users));
}

function registerUser($chat_id, $role = 'user') {
    $users = loadUsers();
    if (!isset($users[$chat_id])) {
        $users[$chat_id] = [
            'role' => $role,
            'timestamp' => time()
        ];
        saveUsers($users);
        return true;
    }
    return false;
}

function loadEscrows() {
    global $escrows_file;
    if (file_exists($escrows_file)) {
        return json_decode(file_get_contents($escrows_file), true) ?: [];
    }
    return [];
}

function saveEscrows($escrows) {
    global $escrows_file;
    file_put_contents($escrows_file, json_encode($escrows));
}

function createEscrow($chat_id, $amount, $seller_chat_id) {
    $escrows = loadEscrows();
    $escrows[] = [
        'buyer_chat_id' => $chat_id,
        'seller_chat_id' => $seller_chat_id,
        'amount' => $amount,
        'status' => 'pending',
        'timestamp' => time(),
        'negotiation' => []
    ];
    saveEscrows($escrows);
}

function listEscrows($chat_id) {
    $escrows = loadEscrows();
    $user_escrows = array_filter($escrows, function($escrow) use ($chat_id) {
        return $escrow['buyer_chat_id'] == $chat_id || $escrow['seller_chat_id'] == $chat_id;
    });
    return $user_escrows;
}

function updateEscrow($chat_id, $amount, $status) {
    $escrows = loadEscrows();
    foreach ($escrows as &$escrow) {
        if (($escrow['buyer_chat_id'] == $chat_id || $escrow['seller_chat_id'] == $chat_id) && $escrow['amount'] == $amount) {
            $escrow['status'] = $status;
            break;
        }
    }
    saveEscrows($escrows);
}

function notifyAdmin($message) {
    global $admin_chat_id;
    sendMessage($admin_chat_id, $message);
}

function handleAdminCommands($chat_id, $message) {
    if ($message == "/allusers") {
        $users = loadUsers();
        $user_list = "Registered Users:\n";
        foreach ($users as $id => $user) {
            $user_list .= "ID: $id, Role: {$user['role']}, Registered: " . date('Y-m-d H:i:s', $user['timestamp']) . "\n";
        }
        sendMessage($chat_id, $user_list);
    } elseif (preg_match("/^\/setrole (\d+) (\w+)$/", $message, $matches)) {
        $user_id = $matches[1];
        $role = $matches[2];
        $users = loadUsers();
        if (isset($users[$user_id])) {
            $users[$user_id]['role'] = $role;
            saveUsers($users);
            sendMessage($chat_id, "User $user_id role updated to $role.");
        } else {
            sendMessage($chat_id, "User not found.");
        }
    }
}

function handleNegotiation($chat_id, $message) {
    $escrows = loadEscrows();
    foreach ($escrows as &$escrow) {
        if ($escrow['buyer_chat_id'] == $chat_id || $escrow['seller_chat_id'] == $chat_id) {
            $escrow['negotiation'][] = [
                'chat_id' => $chat_id,
                'message' => $message,
                'timestamp' => time()
            ];
            saveEscrows($escrows);
            if ($escrow['buyer_chat_id'] == $chat_id) {
                sendMessage($escrow['seller_chat_id'], "Buyer: $message");
            } else {
                sendMessage($escrow['buyer_chat_id'], "Seller: $message");
            }
            break;
        }
    }
}

$input = file_get_contents('php://input');
$update = json_decode($input, TRUE);

if (isset($update['message'])) {
    $chat_id = $update['message']['chat']['id'];
    $message = $update['message']['text'];

    logMessage("Received message: $message from chat ID: $chat_id");

    $users = loadUsers();
    if (!isset($users[$chat_id])) {
        registerUser($chat_id);
        sendMessage($chat_id, "Welcome! You've been registered.");
    }

    $user_role = $users[$chat_id]['role'];

    if ($message == "/start") {
        sendMessage($chat_id, "Welcome to the Escrow Bot! Please send BTC to this address: $bluewallet_address");
    } elseif ($message == "/check") {
        $isPaid = checkPayment();
        if ($isPaid) {
            updateEscrow($chat_id, $expected_amount, 'completed');
            sendMessage($chat_id, "Payment received. Your transaction is now in escrow.");
            notifyAdmin("Payment received from $chat_id. Amount: $expected_amount BTC");
        } else {
            sendMessage($chat_id, "No payment detected. Please send BTC to: $bluewallet_address");
        }
    } elseif ($message == "/balance") {
        $balance = getBalance();
        sendMessage($chat_id, "Current balance: $balance BTC");
    } elseif ($message == "/transactions") {
        $transactions = listTransactions();
        if (empty($transactions)) {
            sendMessage($chat_id, "No transactions found.");
        } else {
            sendMessage($chat_id, "Transactions:\n" . implode("\n", $transactions));
        }
    } elseif (preg_match("/^\/createescrow (\d+(\.\d{1,8})?) (\d+)$/", $message, $matches)) {
        $amount = $matches[1];
        $seller_chat_id = $matches[3];
        createEscrow($chat_id, $amount, $seller_chat_id);
        sendMessage($chat_id, "Escrow created for $amount BTC with seller $seller_chat_id.");
        sendMessage($seller_chat_id, "You have a new escrow request from $chat_id for $amount BTC.");
    } elseif ($message == "/myescrows") {
        $escrows = listEscrows($chat_id);
        if (empty($escrows)) {
            sendMessage($chat_id, "You have no active escrows.");
        } else {
            $escrow_list = array_map(function($escrow) {
                return "Amount: {$escrow['amount']} BTC, Status: {$escrow['status']}, Created: " . date('Y-m-d H:i:s', $escrow['timestamp']);
            }, $escrows);
            sendMessage($chat_id, "Your escrows:\n" . implode("\n", $escrow_list));
        }
    } elseif (preg_match("/^\/message (\d+) (.+)$/", $message, $matches)) {
        $receiver_chat_id = $matches[1];
        $negotiation_message = $matches[2];
        handleNegotiation($receiver_chat_id, $negotiation_message);
        sendMessage($chat_id, "Your message has been sent to $receiver_chat_id.");
    } else {
        sendMessage($chat_id, "Unknown command. Available commands:\n/start\n/check\n/balance\n/transactions\n/createescrow <amount> <seller_chat_id>\n/myescrows\n/message <chat_id> <message>");
    }

    if ($user_role == 'admin') {
        handleAdminCommands($chat_id, $message);
    }
} else {
    logMessage("No message found in update.");
}
?>
