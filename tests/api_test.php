<?php
// End-to-end tests for api.php. They talk to a running server over HTTP, so
// the same checks cover the SQLite and the MySQL setup.
//
//   PHP_CLI_SERVER_WORKERS=8 php -S localhost:8000 &
//   php tests/api_test.php http://localhost:8000
//
// Run it against a fresh database: it books real appointments.

date_default_timezone_set('Asia/Dhaka');

$base = rtrim($argv[1] ?? 'http://localhost:8000', '/');
$passed = 0;
$failed = 0;

function check($name, $condition, $detail = null) {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  ok   $name\n";
    } else {
        $failed++;
        echo "  FAIL $name\n";
        if ($detail !== null) {
            echo '       ' . json_encode($detail) . "\n";
        }
    }
}

function api($method, array $params) {
    global $base;
    $url = $base . '/api.php';
    $http = ['method' => $method, 'ignore_errors' => true, 'timeout' => 60];
    if ($method === 'GET') {
        $url .= '?' . http_build_query($params);
    } else {
        $http['header'] = "Content-Type: application/x-www-form-urlencoded\r\n";
        $http['content'] = http_build_query($params);
    }
    $body = file_get_contents($url, false, stream_context_create(['http' => $http]));
    $data = json_decode((string)$body, true);
    if (!is_array($data)) {
        fwrite(STDERR, "Not JSON from $method $url:\n" . substr((string)$body, 0, 500) . "\n");
        exit(1);
    }
    return $data;
}

function client($n, $date, $mechanicId, array $overrides = []) {
    return array_merge([
        'action' => 'book',
        'client_name' => "Test Client $n",
        'address' => "House $n, Road 4, Dhanmondi, Dhaka",
        'phone' => sprintf('0171%07d', $n),
        'car_license' => "DHAKA-METRO-GA-$n",
        'car_engine' => "ENG$n",
        'appointment_date' => $date,
        'mechanic_id' => $mechanicId,
    ], $overrides);
}

function slots($date) {
    $mechanics = [];
    foreach (api('GET', ['action' => 'get_slots', 'date' => $date])['mechanics'] as $m) {
        $mechanics[$m['id']] = $m;
    }
    return $mechanics;
}

function appointments(array $filters) {
    return api('GET', ['action' => 'get_appointments'] + $filters)['appointments'];
}

$day1 = date('Y-m-d', strtotime('+30 days'));
$day2 = date('Y-m-d', strtotime('+31 days'));
$day3 = date('Y-m-d', strtotime('+32 days'));
$past = date('Y-m-d', strtotime('-2 days'));

echo "Testing $base\n\nSlots\n";
$s = slots($day1);
check('five mechanics are seeded', count($s) === 5, count($s));
check('every mechanic starts with 4 free slots', array_sum(array_column($s, 'available_slots')) === 20);

echo "\nValidation\n";
$r = api('POST', ['action' => 'book']);
check('empty form is rejected', $r['success'] === false && strpos($r['message'], 'Client Name is required') !== false, $r);
$r = api('POST', client(1, $day1, 1, ['phone' => 'call me']));
check('phone with letters is rejected', $r['success'] === false && strpos($r['message'], 'Phone') !== false, $r);
$r = api('POST', client(1, $day1, 1, ['car_engine' => 'ENG#1']));
check('engine number with symbols is rejected', $r['success'] === false && strpos($r['message'], 'Engine') !== false, $r);
$r = api('POST', client(1, $past, 1));
check('past date is rejected', $r['success'] === false && strpos($r['message'], 'past') !== false, $r);
$r = api('POST', client(1, '2027-02-30', 1));
check('impossible date is rejected', $r['success'] === false && strpos($r['message'], 'valid Appointment Date') !== false, $r);
$r = api('POST', client(1, $day1, 99));
check('unknown mechanic is rejected', $r['success'] === false && strpos($r['message'], 'does not exist') !== false, $r);
$r = api('GET', client(1, $day1, 1));
check('booking over GET is rejected', $r['success'] === false && strpos($r['message'], 'method') !== false, $r);
$r = api('GET', ['action' => 'drop_tables']);
check('unknown action is rejected', $r['success'] === false, $r);

echo "\nBooking rules\n";
$r = api('POST', client(1, $day1, 1));
check('valid booking is approved', $r['success'] === true && !empty($r['appointment_id']), $r);
$r = api('POST', client(1, $day1, 2));
check('same phone on the same day is a duplicate', ($r['error_type'] ?? '') === 'DUPLICATE_CLIENT', $r);
$r = api('POST', client(50, $day1, 2, ['car_license' => 'DHAKA-METRO-GA-1']));
check('same car on the same day is a duplicate', ($r['error_type'] ?? '') === 'DUPLICATE_CLIENT', $r);
$r = api('POST', client(1, $day2, 3));
check('same client on another day is allowed', $r['success'] === true, $r);
foreach ([2, 3, 4] as $n) {
    $r = api('POST', client($n, $day1, 1));
    check("client $n takes a slot with mechanic 1", $r['success'] === true, $r);
}
$s = slots($day1);
check('mechanic 1 shows as full after 4 bookings', $s[1]['available_slots'] === 0 && $s[1]['is_full'] === true, $s[1]);
check('other mechanics are untouched', $s[2]['available_slots'] === 4, $s[2]);
$r = api('POST', client(5, $day1, 1));
check('5th car for mechanic 1 is refused', ($r['error_type'] ?? '') === 'MECHANIC_FULL', $r);
$r = api('POST', client(5, $day1, 2));
check('the same client can pick another mechanic', $r['success'] === true, $r);

echo "\nAdmin list\n";
check('date filter finds the 5 bookings on day 1', count(appointments(['date' => $day1])) === 5);
check('mechanic filter finds mechanic 2\'s booking', count(appointments(['date' => $day1, 'mechanic_id' => 2])) === 1);
$found = appointments(['search' => 'DHAKA-METRO-GA-3']);
check('search by car licence finds one client', count($found) === 1 && $found[0]['client_name'] === 'Test Client 3', $found);
check('rows include the mechanic name', ($found[0]['mechanic_name'] ?? '') === 'Karim Rahman', $found);

echo "\nAdmin edits\n";
$byName = [];
foreach (appointments([]) as $a) {
    $byName[$a['client_name'] . ' ' . $a['appointment_date']] = $a;
}
$c5 = $byName["Test Client 5 $day1"]['id'];
$c1day2 = $byName["Test Client 1 $day2"]['id'];
$c2 = $byName["Test Client 2 $day1"]['id'];
$update = function ($id, $date, $mechanicId) {
    return api('POST', ['action' => 'update_appointment', 'id' => $id, 'appointment_date' => $date, 'mechanic_id' => $mechanicId]);
};
$r = $update($c5, $day1, 1);
check('moving onto a full mechanic is refused', $r['success'] === false && strpos($r['message'], 'fully occupied') !== false, $r);
$r = $update($c5, $day1, 3);
check('moving to a free mechanic works', $r['success'] === true, $r);
check('the move shows in the slot count', slots($day1)[3]['booked_count'] === 1);
$r = $update($c1day2, $day1, 4);
check('moving onto a day the client already has is refused', $r['success'] === false && strpos($r['message'], 'another appointment') !== false, $r);
$r = $update($c5, 'not-a-date', 3);
check('garbage date is refused', $r['success'] === false, $r);
$r = $update($c5, $past, 3);
check('past date is refused', $r['success'] === false && strpos($r['message'], 'past') !== false, $r);
$r = api('POST', ['action' => 'cancel_appointment', 'id' => $c2]);
check('cancelling works', $r['success'] === true, $r);
check('cancelling frees the slot', slots($day1)[1]['available_slots'] === 1);

echo "\nConcurrency\n";
if (!function_exists('curl_multi_init')) {
    echo "  skip curl extension not loaded\n";
} else {
    // Ten different clients hit mechanic 4 at the same moment. Only four fit.
    $multi = curl_multi_init();
    $handles = [];
    for ($n = 100; $n < 110; $n++) {
        $h = curl_init($base . '/api.php');
        curl_setopt_array($h, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(client($n, $day3, 4)),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
        ]);
        curl_multi_add_handle($multi, $h);
        $handles[] = $h;
    }
    do {
        curl_multi_exec($multi, $running);
        curl_multi_select($multi);
    } while ($running > 0);
    $approved = 0;
    $full = 0;
    foreach ($handles as $h) {
        $r = json_decode(curl_multi_getcontent($h), true);
        if (($r['success'] ?? false) === true) {
            $approved++;
        } elseif (($r['error_type'] ?? '') === 'MECHANIC_FULL') {
            $full++;
        } else {
            echo '       unexpected: ' . curl_multi_getcontent($h) . "\n";
        }
        curl_multi_remove_handle($multi, $h);
    }
    curl_multi_close($multi);
    check('10 simultaneous bookings: exactly 4 approved', $approved === 4, compact('approved', 'full'));
    check('the other 6 are told the mechanic is full', $full === 6, compact('approved', 'full'));
    check('database agrees: 4 booked', slots($day3)[4]['booked_count'] === 4, slots($day3)[4]);
}

echo "\n$passed passed, $failed failed\n";
exit($failed === 0 ? 0 : 1);
