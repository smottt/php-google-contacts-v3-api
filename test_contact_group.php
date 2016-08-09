<?php

require_once __DIR__ . '/vendor/autoload.php';

use rapidweb\googlecontacts\factories\ContactFactory;
use rapidweb\googlecontacts\factories\GroupFactory;
use rapidweb\googlecontacts\helpers\GoogleHelper;

// fill these out in order to test this functionality
$accessToken    = [];
$groupSelfURL   = '';
$contactSelfURL = '';

GoogleHelper::setAccessToken($accessToken);

$group   = GroupFactory::getBySelfURL($groupSelfURL);
$contact = ContactFactory::getBySelfURL($contactSelfURL);

$contact->addToGroup($group);

var_dump(ContactFactory::submitUpdates($contact));

$contact->removeFromGroup($group);

var_dump(ContactFactory::submitUpdates($contact));
