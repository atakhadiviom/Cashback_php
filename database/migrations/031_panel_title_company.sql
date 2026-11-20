-- 031: the panel title is the store/company name, not a generic system title.
-- Undo the generic value seeded by migration 030 so the config company name shows.

UPDATE cashback_settings
   SET panel_title = NULL
 WHERE panel_title = 'سامانه پیگیری فروش و باشگاه مشتریان و کش بک';
