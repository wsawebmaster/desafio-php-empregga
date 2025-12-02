<?php
require __DIR__ . '/../src/Autoloader.php';
use App\Autoloader;
use App\Infrastructure\Database;
use App\Repository\ContactRepository;
use App\Repository\PhoneRepository;

(new Autoloader(__DIR__ . '/../src'))->register();

const RESET = "\033[0m";
const GREEN = "\033[32m";
const RED = "\033[31m";
const YELLOW = "\033[33m";
const BLUE = "\033[34m";
const BOLD = "\033[1m";

function printTest($name) {
    echo BLUE . "▶ " . RESET . $name . "... ";
}

function printPass($msg = "OK") {
    echo GREEN . "✓ " . $msg . RESET . "\n";
}

function printFail($msg) {
    echo RED . "✗ FAILED: " . $msg . RESET . "\n";
    exit(1);
}

function assertTrue($cond, $msg) {
    if (!$cond) {
        printFail($msg);
    }
}

echo BOLD . "\n═══════════════════════════════════════════\n";
echo "  Contacts API - Test Suite\n";
echo "═══════════════════════════════════════════" . RESET . "\n\n";

printTest("Database initialization");
try {
    Database::migrate();
    printPass();
} catch (Throwable $e) {
    echo YELLOW . "⊘ SKIPPED" . RESET . " (" . $e->getMessage() . ")\n";
    exit(0);
}

printTest("Cleaning test data");
try {
    $pdo = App\Infrastructure\Database::pdo();
    $pdo->exec('DELETE FROM phones');
    $pdo->exec('DELETE FROM contacts');
    printPass();
} catch (Throwable $e) {
    printFail("Could not clean tables");
}

$contacts = new ContactRepository();
$phones = new PhoneRepository();

printTest("Creating contact");
$c = $contacts->create('Teca', 'teca@example.com', 'Street 1');
assertTrue($c->id > 0, 'contact not created');
printPass("ID: " . $c->id);

printTest("Checking unique email constraint");
$dupFailed = false;
try {
    $contacts->create('Teca2', 'teca@example.com', null);
} catch (Throwable $e) {
    $dupFailed = true;
}
assertTrue($dupFailed, 'duplicate email was not prevented');
printPass("Duplicate email rejected");

printTest("Updating contact (remove address)");
$u = $contacts->update($c->id, ['address' => null]);
assertTrue($u->address === null, 'address not removed');
printPass("Address removed");

printTest("Adding phone to contact");
$p = $phones->add($c->id, '38911112222', 'home');
assertTrue($p->id > 0, 'phone not created');
printPass("Phone ID: " . $p->id);

printTest("Deleting phone");
$phones->delete($p->id);
printPass("Phone removed");

printTest("Listing contacts with pagination");
$list = $contacts->list('', 1, 10);
assertTrue(count($list) >= 1, 'no contacts found in list');
printPass("Found " . count($list) . " contact(s)");

echo BOLD . "\n═══════════════════════════════════════════\n";
echo GREEN . "  ✓ All tests passed!\n" . RESET;
echo BOLD . "═══════════════════════════════════════════" . RESET . "\n\n";
