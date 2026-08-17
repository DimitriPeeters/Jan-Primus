-- Verwijder historische plaintext/ciphertext-kopieën van gevoelige velden uit
-- auditpayloads. De auditactie, actor, entiteit en overige wijzigingshistoriek
-- blijven behouden. Deze migratie is veilig opnieuw uitvoerbaar.

UPDATE audit_logs
SET old_values = JSON_SET(
    old_values,
    '$.rijksregisternummer',
    '[afgeschermd]'
)
WHERE JSON_CONTAINS_PATH(
    old_values,
    'one',
    '$.rijksregisternummer'
) = 1
  AND COALESCE(
      JSON_UNQUOTE(
          JSON_EXTRACT(
              old_values,
              '$.rijksregisternummer'
          )
      ),
      ''
  ) NOT IN ('', '[afgeschermd]');

UPDATE audit_logs
SET new_values = JSON_SET(
    new_values,
    '$.rijksregisternummer',
    '[afgeschermd]'
)
WHERE JSON_CONTAINS_PATH(
    new_values,
    'one',
    '$.rijksregisternummer'
) = 1
  AND COALESCE(
      JSON_UNQUOTE(
          JSON_EXTRACT(
              new_values,
              '$.rijksregisternummer'
          )
      ),
      ''
  ) NOT IN ('', '[afgeschermd]');

UPDATE audit_logs
SET old_values = JSON_SET(
    old_values,
    '$.rekeningnummer',
    '[afgeschermd]'
)
WHERE JSON_CONTAINS_PATH(
    old_values,
    'one',
    '$.rekeningnummer'
) = 1
  AND COALESCE(
      JSON_UNQUOTE(
          JSON_EXTRACT(
              old_values,
              '$.rekeningnummer'
          )
      ),
      ''
  ) NOT IN ('', '[afgeschermd]');

UPDATE audit_logs
SET new_values = JSON_SET(
    new_values,
    '$.rekeningnummer',
    '[afgeschermd]'
)
WHERE JSON_CONTAINS_PATH(
    new_values,
    'one',
    '$.rekeningnummer'
) = 1
  AND COALESCE(
      JSON_UNQUOTE(
          JSON_EXTRACT(
              new_values,
              '$.rekeningnummer'
          )
      ),
      ''
  ) NOT IN ('', '[afgeschermd]');
