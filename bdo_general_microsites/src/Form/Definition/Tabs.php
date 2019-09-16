<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/23/2019
 * Time: 11:34 AM
 */

namespace  Drupal\bdo_general_microsites\Form\Definition;

class Tabs
{
    const RECIPIENTS = [
        'defined_recipients' => 'Defined Recipients',
        'microsite_owner' => 'Microsite Owner',
        'site_administrators' => 'Site Administrators',
        'microsite_administrators' => 'Microsite Administrators',
        'microsite_reviewers' => 'Microsite Reviewers',
    ];

    const ENABLE_MIRCROSITE = [
        'content' => '<div style="font-size: 12px">' .
                        '<p>Dear recipient,</p><br />' .
                        '<p>' .
                            'Please be informed that <strong>[microsite_name]</strong>' .
                            'is now enabled with the following details:' .
                        '</p><br />' .
                        '<p>Name:<strong> [microsite_name]</strong><br />' .
                            'Date of Creation: <strong>[microsite_created_date]</strong><br />' .
                            'Created by: <strong>[microsite_created_by]</strong><br />' .
                            'Effectivity Date: <strong>[effective_from]</strong>' .
                            'to<strong> [effective_to]</strong></p><br /><p><span style="font-size:12px;">' .
                            '<strong><em>This is a system generated message.' .
                            'Please do not reply.</em></strong></span>' .
                        '</p>' .
                    '</div>'
    ];

    const MICROSITE = [
        'content' => '<div style="font-size: 12px">' .
                        '<p>Dear recipient,</p><br />' .
                        '<p>' .
                            'Please be informed that <strong>[microsite_name]</strong> is now <strong>[action]</strong>.' .
                        '</p><br />' .
                        '<p>' .
                            '<span style="font-size:12px;"><strong><em>This is a system generated message. Please do not reply.</em></strong></span>' .
                        '</p>' .
                    '</div>'
    ];

    const REACTIVATE_MICROSITE = [
        'content' => '<div style="font-size: 12px">' .
                        '<p>Dear recipient,</p><br />' .
                        '<p>' .
                            'Please be informed that <strong>[microsite_name]</strong> has been reactivated effective <strong>[effective_from]</strong>' .
                            'to <strong>[effective_to]</strong>.' .
                        '</p><br />' .
                        '<p>' .
                            '<span style="font-size:12px;"><strong><em>This is a system generated message. Please do not reply.</em></strong></span>' .
                        '</p>' .
                    '</div>'
    ];

    const REMINDER = [
        'content' => '<div style="font-size: 12px">' .
                        '<p>Dear recipient,</p><br />' .
                        '<p>' .
                            'Please be informed that <strong>[microsite_name]</strong> will be <strong>[action]</strong> on <strong>[specific_date]</strong>.' .
                        '</p><br />' .
                        '<p>' .
                            '<span style="font-size:12px;"><strong><em>This is a system generated message. Please do not reply.</em></strong></span>' .
                        '</p>' .
                  '</div>'
    ];

    const FOR_REVIEW = [
        'subject' => 'Microsite For Review',
        'content' => '<div style="font-size: 12px">' .
                        '<p>Dear Microsite Reviewers,</p><br />' .
                        '<p>' .
                            'Please be informed that <strong>[microsite_name]</strong> microsite was submitted for your review on <strong>[action_date]</strong>.' .
                        '</p><br />' .
                        '<p>' .
                            '<span style="font-size:12px;"><strong><em>This is a system generated message. Please do not reply.</em></strong></span>' .
                        '</p>' .
                    '</div>',
        'has_recipients' => true,
        'default_recipients' => [
            'microsite_reviewers',
        ]
    ];

    const APPROVED = [
        'subject' => 'Microsite Approved',
        'content' => '<div style="font-size: 12px">' .
                        '<p>Dear Microsite Owner,</p><br />' .
                        '<p>' .
                            'Please be informed that <strong>[microsite_name]</strong> microsite was approved and activated on <strong>[action_date]</strong>.' .
                        '</p><br />' .
                        '<p>' .
                            '<span style="font-size:12px;"><strong><em>This is a system generated message. Please do not reply.</em></strong></span>' .
                        '</p>' .
                    '</div>',
        'has_recipients' => true,
        'default_recipients' => [
            'microsite_owner',
            'site_administrators',
        ]
    ];

    const DISAPPROVED = [
        'subject' => 'Microsite Disapproved',
        'content' => '<div style="font-size: 12px">' .
                        '<p>Dear Microsite Owner,</p><br />' .
                        '<p>' .
                            'Please be informed that <strong>[microsite_name]</strong> microsite was disapproved on <strong>[action_date]</strong>.' .
                        '</p><br />' .
                        '<p>'  .
                            '<span style="font-size:12px;"><strong><em>This is a system generated message. Please do not reply.</em></strong></span>' .
                        '</p>' .
                '</div>',
        'has_recipients' => true,
        'default_recipients' => [
            'microsite_owner',
            'site_administrators',
        ]
];

    const ACTIVATION = [
        'subject' => 'Microsite Activated',
        'content' => '<div style="font-size: 12px">' .
                        '<p>Dear Microsite Owner,</p><br />' .
                        '<p>' .
                            'Please be informed that <strong>[microsite_name]</strong> microsite has been activated on <strong>[action_date]</strong>.' .
                        '</p><br />' .
                        '<p>' .
                            '<span style="font-size:12px;"><strong><em>This is a system generated message. Please do not reply.</em></strong></span>' .
                        '</p>' .
                '</div>',
        'has_recipients' => true,
        'default_recipients' => [
            'microsite_owner',
            'site_administrators',
        ]
    ];

    const DEACTIVATION = [
        'subject' => 'Microsite Deactivated',
        'content' => '<div style="font-size: 12px">' .
                        '<p>Dear Microsite Owner,</p><br />' .
                        '<p>' .
                            'Please be informed that <strong>[microsite_name]</strong> microsite has been deactivated on <strong>[action_date]</strong>.' .
                        '</p><br />' .
                        '<p>' .
                            '<span style="font-size:12px;"><strong><em>This is a system generated message. Please do not reply.</em></strong></span>' .
                        '</p>' .
                '</div>',
        'has_recipients' => true,
        'default_recipients' => [
            'microsite_owner',
            'site_administrators',
        ]
    ];

    const ARCHIVED = [
        'subject' => 'Microsite Archived',
        'content' => '<div style="font-size: 12px">' .
                        '<p>Dear Microsite Owner,</p><br />' .
                        '<p>' .
                            'Please be informed that <strong>[microsite_name]</strong> microsite has been archived on <strong>[action_date]</strong>.' .
                        '</p><br />' .
                        '<p>' .
                            '<span style="font-size:12px;"><strong><em>This is a system generated message. Please do not reply.</em></strong></span>' .
                        '</p>' .
                    '</div>',
        'has_recipients' => true,
        'default_recipients' => [
            'microsite_owner',
            'site_administrators',
        ]
    ];

    const PURGED = [
        'subject' => 'Microsite Purged',
        'content' => '<div style="font-size: 12px">' .
                        '<p>Dear Microsite Owner,</p><br />' .
                        '<p>' .
                            'Please be informed that <strong>[microsite_name]</strong> microsite has been purged on <strong>[action_date]</strong>.' .
                        '</p><br />' .
                        '<p>' .
                            '<span style="font-size:12px;"><strong><em>This is a system generated message. Please do not reply.</em></strong></span>' .
                        '</p>' .
                    '</div>',
        'has_recipients' => true,
        'default_recipients' => [
            'microsite_owner',
            'site_administrators',
        ]
    ];

    const EXPIRED = [
        'subject' => 'Microsite Expired',
        'content' => '<div style="font-size: 12px">' .
                        '<p>Dear Microsite Owner,</p><br />' .
                        '<p>' .
                            'Please be informed that <strong>[microsite_name]</strong> microsite has expired on <strong>[action_date]</strong>.' .
                        '</p><br />' .
                        '<p>' .
                            '<span style="font-size:12px;"><strong><em>This is a system generated message. Please do not reply.</em></strong></span>' .
                        '</p>' .
                    '</div>',
        'has_recipients' => true,
        'default_recipients' => [
            'microsite_owner',
            'site_administrators',
        ]
    ];

    const ITEMS = [
        'enable_microsite' => [
            'title' => 'Enable Microsite Notification',
            'data' => self::ENABLE_MIRCROSITE,
            'key' => 'enable_message'
        ],
        'microsite' => [
            'title' => 'Microsite Notification',
            'data' => self::MICROSITE,
            'key' => 'microsite_notification'
        ],
        'reactivate_microsite' => [
            'title' => 'Re-activate Microsite Notification',
            'data' => self::REACTIVATE_MICROSITE,
            'key' => 'reactivate_message'
        ],
        'reminder' => [
            'title' => 'Reminder Notification',
            'data' => self::REMINDER,
            'key' => 'reminder_message'
        ],
        'for_review' => [
            'title' => 'For review Notification',
            'data' => self::FOR_REVIEW,
            'key' => 'for_review'
        ],
        'approved' => [
            'title' => 'Approved Notification',
            'data' => self::APPROVED,
            'key' => 'approve'
        ],
        'disapproved' => [
            'title' => 'Disapproved Notification',
            'data' => self::DISAPPROVED,
            'key' => 'disapprove'
        ],
        'activation' => [
            'title' => 'Activation Notification',
            'data' => self::ACTIVATION,
            'key' => 'activate'
        ],
        'deactivation' => [
            'title' => 'Deactivation Notification',
            'data' => self::DEACTIVATION,
            'key' => 'deactivate'
        ],
        'archived' => [
            'title' => 'Archived Notification',
            'data' => self::ARCHIVED,
            'key' => 'archive'
        ],
        'purged' => [
            'title' => 'Purged Notification',
            'data' => self::PURGED,
            'key' => 'purge'
        ],
        'expired' => [
            'title' => 'Expired Notification',
            'data' => self::EXPIRED,
            'key' => 'expire'
        ]
    ];
}