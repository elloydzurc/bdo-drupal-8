<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/27/2019
 * Time: 6:45 PM
 */

namespace Drupal\bdo_enews_edm\Form\Library;

class Content
{
    const INSTRUCTION = '<ul>' .
                            '<li>' .
                                'In the view in browser field, enter the message you want to appear for the view in ' .
                                'browser text of the edm content. Leave the field blank if this will be provided ' .
                                'elsewhere.' .
                            '</li>' .
                            '<li>' .
                                'In the Opt out/Unsubscribe field, enter the message you want to appear for the opt ' .
                                'out text of the edm content. Leave the field blank if this will be provided ' .
                                'elsewhere.' .
                            '</li>' .
                        '</ul>';

    const TITLE_DESC =  '<ul>' .
                            '<li>' .
                                'This will also be the node title once the content has been created.' .
                            '</li>' .
                            '<li>' .
                                'If you change/update the title of the created content(node), the change will ' .
                                'also be reflected here.' .
                            '</li>' .
                        '</ul>';

    const PATH_DESC = '<ul>' .
                            '<li>' .
                                'This will be the path that will be used by the created edm content.' .
                            '</li>' .
                            '<li>' .
                                'If you change/update the path of the created content(node), the change will also be ' .
                                'reflected here.' .
                            '</li>' .
                    '</ul>';

    const TEMPLATE_DESC = 'Select Custom if you do not wish to use a pre-defined template and will create your own' .
                            'edm template instead.';

    const TOKEN_NOTE = 'Use <strong>[edm:url_token]</strong> when attaching the token to the URL.<br/>' .
                        'e.g. http://bdo.com.ph/creditcardsenews/amex/?[edm:url_token]';

    const EDM_NOTE = '<span style="font-weight:bold">View in browser text </span>' .
                            '<div>' .
                                'If you want to use the default view in browser content of the selected template ' .
                                'leave this field blank. Otherwise specify the view in browser content you want the ' .
                                'eDM content to use.' .
                            '</div>';

    const OPTOUT_NOTE = '<span style="font-weight:bold">Opt out / Unsubscribe</span>' .
                        '<div>' .
                            'If you want to use the default opt out content of the selected template leave this ' .
                            'field blank. Otherwise specify the opt out content you want the eDM content to use.'.
                        '</div>';
}