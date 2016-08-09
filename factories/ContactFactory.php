<?php

namespace rapidweb\googlecontacts\factories;

use rapidweb\googlecontacts\helpers\GoogleHelper;
use rapidweb\googlecontacts\objects\Contact;

abstract class ContactFactory
{
    public static function getAll()
    {
        $response = GoogleHelper::doRequest(
            'GET',
            'https://www.google.com/m8/feeds/contacts/default/full?max-results=10000&updated-min=2007-03-16T00:00:00'
        );

        $xmlContacts = simplexml_load_string($response);
        $xmlContacts->registerXPathNamespace('gd', 'http://schemas.google.com/g/2005');

        $contactsArray = array();

        foreach ($xmlContacts->entry as $xmlContactsEntry) {
            $contactDetails = array();

            $contactDetails['id'] = (string) $xmlContactsEntry->id;
            $contactDetails['name'] = (string) $xmlContactsEntry->title;

            foreach ($xmlContactsEntry->children() as $key => $value) {
                $attributes = $value->attributes();

                if ($key == 'link') {
                    if ($attributes['rel'] == 'edit') {
                        $contactDetails['editURL'] = (string) $attributes['href'];
                    } elseif ($attributes['rel'] == 'self') {
                        $contactDetails['selfURL'] = (string) $attributes['href'];
                    }
                }
            }

            $contactGDNodes = $xmlContactsEntry->children('http://schemas.google.com/g/2005');
            foreach ($contactGDNodes as $key => $value) {
                switch ($key) {
                    case 'organization':
                        $contactDetails[$key]['orgName'] = (string) $value->orgName;
                        $contactDetails[$key]['orgTitle'] = (string) $value->orgTitle;
                        break;
                    case 'email':
                        $attributes = $value->attributes();
                        $emailadress = (string) $attributes['address'];
                        $emailtype = substr(strstr($attributes['rel'], '#'), 1);
                        $contactDetails[$key][$emailtype] = $emailadress;
                        break;
                    case 'phoneNumber':
                        $attributes = $value->attributes();
                        $uri = (string) $attributes['uri'];
                        $type = substr(strstr($attributes['rel'], '#'), 1);
                        $e164 = substr(strstr($uri, ':'), 1);
                        $contactDetails[$key][$type] = $e164;
                        break;
                    default:
                        $contactDetails[$key] = (string) $value;
                        break;
                }
            }

            $contactsArray[] = new Contact($contactDetails);
        }

        return $contactsArray;
    }

    public static function getBySelfURL($selfURL)
    {
        $response = GoogleHelper::doRequest('GET', $selfURL);

        return self::constructContact(
            simplexml_load_string($response)
        );
    }

    public static function submitUpdates(Contact $updatedContact)
    {
        $response = GoogleHelper::doRequest(
            'PUT',
            $updatedContact->editURL,
            self::toXML($updatedContact)
        );

        return self::constructContact(
            simplexml_load_string($response)
        );
    }

    public static function create($name, $phoneNumber, $emailAddress)
    {
        $newContact              = new Contact();
        $newContact->name        = $name;
        $newContact->phoneNumber = $phoneNumber;
        $newContact->email       = $emailAddress;

        $response = GoogleHelper::doRequest(
            'POST',
            'https://www.google.com/m8/feeds/contacts/default/full',
            self::toXML($newContact)
        );

        return self::constructContact(
            simplexml_load_string($response)
        );
    }

    protected static function toXML(Contact $contact)
    {
        $doc = new \DOMDocument();
        $doc->formatOutput = true;
        $entry = $doc->createElement('atom:entry');
        $entry->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:atom', 'http://www.w3.org/2005/Atom');
        $entry->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:gd', 'http://schemas.google.com/g/2005');
        $entry->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:gd', 'http://schemas.google.com/g/2005');
        $doc->appendChild($entry);

        $title = $doc->createElement('title', $contact->name);
        $entry->appendChild($title);

        $email = $doc->createElement('gd:email');
        $email->setAttribute('rel', 'http://schemas.google.com/g/2005#work');
        $email->setAttribute('address', $contact->email);
        $entry->appendChild($email);

        if (!empty($contact->phoneNumber)) {
            $contact = $doc->createElement('gd:phoneNumber', $contact->phoneNumber);
            $contact->setAttribute('rel', 'http://schemas.google.com/g/2005#work');
            $entry->appendChild($contact);
        }

        if (!empty($contact->groupMembershipInfo)) {
            foreach ($contact->groupMembershipInfo as $groupInfo) {
                $groupEntry = $doc->createElement('gContact:groupMembershipInfo');
                $groupEntry->setAttribute('deleted', $groupInfo['deleted']);
                $groupEntry->setAttribute('href', $groupInfo['href']);

                $entry->appendChild($groupEntry);

                $groupEntry = null;
            }
        }

        return $doc->saveXML();
    }

    protected static function constructContact(\SimpleXMLElement $contactEntry)
    {
        $contactEntry->registerXPathNamespace('gd', 'http://schemas.google.com/g/2005');

        $contactDetails = array();

        $contactDetails['id'] = (string) $contactEntry->id;
        $contactDetails['name'] = (string) $contactEntry->title;

        foreach ($contactEntry->children() as $key => $value) {
            $attributes = $value->attributes();

            if ($key == 'link') {
                if ($attributes['rel'] == 'edit') {
                    $contactDetails['editURL'] = (string) $attributes['href'];
                } elseif ($attributes['rel'] == 'self') {
                    $contactDetails['selfURL'] = (string) $attributes['href'];
                }
            }
        }

        $gdNodes = $contactEntry->children('http://schemas.google.com/g/2005');

        foreach ($gdNodes as $key => $value) {
            $attributes = $value->attributes();

            if ($key == 'email') {
                $contactDetails[$key] = (string) $attributes['address'];
            } else {
                $contactDetails[$key] = (string) $value;
            }
        }

        $gContactNodes = $contactEntry->children('http://schemas.google.com/contact/2008');

        foreach ($gContactNodes as $key => $value) {
            if ($key === 'groupMembershipInfo') {
                    $groupAttributes = $value->attributes();

                    $contactDetails[$key][] = [
                        'deleted' => (string )$groupAttributes['deleted'],
                        'href'    => (string )$groupAttributes['href']
                    ];
            } else {
                $contactDetails[$key] = (string) $value;
            }
        }

        return new Contact($contactDetails);
    }
}
