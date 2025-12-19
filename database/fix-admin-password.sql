-- Fix admin password hash in production
-- Run this SQL on the production database
-- clave admin123 por defecto del admin.
UPDATE users 
SET password_hash = '$2y$12$dFC.OwPt0Ao.5IHiH4ezx.8fBM3DDgRHalEsAwynmIISpvC3hR0yS' 
WHERE username = 'admin';

-- Verify the update
SELECT username, email, role, 
       SUBSTRING(password_hash, 1, 20) as hash_preview,
       is_active
FROM users 
WHERE username = 'admin';
