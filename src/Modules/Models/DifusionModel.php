<?php

namespace App\Modules\Models;

use App\Helpers\Model;
use PDO;

class DifusionModel extends Model
{
    protected string $table = 'usuario';

    /**
     * Obtener destinatarios según filtros
     *
     * @param array $filters query filters: [all, leads, clients, admins]
     * @return array List of users {email, nombre}
     */
    public function getRecipients(array $filters): array
    {

        $sql = "SELECT email, nombre FROM usuario WHERE 1=1";
        $conditions = [];

        // Si se selecciona 'all', ignoramos otros filtros restrictivos
        if (!empty($filters['all'])) {
            // Solo aseguramos que tengan email
        } else {
            if (!empty($filters['admins'])) {
                $conditions[] = "esAdmin = 1";
            }
            // Clients: Usuarios con suscripción activa
            if (!empty($filters['clients'])) {
                $conditions[] = "idUsuario IN (SELECT idUsuario FROM suscripcion WHERE estado = 'Activa')";
            }
            // Leads: Usuarios SIN suscripción activa
            if (!empty($filters['leads'])) {
                $conditions[] = "idUsuario NOT IN (SELECT idUsuario FROM suscripcion WHERE estado = 'Activa')";
            }
        }







        // Si hay condiciones específicas (y no es all), las unimos con OR
        // Ejemplo: Admins OR Clients -> Si eres admin O cliente, recibes el correo.
        if (!empty($conditions) && empty($filters['all'])) {
            $sql .= " AND (" . implode(" OR ", $conditions) . ")";
                                                                // BORRAR DESPUÉS DE COMPROBAR FUNCIONAMIENTO
        } elseif (empty($filters['all']) && empty($conditions) && empty($filters['test_email'])) {
            // Si no es all y no hay condiciones, no devolvemos nada para seguridad
            return [];
        }

        // BORRAR DESPUÉS DE COMPROBAR FUNCIONAMIENTO
        // Si hay un email de prueba, forzamos que sea ESE usuario, pero CUMPLIENDO las condiciones
        if (!empty($filters['test_email'])) {
            $sql .= " AND email = '" . $filters['test_email'] . "'";
        }

        // Validaciones básicas de email
        $sql .= " AND email IS NOT NULL AND email != '' GROUP BY email";

        $result = $this->query($sql);

        if ($result['status'] === 'ok') {
            return $result['data'];
        }

        return [];
    }
}
