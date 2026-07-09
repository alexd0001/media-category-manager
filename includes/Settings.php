<?php

namespace MediaCategoryManager;

if (!defined('ABSPATH')) {
    exit;
}

final class Settings
{
    public const TAXONOMY_CATEGORY = 'attachment_category';
    public const TAXONOMY_TAG = 'attachment_tag';

    public const QUERY_CATEGORY = 'mcm_category';
    public const QUERY_VIEW = 'mcm_view';
    public const VIEW_ALL = 'all';
    public const VIEW_UNCATEGORIZED = 'uncategorized';

    public const NONCE_ACTION = 'mcm_admin_nonce';
    public const NONCE_NAME = 'mcm_nonce';

    public const AJAX_BULK_ASSIGN = 'mcm_bulk_assign';

    private function __construct()
    {
    }
}
