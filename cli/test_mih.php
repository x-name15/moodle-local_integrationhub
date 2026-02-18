<?php
define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

$system = \context_system::instance();

mtrace("---------------------------------------------------");
mtrace("Testing MIH Facade...");
mtrace("---------------------------------------------------");

// Test 1: Static Request
try {
    mtrace("1. Testing mih::request('test-service')...");
    \local_integrationhub\mih::request('test-service-static', '/api', ['a' => 1]);
}
catch (\moodle_exception $e) {
    // We expect service_not_found if it doesn't exist.
    if ($e->errorcode === 'service_not_found') {
        mtrace("   [PASS] MIH handled request directly (Service check failed as expected).");
    }
    else {
        mtrace("   [FAIL] Unexpected error: " . $e->getMessage());
    }
}
catch (\Exception $e) {
    mtrace("   [FAIL] Exception: " . $e->getMessage());
}

// Test 2: Fluent Interface
try {
    mtrace("2. Testing mih::send('test-service')...");
    \local_integrationhub\mih::send('test-service-fluent')
        ->to('/api/v2')
        ->with(['b' => 2])
        ->method('PUT')
        ->dispatch();

}
catch (\moodle_exception $e) {
    if ($e->errorcode === 'service_not_found') {
        mtrace("   [PASS] Fluent chain executed correctly via MIH.");
    }
    else {
        mtrace("   [FAIL] Unexpected error: " . $e->getMessage());
    }
}
catch (\Error $e) {
    mtrace("   [FAIL] PHP Error (Interface mismatch?): " . $e->getMessage());
}

mtrace("---------------------------------------------------");
mtrace("Done.");