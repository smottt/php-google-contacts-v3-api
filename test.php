<?php

require_once __DIR__ . '/vendor/autoload.php';

use rapidweb\googlecontacts\factories\ContactFactory;

$contacts = ContactFactory::getAll();

if (count($contacts)) {
    echo 'Test retrieved '.count($contacts).' contacts.';
} else {
    echo 'No contacts retrieved!';
}
