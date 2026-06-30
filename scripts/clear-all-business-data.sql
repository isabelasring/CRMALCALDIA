-- Vacía datos de negocio de EspoCRM (PostgreSQL).
-- NO elimina la base de datos ni el esquema.
-- CONSERVA: usuarios, roles, equipos, preferencias, jobs del sistema, plantillas, configuración.
--
-- NOTA: En deploy (Dokploy) esto se ejecuta solo vía scripts/wipe-business-data.php
-- la primera vez. No hace falta correr este SQL a mano salvo emergencia.
--
-- Uso manual (contenedor espocrm-db):
--   docker exec -i espocrm-db psql -U espocrm -d espocrm < scripts/clear-all-business-data.sql

BEGIN;

-- Entidades custom Alcaldía y casos
TRUNCATE TABLE
    public.acta_visita,
    public.actuo_archivo,
    public.asignacion_historial,
    public.comunicacion_caso,
    public.c_case_document,
    public.case_contact,
    public.case_knowledge_base_article,
    public."case"
RESTART IDENTITY CASCADE;

-- CRM estándar (contactos, cuentas, actividades, documentos)
TRUNCATE TABLE
    public.account_contact,
    public.account_document,
    public.account_portal_user,
    public.account_target_list,
    public.account,
    public.contact_document,
    public.contact_meeting,
    public.contact_opportunity,
    public.contact_target_list,
    public.contact,
    public.document_lead,
    public.document_opportunity,
    public.document,
    public.document_folder_path,
    public.document_folder,
    public.lead_capture_log_record,
    public.lead_capture,
    public.lead_meeting,
    public.lead_target_list,
    public.lead,
    public.opportunity,
    public.task,
    public.meeting_user,
    public.meeting,
    public.call_contact,
    public.call_lead,
    public.call_user,
    public.call,
    public.campaign_log_record,
    public.campaign_target_list_excluding,
    public.campaign_target_list,
    public.campaign_tracking_url,
    public.campaign,
    public.mass_email_target_list_excluding,
    public.mass_email_target_list,
    public.mass_email,
    public.target_list_user,
    public.target_list_category_path,
    public.target_list_category,
    public.target_list,
    public.target,
    public.knowledge_base_article_knowledge_base_category,
    public.knowledge_base_article_portal,
    public.knowledge_base_article,
    public.knowledge_base_category_path,
    public.knowledge_base_category,
    public.c_location
RESTART IDENTITY CASCADE;

-- Stream, notas, adjuntos, historial
TRUNCATE TABLE
    public.action_history_record,
    public.note_user,
    public.note_team,
    public.note_portal,
    public.note,
    public.stream_subscription,
    public.star_subscription,
    public.notification,
    public.user_reaction,
    public.entity_email_address,
    public.entity_phone_number,
    public.entity_team,
    public.entity_user,
    public.attachment,
    public.email_queue_item,
    public.email_user,
    public.email_inbound_email,
    public.email_email_address,
    public.email_email_account,
    public.email,
    public.sms_phone_number,
    public.sms,
    public.reminder,
    public.autofollow,
    public.import_error,
    public.import_entity,
    public.import,
    public.export,
    public.mass_action,
    public.webhook_queue_item,
    public.webhook_event_queue_item,
    public.auth_log_record,
    public.auth_token,
    public.app_log_record,
    public.scheduled_job_log_record,
    public.job,
    public.kanban_order,
    public.array_value,
    public.password_change_request,
    public.two_factor_code,
    public.o_auth_account,
    public.external_account,
    public.phone_number
RESTART IDENTITY CASCADE;

-- Reiniciar consecutivos
UPDATE public.next_number
SET value = 1
WHERE entity_type IN (
    'Case', 'ActaVisita', 'ActuoArchivo', 'ComunicacionCaso', 'AsignacionHistorial',
    'Contact', 'Account', 'Document', 'Task', 'Lead', 'Opportunity'
);

COMMIT;

-- Tablas NO tocadas: user, role, team, preferences, scheduled_job, template, layout_*, etc.
