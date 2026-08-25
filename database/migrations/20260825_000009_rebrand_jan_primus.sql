-- Rebrand only unchanged AEFS defaults for the Jan Primus application.
-- Deliberate administrator customizations remain untouched.

UPDATE `instellingen`
SET `waarde` = 'Ledenbeheer'
WHERE `sleutel` = 'application_name'
  AND `waarde` = 'AEFS Eventbeheer';

UPDATE `instellingen`
SET `waarde` = 'vzw Jan Primus'
WHERE `sleutel` = 'organization_name'
  AND `waarde` = 'All Events Forever Sure';

UPDATE `instellingen`
SET `waarde` = 'vzw Jan Primus'
WHERE `sleutel` IN ('mail_from_name', 'mail_reply_to_name')
  AND `waarde` = 'AEFS Eventbeheer';

UPDATE `instellingen`
SET `waarde` = 'info@jan-primus.be'
WHERE `sleutel` = 'mail_reply_to_address'
  AND `waarde` = '';
