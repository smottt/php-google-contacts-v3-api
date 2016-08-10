<?php

namespace rapidweb\googlecontacts\factories;

use rapidweb\googlecontacts\helpers\GoogleHelper;
use rapidweb\googlecontacts\objects\Group;

abstract class GroupFactory
{
    public static function getAll(array $queryParams = [])
    {
        $url = 'https://www.google.com/m8/feeds/groups/default/full';

        if ($queryParams) {
            $url .= sprintf('?%s', http_build_query($queryParams));
        }

        $xmlGroups = simplexml_load_string(
            GoogleHelper::doRequest('GET', $url)
        );

        $groupsArray = [];

        foreach ($xmlGroups->entry as $xmlGroupEntry) {
            $groupsArray[] = self::constructGroup($xmlGroupEntry);
        }

        return $groupsArray;
    }

    public static function create($userEmail, $groupTitle)
    {
        $doc = new \DOMDocument();
        $doc->formatOutput = true;

        $entry = $doc->createElement('atom:entry');
        $entry->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:atom',
            'http://www.w3.org/2005/Atom'
        );
        $entry->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:gd',
            'http://schemas.google.com/g/2005'
        );

        $doc->appendChild($entry);

        $category = $doc->createElement('atom:category');
        $category->setAttribute('scheme', 'http://schemas.google.com/g/2005#kind');
        $category->setAttribute('term', 'http://schemas.google.com/contact/2008#group');

        $entry->appendChild($category);

        $titleNode = $doc->createElement('atom:title', $groupTitle);
        $titleNode->setAttribute('type', 'text');

        $entry->appendChild($titleNode);

        $response = GoogleHelper::doRequest(
            'POST',
            sprintf('https://www.google.com/m8/feeds/groups/%s/full', $userEmail),
            $doc->saveXML()
        );

        return self::constructGroup(
            simplexml_load_string($response)
        );
    }

    public static function getBySelfURL($selfURL)
    {
        $response = GoogleHelper::doRequest('GET', $selfURL);

        return self::constructGroup(
            simplexml_load_string($response)
        );
    }

    protected static function constructGroup(\SimpleXMLElement $groupEntry)
    {
        $groupEntry->registerXPathNamespace('gd', 'http://schemas.google.com/g/2005');

        $group          = new Group();
        $group->id      = (string) $groupEntry->id;
        $group->title   = (string) $groupEntry->title;
        $group->content = (string) $groupEntry->content;

        /** @var \SimpleXMLElement $value */
        foreach ($groupEntry->link as $value) {
            $attributes = $value->attributes();

            if ((string) $attributes['rel'] === 'edit') {
                $group->editURL = (string) $attributes['href'];
            } elseif ((string) $attributes['rel'] === 'self') {
                $group->selfURL = (string ) $attributes['href'];
            }
        }

        return $group;
    }
}
