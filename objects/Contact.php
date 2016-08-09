<?php

namespace rapidweb\googlecontacts\objects;

class Contact
{
    public function __construct($contactDetails)
    {
        foreach ($contactDetails as $key => $value) {
            $this->$key = $value;
        }
    }

    public function addToGroup(Group $group)
    {
        if (!isset($this->groupMembershipInfo)) {
            $this->groupMembershipInfo = [];
        }

        foreach ($this->groupMembershipInfo as $key => $membership) {
            if ($membership['href'] === $group->id) {
                $membership['deleted'] = 'false';

                // modify the existing entry
                $this->groupMembershipInfo[$key] = $membership;
                return;
            }
        }

        $this->groupMembershipInfo[] = [
            'href'    => $group->id,
            'deleted' => 'false'
        ];
    }

    public function removeFromGroup(Group $group)
    {
        if (empty($this->groupMembershipInfo)) {
            return;
        }

        foreach ($this->groupMembershipInfo as $key => $membership) {
            if ($membership['href'] === $group->id) {
                unset($this->groupMembershipInfo[$key]);
                break;
            }
        }
    }
}
