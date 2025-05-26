<?php
// --- HABILITAR ERRORES PHP (DESCOMENTAR PARA DEPURAR) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- FIN HABILITAR ERRORES ---

// --- Incluir archivos necesarios ---
// Asegúrate que estas rutas son correctas desde /api/
$auth_check_path = __DIR__ . '/../dashboard/auth_check.php';
$config_db_path = __DIR__ . '/../config/database.php';

if (!file_exists($auth_check_path)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['data' => [], 'error' => "Error crítico: Falta auth_check.php. Ruta buscada: " . $auth_check_path]);
    exit;
}
if (!file_exists($config_db_path)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['data' => [], 'error' => "Error crítico: Falta config/database.php. Ruta buscada: " . $config_db_path]);
    exit;
}

require_once $auth_check_path;
require_once $config_db_path;

// Indicar que la respuesta será JSON y con UTF-8 (después de los includes)
header('Content-Type: application/json; charset=utf-8');

// --- Verificación de constantes (para depurar) ---
if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS') || !defined('DB_TABLE_AUDITORIA')) {
    http_response_code(500);
    echo json_encode(['data' => [], 'error' => 'Error de configuración: Faltan constantes de base de datos. Verifica config/database.php']);
    exit;
}
if (!function_exists('getPDO')) {
    http_response_code(500);
    echo json_encode(['data' => [], 'error' => 'Error de configuración: La función getPDO() no está definida. Verifica config/database.php']);
    exit;
}

try {
    $pdo = getPDO();
} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Error conexión DB fetch_audits (catch inicial): " . $e->getMessage());
    echo json_encode(['data' => [], 'error' => 'Error interno del servidor al conectar a la base de datos.']);
    exit;
} catch (\Exception $e) { // Capturar otras excepciones de getPDO
    http_response_code(500);
    error_log("Error general en getPDO desde fetch_audits: " . $e->getMessage());
    echo json_encode(['data' => [], 'error' => 'Error interno del servidor al configurar la conexión.']);
    exit;
}

// --- Consulta SQL ---
$sql = "SELECT
            id, empresa_nombre, contacto_nombre, contacto_email,
            contacto_telefono, contacto_whatsapp, perfil_cargo, cargo_otro_texto,
            sectores, sector_otro_texto, empresa_empleados, areas_mejora,
            sistemas, sis_otro_texto, retos, reto_otro_texto,
            soluciones, sol_otro_texto, cuando_empezar, urgencia_nivel,
            como_recibir, recibir_whatsapp, fecha_envio ip_address,

            dq_formacion_equipo, dq_revision_roles, dq_indicadores_medidos, dq_dependencia_terceros, dq_causa_no_conformidades, dq_cuellos_botella_auditorias, dq_tarea_mas_tiempo, dq_datos_tiempo_real, dq_porcentaje_errores_humanos, dq_impacto_devolucion, dq_informes_automaticos, dq_duplicidad_registros, dq_flujo_manual, dq_integracion_calidad_sistemas, dq_resistencia_tecnologia, dq_satisfaccion_sistemas_actuales,
            dg_modelo_negocio, dg_sectores_principales, dg_perfil_clientes, dg_etapa_negocio, dg_reto_principal, dg_cambios_estructurales, dg_areas_mejor_estructuradas, dg_areas_mas_incidencias, dg_comunicacion_departamentos, dg_tareas_duplicadas, dg_tareas_tiempo_innecesario, dg_nivel_erp_actual, dg_integracion_herramientas, dg_gestion_datos_operativa, dg_digitalizacion_global_percibida, dg_compromiso_direccion_mejora, dg_reaccion_equipo_cambios, dg_formacion_nuevas_herramientas, dg_objetivos_prioritarios_proyecto, dg_urgencia_proyecto, dg_expectativa_roi,
            dr_funciones_rrhh, dr_perfil_equipo_rrhh, dr_gestion_procesos_rrhh, dr_quien_gestiona_rrhh, dr_mayor_problema_rrhh, dr_nivel_errores_admin_rrhh, dr_mejora_impacto_rrhh, dr_impacto_esperado_mejoras_rrhh, dr_procesos_seleccion_ano, dr_horas_tareas_admin_semana_rrhh, dr_herramientas_rrhh, dr_almacenamiento_documentos_personal, dr_digitalizacion_formaciones_contratos, dr_situacion_digital_rrhh, dr_automatizacion_actual_rrhh, dr_colaboracion_rrhh_direccion,
            dp_modelo_turnos, dp_variedad_productos, dp_gestion_procesos_clave, dp_quien_coordina_produccion, dp_principal_cuello_botella, dp_tareas_repetitivas_tiempo, dp_donde_mas_errores_retrabajos, dp_automatizar_digitalizar_primero, dp_impacto_mejora_prioritaria, dp_tiempo_tarea_operativa_clave, dp_frecuencia_errores_paradas, dp_conocimiento_coste_parada_error, dp_herramientas_usadas_produccion, dp_registro_informacion_diaria, dp_sistemas_visuales_seguimiento, dp_digitalizacion_produccion, dp_actitud_equipo_herramientas_digitales,
            dc_estructura_equipo, dc_funciones_principales, dc_gestion_procesos_comerciales, dc_herramientas_presupuestos_pedidos, dc_tarea_comercial_mas_tiempo, dc_donde_mas_errores_clientes, dc_problema_frecuente_equipo, dc_mejora_impacto_comercial, dc_beneficio_esperado_mejoras, dc_tiempo_comercial_tareas_repetitivas, dc_incidencias_errores_mes, dc_canales_comunicacion_clientes, dc_crm_activo, dc_registro_interacciones, dc_informes_automaticos_comercial, dc_integracion_comercial_dptos, dc_estado_digital_comercial, dc_herramientas_automatizadas_uso, dc_exp_zonas_paises, dc_exp_idiomas_usados, dc_exp_funciones_equipo, dc_exp_gestion_pedidos, dc_exp_herramientas_coordinacion_clientes_int, dc_exp_mayores_cuellos_botella, dc_exp_generacion_documentacion, dc_exp_error_mas_habitual, dc_exp_automatizacion_util,
            dm_quien_encarga_marketing, dm_tipo_marketing_realizado, dm_presupuesto_anual_marketing, dm_gestion_campanas_acciones, dm_almacenamiento_contenidos_resultados, dm_problema_limita_marketing, dm_dependencia_genera_riesgo, dm_mejora_prioritaria, dm_impacto_esperado_mejoras, dm_campanas_acciones_ano, dm_leads_generados_mes, dm_indicadores_por_campana, dm_canales_digitales_activos, dm_herramientas_gestion_marketing, dm_conexion_marketing_crm_comercial, dm_informes_paneles_resultados, dm_estado_digital_marketing, dm_automatizacion_actual_marketing,
            dl_tipo_productos_gestionados, dl_numero_almacenes_ubicaciones, dl_frecuencia_inventario, dl_volumen_medio_movimientos_semanal, dl_registro_entradas_salidas_ubicaciones, dl_herramienta_picking_inventario, dl_quien_tareas_operativas_clave, dl_problema_frecuente_logistica, dl_situacion_riesgo_operativo, dl_digitalizar_automatizar_primero, dl_impacto_esperado_mejoras_logistica, dl_tiempo_tarea_logistica_frecuente, dl_incidencias_logisticas_mes, dl_conocimiento_coste_incidencia_logistica, dl_penalizaciones_errores_entregas, dl_herramientas_gestion_almacen, dl_registro_movimientos_almacen, dl_visibilidad_stock_otros_dptos, dl_tecnologias_identificacion_productos, dl_digitalizacion_almacen, dl_elementos_automatizados_almacen,
            di_tipo_innovacion_desarrollada, di_tipo_proyectos_id, di_colaboracion_id_areas, di_gestion_proyectos_desarrollo_pruebas, di_registro_resultados_validaciones, di_mayor_problema_id, di_situacion_repetida_frecuencia, di_mejora_util_id, di_impacto_esperado_mejoras_id, di_proyectos_id_ano, di_duracion_proyecto_medio_desarrollo, di_repeticion_errores_pruebas_falta_registro, di_personas_externas_aportan_ideas_id, di_herramienta_principal_gestion_proyectos, di_almacenamiento_documentacion_desarrollos, di_id_conectado_otros_sistemas, di_sistema_priorizar_medir_roi, di_digitalizacion_id, di_automatizaciones_sistemas_id,
            dprl_gestion_prl_actual, dprl_actividades_gestion_prl, dprl_tipo_actividad_industrial, dprl_gestion_procesos_clave_prl, dprl_almacenamiento_documentos_historiales_prl, dprl_problema_frecuente_gestion_prl, dprl_dificulta_operativa_preventiva, dprl_mejora_util_corto_plazo_prl, dprl_impacto_esperado_mejoras_prl, dprl_partes_accidente_ano, dprl_formaciones_prl_ano, dprl_inspeccion_ultimos_2_anos, dprl_tiempo_semanal_tareas_prl, dprl_herramientas_gestion_prl, dprl_control_epis_digital, dprl_trabajadores_acceso_digital_doc_preventiva, dprl_digitalizacion_prl, dprl_sistemas_automatizados_prl,
            daf_funciones_departamento, daf_personas_equipo, daf_gestion_procesos_clave, daf_nivel_estandarizacion_procesos, daf_mayor_problema_operativo, daf_genera_mas_bloqueos_retrasos, daf_mejora_impacto_administrativa, daf_impacto_esperado_mejoras_admin, daf_tiempo_semanal_tareas_repetitivas, daf_errores_administrativos_mes, daf_dependencia_personas_concretas, daf_software_principal_usado, daf_nivel_automatizacion, daf_dashboards_informes_automaticos, daf_sistema_conectado_otros_dptos, daf_estado_digital_area, daf_sistemas_en_uso,
            dt_gestion_transporte_actual, dt_tipo_envios_habituales, dt_numero_agencias_transportistas, dt_gestion_comunicacion_agencias, dt_planificacion_registro_envios, dt_almacenamiento_datos_transporte, dt_mayor_problema_transporte, dt_dificulta_dia_a_dia_logistica, dt_mejorar_primero_gestion_transportes, dt_impacto_esperado_mejoras_transporte, dt_envios_semanales, dt_incidencias_mes_entregas_fallidas_errores, dt_tiempo_semanal_coordinar_transportes, dt_conocimiento_costes_medios_envio_ruta, dt_herramienta_gestion_envios, dt_generacion_documentacion_logistica, dt_informes_transporte_disponibles, dt_digitalizacion_actual_transporte, dt_elementos_digitales_activos_transporte,
            dcomp_proceso_general_compras, dcomp_participacion_decisiones_compra, dcomp_acuerdos_marco_proveedores_clave, dcomp_gestion_pedidos_solicitudes, dcomp_quien_genera_solicitudes_compra, dcomp_almacenamiento_info_proveedores_precios_pedidos, dcomp_problema_frecuente_dia_a_dia, dcomp_tarea_consume_mas_tiempo_errores, dcomp_mejora_impacto_inmediato, dcomp_beneficio_esperado_mejoras, dcomp_tiempo_promedio_gestionar_pedido, dcomp_frecuencia_pedidos_urgentes, dcomp_numero_proveedores_aprox, dcomp_visibilidad_volumen_comprado_proveedor, dcomp_herramientas_utilizadas, dcomp_aprobacion_solicitudes_compra, dcomp_comunicacion_proveedores, dcomp_estado_digitalizacion_area, dcomp_elementos_digitales_activos,
            ding_funciones_principales_equipo_tecnico, ding_tipo_producto_cubre_ingenieria, ding_organizacion_documentacion_tecnica, ding_generacion_gestion_planos_disenos, ding_registro_versiones_modificaciones_revisiones, ding_problema_mas_habitual_area_tecnica, ding_genera_mas_trabajo_innecesario_retrabajo, ding_mejora_aportaria_mayor_valor, ding_impacto_esperado_mejoras_tecnicas, ding_planos_modificaciones_mensuales, ding_tiempo_medio_diseno_modificacion, ding_frecuencia_errores_diseno_incorrecto, ding_retrasos_entregas_pedidos_temas_tecnicos, ding_software_tecnico_habitual, ding_automatismos_plantillas_tecnicas, ding_compartir_documentacion_otros_dptos, ding_estado_digitalizacion_area_tecnica, ding_herramientas_digitales_activas_hoy,
            dsat_tipo_clientes_soporte, dsat_organizacion_atencion_sat, dsat_quien_coordina_servicio_tecnico, dsat_recepcion_gestion_incidencia_tecnica, dsat_asignacion_tecnicos_planificacion_visitas, dsat_problema_frecuente_sat, dsat_tareas_generan_errores_repeticiones, dsat_mejora_prioritaria, dsat_beneficio_esperado_mejoras_sat, dsat_incidencias_gestionadas_mensualmente, dsat_tiempo_medio_incidencia_completa, dsat_frecuencia_retrasos_errores_sat, dsat_herramientas_gestion_sat, dsat_comunicacion_datos_servicio_cliente, dsat_sat_conectado_otros_dptos, dsat_situacion_digital_sat
        FROM auditoria_bliss
        ORDER BY fecha_envio DESC";

try {
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll();

    // DataTables espera un objeto con una clave "data"
    echo json_encode(['data' => $results]);

} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Error fetching audits: " . $e->getMessage() . " SQL: " . $sql);
    echo json_encode(['data' => [], 'error' => 'Error al obtener los datos de auditoría.']);
    exit;
}
?>