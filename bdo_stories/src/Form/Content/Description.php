<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/13/2019
 * Time: 8:35 AM
 */

namespace Drupal\bdo_stories\Form\Content;

class Description
{
    const STORIES_TYPE = "The values in this field is from the taxonomy vocabulary 'Stories Type' " .
    "(See this <a href='/admin/structure/taxonomy/stories_type'>link</a>). If you can\'t see the type, " .
    "it means that it already have a landing page URL mapping configured. See the table below.";

    const LANDING_PAGE_URL = "The URL can be an internal path (e.g. foundation/stories/view) or an external path " .
    "(e.g. https://network.americanexpress.com). If internal, the URL must not have leading slash(/).";

    const DEFAULT_NOTE = "<br/><strong>NOTE: If no Landing Page URL is configured for a type, 'Default' " .
    "will be used.</strong><br/><br/>";
}