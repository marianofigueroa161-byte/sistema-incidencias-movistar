<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ProtegerHistorialIncidencias extends Migration
{
    public function up()
    {
        $this->db->query("
            ALTER TABLE historial_incidencias
            DROP FOREIGN KEY historial_incidencias_incidencia_id_foreign,
            DROP FOREIGN KEY historial_incidencias_usuario_id_foreign,
            ADD CONSTRAINT fk_historial_incidencia_restrict
                FOREIGN KEY (incidencia_id)
                REFERENCES incidencias(id)
                ON DELETE RESTRICT
                ON UPDATE RESTRICT,
            ADD CONSTRAINT fk_historial_usuario_restrict
                FOREIGN KEY (usuario_id)
                REFERENCES usuarios(id)
                ON DELETE RESTRICT
                ON UPDATE RESTRICT
        ");

        $this->db->query("
            CREATE TRIGGER historial_incidencias_bloquear_update
            BEFORE UPDATE ON historial_incidencias
            FOR EACH ROW
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'El historial de incidencias no puede modificarse'
        ");

        $this->db->query("
            CREATE TRIGGER historial_incidencias_bloquear_delete
            BEFORE DELETE ON historial_incidencias
            FOR EACH ROW
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'El historial de incidencias no puede eliminarse'
        ");
    }

    public function down()
    {
        $this->db->query("
            DROP TRIGGER IF EXISTS historial_incidencias_bloquear_update
        ");

        $this->db->query("
            DROP TRIGGER IF EXISTS historial_incidencias_bloquear_delete
        ");

        $this->db->query("
            ALTER TABLE historial_incidencias
            DROP FOREIGN KEY fk_historial_incidencia_restrict,
            DROP FOREIGN KEY fk_historial_usuario_restrict,
            ADD CONSTRAINT historial_incidencias_incidencia_id_foreign
                FOREIGN KEY (incidencia_id)
                REFERENCES incidencias(id)
                ON DELETE CASCADE
                ON UPDATE CASCADE,
            ADD CONSTRAINT historial_incidencias_usuario_id_foreign
                FOREIGN KEY (usuario_id)
                REFERENCES usuarios(id)
                ON DELETE CASCADE
                ON UPDATE SET NULL
        ");
    }
}